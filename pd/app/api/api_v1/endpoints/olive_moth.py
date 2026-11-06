from typing import Any, List, Tuple, Dict
from datetime import datetime, timedelta
import math
import random
import os
import requests
from fastapi import APIRouter, Depends, HTTPException, status, Query
from sqlalchemy.orm import Session

from api import deps
from schemas.olive_moth import (
    OliveMothRiskRequest,
    OliveMothRiskResponse,
    DailyCalculation,
    GenerationStatus,
    TemperatureThresholds,
    AlertInfo,
    AlertCounts
)
from utils.olive_moth_risk import OliveMothRiskCalculator, RiskLevel
from utils.alert_service import AlertService

router = APIRouter()


def transform_hourly_to_daily(weather_response: dict) -> List[Tuple[datetime, float, float]]:
    """
    Transform hourly weather data to daily min/max temperatures.

    Args:
        weather_response: Weather service response with hourly data

    Returns:
        List of (datetime, min_temp, max_temp) tuples
    """
    if not weather_response or 'data' not in weather_response:
        return []

    # Group hourly data by date
    daily_temps: Dict[str, List[float]] = {}

    for entry in weather_response['data']:
        timestamp = entry['timestamp']
        temp = entry['values'].get('temperature')

        if temp is not None:
            date_str = timestamp.split('T')[0]
            daily_temps.setdefault(date_str, []).append(temp)

    # Calculate min/max for each day
    temperature_data: List[Tuple[datetime, float, float]] = []
    for date_str in sorted(daily_temps.keys()):
        temps = daily_temps[date_str]
        if temps:
            min_temp = min(temps)
            max_temp = max(temps)
            date_obj = datetime.strptime(date_str, '%Y-%m-%d')
            temperature_data.append((date_obj, min_temp, max_temp))

    return temperature_data


def get_weather_data_for_parcel(
    lat: float,
    lon: float,
    from_date: datetime.date,
    to_date: datetime.date
) -> List[Tuple[datetime, float, float]]:
    """
    Fetch weather data for coordinates from the weather service.
    Returns list of tuples: (date, min_temp, max_temp)
    """
    try:
        BASE_URL = os.getenv("WEATHER_SERVICE_BASE_URL", "http://openagri-weatherservice-app-1:8010")
        ENDPOINT = f"{BASE_URL}/api/v1/history/hourly/"

        payload = {
            "lat": lat,
            "lon": lon,
            "start": str(from_date),
            "end": str(to_date),
            "variables": ["temperature"],
            "radius_km": 10,
        }

        headers = {
            "accept": "application/json",
            "Content-Type": "application/json",
        }

        resp = requests.post(ENDPOINT, json=payload, headers=headers, timeout=30)
        resp.raise_for_status()
        data = resp.json()

        # Transform hourly to daily min/max
        return transform_hourly_to_daily(data)

    except requests.HTTPError as e:
        raise HTTPException(
            status_code=status.HTTP_502_BAD_GATEWAY,
            detail=f"Weather service error: {getattr(e.response, 'text', str(e))}"
        )
    except Exception as e:
        raise HTTPException(
            status_code=status.HTTP_500_INTERNAL_SERVER_ERROR,
            detail=f"Error fetching weather data: {str(e)}"
        )


@router.post("/olive-moth", response_model=OliveMothRiskResponse)
def calculate_olive_moth_risk(
    *,
    request: OliveMothRiskRequest
) -> Any:
    """
    Calculate Olive Moth risk index on-the-fly for a parcel.

    This endpoint:
    1. Fetches weather data for the specified parcel and date range
    2. Calculates degree-days using the exact PHP algorithm logic (starting from March 1st)
    3. Determines risk level based on accumulated degree-days
    4. Creates alerts for generation emergence dates
    5. Returns comprehensive risk assessment with alert information

    Args:
        parcel_id: Integer ID of the parcel to analyze
        from_date: Start date for analysis
        to_date: End date for analysis

    Returns:
        Complete risk assessment with degree-days, risk level, and alerts
    """
    try:
        # Validate date range
        if request.from_date >= request.to_date:
            raise HTTPException(
                status_code=status.HTTP_400_BAD_REQUEST,
                detail="from_date must be before to_date"
            )

        # Check if date range is reasonable (not more than 1 year)
        date_diff = request.to_date - request.from_date
        if date_diff.days > 365:
            raise HTTPException(
                status_code=status.HTTP_400_BAD_REQUEST,
                detail="Date range cannot exceed 365 days"
            )

        # Fetch weather data for the parcel
        temperature_data = get_weather_data_for_parcel(
            request.lat,
            request.lon,
            request.from_date,
            request.to_date
        )

        if not temperature_data:
            raise HTTPException(
                status_code=status.HTTP_404_NOT_FOUND,
                detail=f"No weather data found for coordinates ({request.lat}, {request.lon}) in the specified date range"
            )

        # Calculate risk using the olive moth calculator
        risk_result = OliveMothRiskCalculator.calculate_risk_index(
            temperature_data=temperature_data,
            start_of_season=datetime(request.from_date.year, 3, 1)  # Start from March 1st
        )

        # Transform generation status into expected format
        generations = []
        thresholds = risk_result["thresholds"]
        generation_status = risk_result["generation_status"]
        
        for gen_name, threshold in thresholds.items():
            emergence_date = None
            risk_level_num = 0
            
            # Find the date when this generation threshold was reached
            for calc in risk_result["daily_degree_days"]:
                if calc["accumulated_degree_days"] >= threshold:
                    emergence_date = datetime.strptime(calc["date"], '%Y-%m-%d').date()
                    break
            
            # Set risk level based on emergence
            if generation_status[f"{gen_name}_reached"]:
                if gen_name == "first_generation":
                    risk_level_num = 1
                elif gen_name == "second_generation":
                    risk_level_num = 2
                elif gen_name == "third_generation":
                    risk_level_num = 3
            
            generations.append(GenerationStatus(
                generation=gen_name,
                threshold=float(threshold),
                emergence_date=emergence_date,
                risk_level=risk_level_num
            ))

        # Transform daily calculations into expected format
        daily_calculations = []
        for calc in risk_result["daily_degree_days"]:
            daily_calculations.append(DailyCalculation(
                date=datetime.strptime(calc["date"], '%Y-%m-%d').date(),
                min_temp=calc["min_temperature"],
                max_temp=calc["max_temperature"],
                daily_dd=calc["daily_degree_days"],
                cumulative_dd=calc["accumulated_degree_days"]
            ))

        # Transform alerts into expected format
        alerts = []
        for alert in risk_result["alerts"]:
            alerts.append(AlertInfo(
                date=datetime.strptime(alert["date"], '%Y-%m-%d').date(),
                generation=alert["generation"],
                risk_level=alert["risk_level"],
                degree_days=alert["degree_days"],
                sub_type=alert["sub_type"],
                extra_data=alert["extra_data"]
            ))

        # Build response
        response = OliveMothRiskResponse(
            parcel_id=request.parcel_id,
            from_date=request.from_date,
            to_date=request.to_date,
            current_risk_level=risk_result["risk_level"],
            total_degree_days=risk_result["accumulated_degree_days"],
            generations=generations,
            daily_calculations=daily_calculations,
            temperature_thresholds=TemperatureThresholds(
                lower_threshold=risk_result["temperature_thresholds"]["lower_threshold"],
                upper_threshold=risk_result["temperature_thresholds"]["upper_threshold"]
            ),
            alerts=alerts,
            alert_counts=AlertCounts(**risk_result["alert_counts"]),
            days_analyzed=len(temperature_data),
            analysis_date=datetime.utcnow(),
            start_of_season=datetime.fromisoformat(risk_result["start_of_season"])
        )

        return response

    except HTTPException:
        raise
    except Exception as e:
        raise HTTPException(
            status_code=status.HTTP_500_INTERNAL_SERVER_ERROR,
            detail=f"Error calculating olive moth risk: {str(e)}"
        )