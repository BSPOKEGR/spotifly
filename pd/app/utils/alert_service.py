from typing import List, Dict, Any, Optional
from datetime import date
from sqlalchemy.orm import Session

from crud.crud_alert import alert as alert_crud
from models.alert import Alert


class AlertService:
    """Service for managing pest-related alerts"""
    
    @staticmethod
    def create_alerts_from_risk_calculation(
        db: Session,
        parcel_id: int,
        model_name: str,
        alerts_data: List[Dict[str, Any]],
        clear_existing: bool = True,
        from_date: Optional[date] = None,
        to_date: Optional[date] = None
    ) -> List[Alert]:
        """
        Create alerts from risk calculation results
        
        Args:
            db: Database session
            parcel_id: Integer ID of the parcel
            model_name: Model name ('fruit-fly' or 'moth')
            alerts_data: List of alert data from risk calculator
            clear_existing: Whether to clear existing alerts for the date range
            from_date: Start date for clearing existing alerts
            to_date: End date for clearing existing alerts
        
        Returns:
            List of created Alert objects
        """
        created_alerts = []
        
        # Clear existing alerts if requested
        if clear_existing and from_date and to_date:
            alert_crud.delete_alerts_for_parcel_and_date_range(
                db=db,
                parcel_id=parcel_id,
                from_date=from_date,
                to_date=to_date,
                model_name=model_name
            )
        
        # Create new alerts
        for alert_data in alerts_data:
            alert_date = date.fromisoformat(alert_data["date"]) if isinstance(alert_data["date"], str) else alert_data["date"]
            
            alert_obj = alert_crud.create_alert(
                db=db,
                parcel_id=parcel_id,
                date=alert_date,
                model_name=model_name,
                sub_type=alert_data["sub_type"],
                risk_level=alert_data["risk_level"],
                degree_days=alert_data.get("degree_days"),
                extra_data=alert_data.get("extra_data", {})
            )
            created_alerts.append(alert_obj)
        
        return created_alerts
    
    @staticmethod
    def get_alerts_for_parcel(
        db: Session,
        parcel_id: int,
        from_date: Optional[date] = None,
        to_date: Optional[date] = None,
        model_name: Optional[str] = None
    ) -> List[Alert]:
        """Get alerts for a parcel with optional filters"""
        return alert_crud.get_alerts_by_parcel(
            db=db,
            parcel_id=parcel_id,
            from_date=from_date,
            to_date=to_date,
            model_name=model_name
        )
    
    @staticmethod
    def format_alerts_for_api(alerts: List[Alert]) -> List[Dict[str, Any]]:
        """Format alerts for API response"""
        formatted_alerts = []
        
        for alert in alerts:
            import json
            extra_data = {}
            if alert.extra_data:
                try:
                    extra_data = json.loads(alert.extra_data)
                except json.JSONDecodeError:
                    extra_data = {}
            
            formatted_alerts.append({
                "id": str(alert.id),
                "parcel_id": alert.parcel_id,
                "date": alert.date.date(),
                "model_name": alert.model_name,
                "sub_type": alert.sub_type,
                "risk_level": alert.risk_level,
                "degree_days": alert.degree_days,
                "extra_data": extra_data,
                "created_at": alert.created_at
            })
        
        return formatted_alerts