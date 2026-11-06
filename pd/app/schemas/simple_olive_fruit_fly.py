from typing import List, Optional
from datetime import datetime, date
from pydantic import BaseModel, Field


class OliveFruitFlyRiskRequest(BaseModel):
    """Request schema for olive fruit fly risk calculation"""
    parcel_id: int = Field(..., description="Integer ID of the parcel to analyze")
    lat: float = Field(..., description="Lat")
    lon: float = Field(..., description="Lon")
    from_date: date = Field(..., description="Start date for analysis")
    to_date: date = Field(..., description="End date for analysis")
    location_name: Optional[str] = Field(None, description="Location name for threshold selection")


class DailyCalculation(BaseModel):
    """Daily calculation details"""
    date: date
    min_temp: float
    max_temp: float
    daily_dd: float
    cumulative_dd: float


class GenerationStatus(BaseModel):
    """Generation emergence status"""
    generation: str
    threshold: float
    emergence_date: Optional[date]
    risk_level: int


class TemperatureThresholds(BaseModel):
    """Temperature thresholds used for calculation"""
    lower_threshold: float
    upper_threshold: float
    thresholds_set: str  # "first_case" or "second_case"


class AlertInfo(BaseModel):
    """Alert information for a specific date and generation"""
    date: date
    generation: str
    risk_level: int
    degree_days: float
    sub_type: str
    extra_data: dict


class AlertCounts(BaseModel):
    """Count of alerts created for each generation"""
    first_generation: int
    second_generation: int
    third_generation: int


class OliveFruitFlyRiskResponse(BaseModel):
    """Response schema for olive fruit fly risk calculation"""
    parcel_id: int
    from_date: date
    to_date: date
    location_name: Optional[str]

    # Risk assessment
    current_risk_level: str
    total_degree_days: float

    # Generation status
    generations: List[GenerationStatus]

    # Calculation details
    daily_calculations: List[DailyCalculation]
    temperature_thresholds: TemperatureThresholds

    # Alert information (optional - only if alerts were generated)
    alerts: Optional[List[AlertInfo]] = None
    alert_counts: Optional[AlertCounts] = None

    # Summary
    days_analyzed: int
    analysis_date: datetime
