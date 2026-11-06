from typing import List, Optional, Dict, Any
from datetime import datetime, date
from sqlalchemy.orm import Session
import json

from crud.base import CRUDBase
from models.alert import Alert
from schemas.alert import AlertCreate


class CRUDAlert(CRUDBase[Alert, AlertCreate, AlertCreate]):
    def create_alert(
        self,
        db: Session,
        *,
        parcel_id: int,
        date: date,
        model_name: str,
        sub_type: str,
        risk_level: int,
        degree_days: Optional[float] = None,
        extra_data: Optional[Dict[str, Any]] = None
    ) -> Alert:
        """Create a new alert"""
        extra_data_json = json.dumps(extra_data) if extra_data else None
        
        db_obj = Alert(
            parcel_id=parcel_id,
            date=datetime.combine(date, datetime.min.time()),
            model_name=model_name,
            sub_type=sub_type,
            risk_level=risk_level,
            degree_days=degree_days,
            extra_data=extra_data_json,
            created_at=datetime.utcnow()
        )
        db.add(db_obj)
        db.commit()
        db.refresh(db_obj)
        return db_obj

    def get_alerts_by_parcel(
        self,
        db: Session,
        parcel_id: int,
        from_date: Optional[date] = None,
        to_date: Optional[date] = None,
        model_name: Optional[str] = None
    ) -> List[Alert]:
        """Get alerts for a parcel with optional filters"""
        query = db.query(Alert).filter(Alert.parcel_id == parcel_id)
        
        if from_date:
            query = query.filter(Alert.date >= datetime.combine(from_date, datetime.min.time()))
        
        if to_date:
            query = query.filter(Alert.date <= datetime.combine(to_date, datetime.max.time()))
        
        if model_name:
            query = query.filter(Alert.model_name == model_name)
        
        return query.order_by(Alert.date.desc()).all()

    def get_recent_alerts(
        self,
        db: Session,
        parcel_id: int,
        limit: int = 50
    ) -> List[Alert]:
        """Get recent alerts for a parcel"""
        return db.query(Alert).filter(
            Alert.parcel_id == parcel_id
        ).order_by(Alert.date.desc()).limit(limit).all()

    def delete_alerts_for_parcel_and_date_range(
        self,
        db: Session,
        parcel_id: int,
        from_date: date,
        to_date: date,
        model_name: Optional[str] = None
    ) -> int:
        """Delete existing alerts for a parcel and date range (useful for recalculation)"""
        query = db.query(Alert).filter(
            Alert.parcel_id == parcel_id,
            Alert.date >= datetime.combine(from_date, datetime.min.time()),
            Alert.date <= datetime.combine(to_date, datetime.max.time())
        )
        
        if model_name:
            query = query.filter(Alert.model_name == model_name)
        
        count = query.count()
        query.delete(synchronize_session=False)
        db.commit()
        return count


alert = CRUDAlert(Alert)