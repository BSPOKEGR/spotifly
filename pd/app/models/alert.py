from sqlalchemy import Column, Integer, String, DateTime, Float, Text, ForeignKey
from sqlalchemy.dialects.postgresql import UUID
from sqlalchemy.orm import relationship
from db.base_class import Base
import uuid


class Alert(Base):
    """Alert model for storing pest-related alerts"""
    __tablename__ = "alerts"

    id = Column(UUID(as_uuid=True), primary_key=True, default=uuid.uuid4, index=True)
    parcel_id = Column(Integer, ForeignKey("parcel.id"), nullable=False, index=True)
    date = Column(DateTime, nullable=False, index=True)
    model_name = Column(String, nullable=False)  # 'fruit-fly' or 'moth'
    sub_type = Column(String, nullable=False)  # 'first_generation', 'second_generation', etc.
    risk_level = Column(Integer, nullable=False)  # 1, 2, 3
    degree_days = Column(Float, nullable=True)
    extra_data = Column(Text, nullable=True)  # JSON string for additional data
    created_at = Column(DateTime, nullable=False)
    
    # Relationship to parcel (assuming parcel model exists)
    # parcel = relationship("Parcel", back_populates="alerts")