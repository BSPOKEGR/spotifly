import httpx
from datetime import date, datetime
from typing import List, Optional, Protocol
import os

from src.core import config
from src.schemas.history_data import DailyObservationOut, HourlyObservationOut

from datetime import datetime, timezone
import logging
from typing import List, Optional, Union

from src.core.dao import Dao
from src.models.point import Point
from src.models.prediction import Prediction
from src.ocsm.base import JSONLDGraph
from src.external_services.interoperability import InteroperabilitySchema
from src.core.exceptions import InvalidWeatherDataError
from src import utils

logger = logging.getLogger(__name__)

class WeatherProvider(Protocol):
    async def get_hourly_history(self, lat: float, lon: float, start: date, end: date, variables: List[str]) -> List[HourlyObservationOut]:
        ...

    async def get_daily_history(self, lat: float, lon: float, start: date, end: date, variables: List[str]) -> List[DailyObservationOut]:
        ...


class OpenMeteoClient:
    BASE_URL = "https://archive-api.open-meteo.com/v1/archive"

    async def get_hourly_history(self, lat: float, lon: float, start: date, end: date, variables: List[str]) -> List[HourlyObservationOut]:
        params = {
            "latitude": lat,
            "longitude": lon,
            "hourly": ",".join(variables),
            "timezone": "auto",
            "start_date": start.isoformat(),
            "end_date": end.isoformat()
        }

        async with httpx.AsyncClient() as client:
            response = await client.get(self.BASE_URL, params=params)
            response.raise_for_status()
            data = response.json()

        timestamps = data["hourly"]["time"]
        results = []

        for i, t in enumerate(timestamps):
            values = {v: data["hourly"][v][i] for v in variables if v in data["hourly"]}
            results.append(HourlyObservationOut(timestamp=datetime.fromisoformat(t), values=values))

        return results

    async def get_daily_history(self, lat: float, lon: float, start: date, end: date, variables: List[str]) -> List[DailyObservationOut]:
        params = {
            "latitude": lat,
            "longitude": lon,
            "daily": ",".join(variables),
            "timezone": "auto",
            "start_date": start.isoformat(),
            "end_date": end.isoformat()
        }

        async with httpx.AsyncClient() as client:
            response = await client.get(self.BASE_URL, params=params)
            response.raise_for_status()
            data = response.json()

        timestamps = data["daily"]["time"]
        results = []

        for i, t in enumerate(timestamps):
            values = {v: data["daily"][v][i] for v in variables if v in data["daily"]}
            results.append(DailyObservationOut(date=date.fromisoformat(t), values=values))

        return results

    async def get_single_day_history(self, lat: float, lon: float, day: date, variables: dict[str, List[str]]) -> tuple[List[HourlyObservationOut], List[DailyObservationOut]]:
        hourly = await self.get_hourly_history(lat, lon, day, day, variables.get("hourly", []))
        daily = await self.get_daily_history(lat, lon, day, day, variables.get("daily", []))
        return hourly, daily

logger = logging.getLogger(__name__)

class OpenMeteoForecast:
    properties = {
        'service': 'openMeteo',
        'operation': 'weatherForecast',
        'dataClassification': 'prediction',
        'dataType': 'weather',
        'endpointURI': 'https://api.open-meteo.com/v1',
        'documentationURI': 'https://open-meteo.com/en/docs',
        'dataExpiration': 3000,
        'dataProximityRadius': 100,
        # Keep the same extracted schema keys as OpenWeatherMap implementation
        'extracted_schema': {
            'period': {
                'timestamp': ['time'],
            },
            'measurements': {
                'ambient_temperature': ['temperature_2m'],
                'ambient_humidity': ['relative_humidity_2m'],
                'wind_speed': ['wind_speed_10m'],
                'wind_direction': ['wind_direction_10m'],
                'precipitation': ['precipitation'],
            }
        },
    }

    def __init__(self):
        self.dao: Optional[Dao] = None

    def setup_dao(self, dao: Dao):
        self.dao = dao

    async def get_weather_forecast5days(self, lat: float, lon: float) -> List[Prediction]:
        """Fetch 5-day hourly forecast from Open-Meteo and store as Predictions."""
        point = await self.dao.find_or_create_point(lat, lon)

        url = (
            f"{self.properties['endpointURI']}/forecast"
            f"?latitude={lat}&longitude={lon}"
            f"&hourly=temperature_2m,relative_humidity_2m,precipitation,wind_speed_10m,wind_gusts_10m,wind_direction_10m"
            f"&forecast_days=5&timezone=UTC"
        )
        data = await utils.http_get(url)

        if 'hourly' not in data or 'time' not in data['hourly']:
            raise InvalidWeatherDataError("Invalid Open-Meteo hourly forecast payload")

        predictions = await self._parse_openmeteo_hourly(point, data['hourly'])
        return predictions

    async def get_weather_forecast5days_ld(self, lat: float, lon: float) -> JSONLDGraph:
        preds = await self.get_weather_forecast5days(lat, lon)
        point = await self.dao.find_point(lat, lon)
        jsonld = InteroperabilitySchema.predictions_to_jsonld(preds, point)
        return jsonld

    async def _parse_openmeteo_hourly(self, point: Point, hourly: dict) -> List[Prediction]:
        """Transform Open-Meteo hourly arrays into Prediction entities, mirroring OWM logic."""
        time_arr = hourly.get('time', [])
        temp_arr = hourly.get('temperature_2m', [])
        rh_arr = hourly.get('relative_humidity_2m', [])
        pr_arr = hourly.get('precipitation', [])
        ws_arr = hourly.get('wind_speed_10m', [])
        wd_arr = hourly.get('wind_direction_10m', [])

        n = len(time_arr)
        if not n:
            return []

        # Ensure all arrays can be indexed safely; pad with None if shorter
        def safe_get(arr, i):
            return arr[i] if i < len(arr) else None

        predictions: List[Prediction] = []
        for i in range(n):
            # Open-Meteo time is ISO8601 (e.g. "2025-10-21T12:00")
            ts_iso = time_arr[i]
            # Normalize to timezone-aware UTC datetime
            dt = datetime.fromisoformat(ts_iso.replace('Z', '+00:00'))
            if dt.tzinfo is None:
                dt = dt.replace(tzinfo=timezone.utc)
            # Build measurements dict using the same keys as OpenWeatherMap extracted schema
            measurements = {
                'ambient_temperature': safe_get(temp_arr, i),
                'ambient_humidity': safe_get(rh_arr, i),
                'wind_speed': safe_get(ws_arr, i),
                'wind_direction': safe_get(wd_arr, i),
                'precipitation': safe_get(pr_arr, i),
            }

            # Persist one Prediction per non-empty measurement, same as OWM parser
            for key, value in measurements.items():
                if value is None:
                    continue
                pred = await Prediction(
                    value=value,
                    measurement_type=key,
                    timestamp=dt,
                    data_type='weather',
                    source='openmeteo',
                    spatial_entity=point
                ).create()
                predictions.append(pred)

        return predictions

# Factory using environment variable
class WeatherClientFactory:
    _provider: Optional[WeatherProvider] = None
    _forecast_client: Optional[OpenMeteoForecast] = None

    @classmethod
    def get_provider(cls) -> WeatherProvider:
        if cls._provider is None:
            provider_name = config.HISTORY_WEATHER_PROVIDER
            if provider_name == "openmeteo":
                cls._provider = OpenMeteoClient()
            else:
                raise ValueError(f"Unsupported weather provider: {provider_name}")
        return cls._provider

    @classmethod
    def get_forecast_provider(cls) -> OpenMeteoForecast:
        if not cls._forecast_client:
            cls._forecast_client = OpenMeteoForecast()
        return cls._forecast_client