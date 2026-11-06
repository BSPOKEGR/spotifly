from typing import Any, Optional, List
from datetime import date
from fastapi import APIRouter, Depends, HTTPException, status, Query
from sqlalchemy.orm import Session

from api import deps
from utils.alert_service import AlertService
from schemas.alert import AlertListResponse, AlertResponse

router = APIRouter()


@router.get("/parcel/{parcel_id}", response_model=AlertListResponse)
def get_alerts_for_parcel(
    parcel_id: int,
    from_date: Optional[date] = Query(None, description="Start date for filtering alerts"),
    to_date: Optional[date] = Query(None, description="End date for filtering alerts"),
    model_name: Optional[str] = Query(None, description="Filter by model name (fruit-fly or moth)"),
    db: Session = Depends(deps.get_db)
) -> Any:
    """
    Get alerts for a specific parcel.
    
    This endpoint returns all alerts generated for a parcel, optionally filtered by:
    - Date range (from_date to to_date)
    - Model name (fruit-fly or moth)
    
    Used by client applications to retrieve pest infestation alerts.
    """
    try:
        alerts = AlertService.get_alerts_for_parcel(
            db=db,
            parcel_id=parcel_id,
            from_date=from_date,
            to_date=to_date,
            model_name=model_name
        )
        
        formatted_alerts = AlertService.format_alerts_for_api(alerts)
        
        return AlertListResponse(
            alerts=[AlertResponse(**alert) for alert in formatted_alerts],
            total_count=len(formatted_alerts)
        )
        
    except Exception as e:
        raise HTTPException(
            status_code=status.HTTP_500_INTERNAL_SERVER_ERROR,
            detail=f"Error retrieving alerts: {str(e)}"
        )


@router.get("/parcel/{parcel_id}/recent", response_model=AlertListResponse)
def get_recent_alerts_for_parcel(
    parcel_id: int,
    limit: int = Query(50, description="Maximum number of alerts to return", ge=1, le=100),
    db: Session = Depends(deps.get_db)
) -> Any:
    """
    Get recent alerts for a specific parcel.
    
    Returns the most recent alerts for a parcel, ordered by date descending.
    Useful for showing the latest alert status on dashboards.
    """
    try:
        from crud.crud_alert import alert as alert_crud
        
        alerts = alert_crud.get_recent_alerts(
            db=db,
            parcel_id=parcel_id,
            limit=limit
        )
        
        formatted_alerts = AlertService.format_alerts_for_api(alerts)
        
        return AlertListResponse(
            alerts=[AlertResponse(**alert) for alert in formatted_alerts],
            total_count=len(formatted_alerts)
        )
        
    except Exception as e:
        raise HTTPException(
            status_code=status.HTTP_500_INTERNAL_SERVER_ERROR,
            detail=f"Error retrieving recent alerts: {str(e)}"
        )


@router.delete("/parcel/{parcel_id}")
def clear_alerts_for_parcel(
    parcel_id: int,
    from_date: date = Query(..., description="Start date for clearing alerts"),
    to_date: date = Query(..., description="End date for clearing alerts"),
    model_name: Optional[str] = Query(None, description="Model name to clear (fruit-fly or moth)"),
    db: Session = Depends(deps.get_db)
) -> Any:
    """
    Clear alerts for a parcel within a date range.
    
    Useful for:
    - Recalculating alerts for a specific period
    - Cleaning up old or incorrect alerts
    - Maintenance operations
    """
    try:
        from crud.crud_alert import alert as alert_crud
        
        deleted_count = alert_crud.delete_alerts_for_parcel_and_date_range(
            db=db,
            parcel_id=parcel_id,
            from_date=from_date,
            to_date=to_date,
            model_name=model_name
        )
        
        return {
            "message": f"Successfully cleared {deleted_count} alerts",
            "parcel_id": parcel_id,
            "from_date": from_date,
            "to_date": to_date,
            "model_name": model_name,
            "deleted_count": deleted_count
        }
        
    except Exception as e:
        raise HTTPException(
            status_code=status.HTTP_500_INTERNAL_SERVER_ERROR,
            detail=f"Error clearing alerts: {str(e)}"
        )