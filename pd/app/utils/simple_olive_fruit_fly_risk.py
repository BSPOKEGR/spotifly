import math
from typing import Dict, List, Tuple, Optional
from datetime import datetime, timedelta
from enum import Enum


class RiskLevel(str, Enum):
    LOW = "LOW"
    MEDIUM = "MEDIUM"
    HIGH = "HIGH"
    CRITICAL = "CRITICAL"


class OliveFruitFlyRiskCalculator:
    """
    Simplified Olive Fruit Fly risk calculator that calculates risk index on-the-fly
    based on the original PHP implementation logic.
    """

    # Temperature thresholds from PHP code
    TEMPERATURE_LOWER_THRESHOLD = 9.8  # °C
    TEMPERATURE_UPPER_THRESHOLD = 30.0  # °C

    # Location-based thresholds (degree-days)
    FIRST_CASE_THRESHOLDS = {
        'first_generation': 1080,
        'second_generation': 2200,
        'third_generation': 2600,
    }

    SECOND_CASE_THRESHOLDS = {
        'first_generation': 920,
        'second_generation': 1850,
        'third_generation': 2250,
    }

    # Greek regions that use first case thresholds
    FIRST_CASE_REGIONS = [
        'CHANION', 'RETHYMNOU', 'IRAKLIOU', 'LASITHIOU'
    ]

    # Number of alert days to create for each generation (from PHP)
    CREATE_ALERTS_FOR_NEXT_DAYS = 5

    @staticmethod
    def simulate_hourly_temperatures(min_temp: float, max_temp: float) -> List[float]:
        """Simulate 24 hourly temperatures from daily min/max"""
        hourly_temps = []
        temp_range = (max_temp - min_temp) / 2.0
        temp_avg = (min_temp + max_temp) / 2.0

        for hour in range(24):
            # Sine wave approximation (min at 6 AM, max at 2 PM)
            angle = (hour - 6) * math.pi / 12.0
            if hour <= 14:
                temp = temp_avg + temp_range * math.sin(angle)
            else:
                angle = (hour - 14) * math.pi / 12.0
                temp = temp_avg - temp_range * math.sin(angle)

            temp = max(min_temp, min(max_temp, temp))
            hourly_temps.append(temp)

        return hourly_temps

    @staticmethod
    def calculate_daily_degree_days(min_temp: float, max_temp: float) -> float:
        """Calculate degree-days for a single day using hourly simulation"""
        max_temp = min(max_temp, OliveFruitFlyRiskCalculator.TEMPERATURE_UPPER_THRESHOLD)

        daily_dd = ((min_temp + max_temp) / 2) - OliveFruitFlyRiskCalculator.TEMPERATURE_LOWER_THRESHOLD

        # we don want to have negative degree-days, so we set it to 0 if it's negative
        return max(daily_dd, 0)

    @staticmethod
    def get_thresholds_for_location(location_name: str = None) -> Dict[str, int]:
        """Get degree-day thresholds based on location"""
        if (location_name and
            location_name.upper() in OliveFruitFlyRiskCalculator.FIRST_CASE_REGIONS):
            return OliveFruitFlyRiskCalculator.FIRST_CASE_THRESHOLDS
        else:
            return OliveFruitFlyRiskCalculator.SECOND_CASE_THRESHOLDS

    @staticmethod
    def determine_risk_level(accumulated_dd: float, thresholds: Dict[str, int]) -> str:
        """Determine risk level based on accumulated degree-days"""
        if accumulated_dd >= thresholds['third_generation']:
            return RiskLevel.CRITICAL.value
        elif accumulated_dd >= thresholds['second_generation']:
            return RiskLevel.HIGH.value
        elif accumulated_dd >= thresholds['first_generation']:
            return RiskLevel.MEDIUM.value
        else:
            return RiskLevel.LOW.value

    @staticmethod
    def calculate_risk_index(
        temperature_data: List[Tuple[datetime, float, float]],
        location_name: str = None,
        start_of_season: datetime = None
    ) -> Dict:
        """
        Calculate olive fruit fly risk index from temperature data

        Args:
            temperature_data: List of (date, min_temp, max_temp) tuples
            location_name: Location name for threshold selection
            start_of_season: Start date for degree-day accumulation (defaults to Jan 1st)

        Returns:
            Dictionary with risk assessment results
        """
        if not temperature_data:
            return {
                'risk_level': RiskLevel.LOW.value,
                'accumulated_degree_days': 0.0,
                'daily_degree_days': [],
                'location_type': 'second_case',
                'thresholds': OliveFruitFlyRiskCalculator.SECOND_CASE_THRESHOLDS,
                'generation_status': {
                    'first_generation_reached': False,
                    'second_generation_reached': False,
                    'third_generation_reached': False
                }
            }

        # Set start of season to January 1st if not provided
        if start_of_season is None:
            first_data_year = temperature_data[0][0].year
            start_of_season = datetime(first_data_year, 1, 1)

        # Get thresholds for location
        thresholds = OliveFruitFlyRiskCalculator.get_thresholds_for_location(location_name)
        location_type = "first_case" if (
            location_name and location_name.upper() in OliveFruitFlyRiskCalculator.FIRST_CASE_REGIONS
        ) else "second_case"

        # Calculate accumulated degree-days
        accumulated_dd = 0.0
        daily_calculations = []
        alerts = []

        # Sort temperature data by date
        sorted_data = sorted(temperature_data, key=lambda x: x[0])

        # Track alert counts for each generation (like PHP implementation)
        alert_counts = {
            'first_generation': 0,
            'second_generation': 0,
            'third_generation': 0
        }

        for date_obj, min_temp, max_temp in sorted_data:
            # Calculate degree-days for this day
            daily_dd = OliveFruitFlyRiskCalculator.calculate_daily_degree_days(min_temp, max_temp)
            accumulated_dd += daily_dd

            daily_calculations.append({
                'date': date_obj.strftime('%Y-%m-%d'),
                'min_temperature': min_temp,
                'max_temperature': max_temp,
                'daily_degree_days': daily_dd,
                'accumulated_degree_days': accumulated_dd
            })

            # Check for generation thresholds and create alerts (matching PHP logic)
            current_generation = None
            risk_level_num = 0

            if (accumulated_dd >= thresholds['first_generation'] and 
                accumulated_dd < thresholds['second_generation']):
                current_generation = 'first_generation'
                risk_level_num = 1
            elif (accumulated_dd >= thresholds['second_generation'] and 
                  accumulated_dd < thresholds['third_generation']):
                current_generation = 'second_generation'
                risk_level_num = 2
            elif accumulated_dd >= thresholds['third_generation']:
                current_generation = 'third_generation'
                risk_level_num = 3

            # Create alerts if we're in a generation phase and haven't exceeded limit
            if (current_generation and 
                alert_counts[current_generation] < OliveFruitFlyRiskCalculator.CREATE_ALERTS_FOR_NEXT_DAYS):
                
                alerts.append({
                    'date': date_obj.strftime('%Y-%m-%d'),
                    'generation': current_generation,
                    'risk_level': risk_level_num,
                    'degree_days': accumulated_dd,
                    'sub_type': current_generation,
                    'extra_data': {'dd': accumulated_dd}
                })
                alert_counts[current_generation] += 1

            # Stop creating third generation alerts after limit
            if (current_generation == 'third_generation' and 
                alert_counts[current_generation] >= OliveFruitFlyRiskCalculator.CREATE_ALERTS_FOR_NEXT_DAYS):
                break

        # Determine risk level
        risk_level = OliveFruitFlyRiskCalculator.determine_risk_level(accumulated_dd, thresholds)

        # Check generation status
        generation_status = {
            'first_generation_reached': accumulated_dd >= thresholds['first_generation'],
            'second_generation_reached': accumulated_dd >= thresholds['second_generation'],
            'third_generation_reached': accumulated_dd >= thresholds['third_generation']
        }

        return {
            'risk_level': risk_level,
            'accumulated_degree_days': accumulated_dd,
            'daily_degree_days': daily_calculations,
            'location_type': location_type,
            'location_name': location_name,
            'thresholds': thresholds,
            'generation_status': generation_status,
            'start_of_season': start_of_season.isoformat(),
            'total_days_processed': len(sorted_data),
            'temperature_thresholds': {
                'lower_threshold': OliveFruitFlyRiskCalculator.TEMPERATURE_LOWER_THRESHOLD,
                'upper_threshold': OliveFruitFlyRiskCalculator.TEMPERATURE_UPPER_THRESHOLD
            },
            'alerts': alerts,
            'alert_counts': alert_counts
        }
