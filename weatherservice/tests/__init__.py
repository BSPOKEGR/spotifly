import os

# Dummy values so `src.core.config` can be imported when running the tests.
os.environ.setdefault('WEATHER_SRV_DATABASE_URI', 'mongodb://localhost:27017/')
os.environ.setdefault('JWT_KEY', 'test-only-key')
