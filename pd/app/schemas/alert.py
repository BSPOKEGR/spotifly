from typing import Optional, Any, Dict, List
from datetime import datetime, date
from uuid import UUID
from pydantic import BaseModel, Field, ConfigDict


class AlertCreate(BaseModel):
    """Schema for creating an alert"""
    parcel_id: int = Field(..., description="Integer ID of the parcel")
    alert_date: date = Field(..., description="Date of the alert", alias="date")
    model_name: str = Field(..., description="Model name (fruit-fly or moth)")
    sub_type: str = Field(..., description="Alert subtype (generation)")
    risk_level: int = Field(..., description="Risk level (1-3)")
    degree_days: Optional[float] = Field(None, description="Accumulated degree days")
    extra_data: Optional[Dict[str, Any]] = Field(None, description="Additional alert data")
    
    model_config = ConfigDict(populate_by_name=True)


class AlertResponse(BaseModel):
    """Schema for alert response"""
    id: UUID
    parcel_id: int
    date: datetime
    model_name: str
    sub_type: str
    risk_level: int
    degree_days: Optional[float]
    extra_data: Optional[Dict[str, Any]]
    created_at: datetime

    model_config = ConfigDict(from_attributes=True)


class AlertListResponse(BaseModel):
    """Schema for listing alerts"""
    alerts: List[AlertResponse]
    total_count: int