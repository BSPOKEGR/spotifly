import os
import dotenv


dotenv.load_dotenv()

# LOGGING
LOGGING_LEVEL = os.environ.get('LOGGING_LEVEL', 'INFO')

# SERVER
WEATHER_SRV_PORT = os.environ.get('WEATHER_SRV_PORT', '8000')
WEATHER_SRV_HOSTNAME = os.environ.get('WEATHER_SRV_HOSTNAME', 'weathersrv')

# DB
try:
    DATABASE_URI = os.environ['WEATHER_SRV_DATABASE_URI']
except KeyError:
    raise RuntimeError(
        'WEATHER_SRV_DATABASE_URI is not set (e.g. mongodb://USER:PASSWORD@HOST:27017/)'
    ) from None
DATABASE_NAME = os.environ.get('WEATHER_SRV_DATABASE_NAME', 'openagridb')

# Weather providers
OPENWEATHERMAP_API_KEY = os.environ.get('WEATHER_SRV_OPENWEATHERMAP_API_KEY', '')
HISTORY_WEATHER_PROVIDER = os.environ.get('HISTORY_WEATHER_PROVIDER', 'openmeteo')
OM_CACHE_VARIABLES = {
    "daily": [
        "temperature_2m_min",
        "temperature_2m_max",
        "temperature_2m_mean",
        "precipitation_sum",
        "rain_sum",
        "wind_speed_10m_max",
        "wind_gusts_10m_max",
        "wind_direction_10m_dominant"
    ],
    "hourly": [
        "temperature_2m",
        "relative_humidity_2m",
        "precipitation",
        "rain",
        "pressure_msl",
        "surface_pressure",
        "cloud_cover",
        "et0_fao_evapotranspiration",
        "wind_speed_10m",
        "wind_direction_10m",
        "wind_gusts_10m",
        "soil_temperature_0_to_7cm",
        "soil_temperature_7_to_28cm",
        "soil_temperature_28_to_100cm",
        "soil_temperature_100_to_255cm",
        "soil_moisture_0_to_7cm",
        "soil_moisture_7_to_28cm",
        "soil_moisture_28_to_100cm",
        "soil_moisture_100_to_255cm"
    ]
}

# ALLOWED_HOSTS
EXTRA_ALLOWED_HOSTS = os.environ.get('EXTRA_ALLOWED_HOSTS', "*").replace(' ', '').split(',')

# GATEKEEPER
GATEKEEPER_URL = os.environ.get('GATEKEEPER_URL', '').rstrip('/')
WEATHER_SRV_GATEKEEPER_USER = os.environ.get('WEATHER_SRV_GATEKEEPER_USER', '')
WEATHER_SRV_GATEKEEPER_PASSWORD = os.environ.get('WEATHER_SRV_GATEKEEPER_PASSWORD', '')

# APP
CURRENT_WEATHER_DATA_CACHE_TIME = os.environ.get('CURRENT_WEATHER_DATA_CACHE_TIME', 1)
LOCATION_RADIUS_METERS = int(os.environ.get('LOCATION_RADIUS_METERS', 10000))

# FARM CALENDAR
PUSH_THI_TO_FARMCALENDAR=os.environ.get('PUSH_THI_TO_FARMCALENDAR', '')
PUSH_FLIGHT_FORECAST_TO_FARMCALENDAR=os.environ.get('PUSH_FLIGHT_FORECAST_TO_FARMCALENDAR', '')
PUSH_SPRAY_F_TO_FARMCALENDAR=os.environ.get('PUSH_SPRAY_F_TO_FARMCALENDAR', '')
GATEKEEPER_FARM_CALENDAR_API = os.environ.get('GATEKEEPER_FARM_CALENDAR_API', 'http://farmcalendar:8002/api/v1/').rstrip('/')

# TASKS
INTERVAL_THI_TO_FARMCALENDAR = os.environ.get('INTERVAL_HOURS_THI_TO_FARMCALENDAR', 8)

# JWT
ACCESS_TOKEN_EXPIRE_MINUTES = int(os.environ.get('ACCESS_TOKEN_EXPIRE_MINUTES', '240'))
try:
    KEY = os.environ['JWT_KEY']
except KeyError:
    raise RuntimeError('JWT_KEY is not set (use the same value as the GateKeeper JWT signing key)') from None
ALGORITHM = os.environ.get('ALGORITHM', 'HS256')
CRYPT_CONTEXT_SCHEME = os.environ.get('CRYPT_CONTEXT_SCHEME', 'bcrypt')
