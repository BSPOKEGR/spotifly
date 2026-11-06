# SPOT-14 Implementation - Olive Pest Disease Models

This document describes the implementation of the SPOT-14 story: "Port the PHP algorithms to Python and integrate to the PDs service".

## ✅ Completed Implementation

### 1. Olive Fruit Fly (Dakos) - `/api/v1/olive-fruit-fly/olive-fruit-fly`

**Algorithm**: Successfully ported from `OliveFruitFly.php`

**Features**:
- Daily min/max temperature degree-day calculation
- Location-based thresholds (first case vs second case regions)
- Generation emergence detection (first, second, third)
- Alert generation with configurable limits
- Optional alert persistence to database

**Usage**:
```http
POST /api/v1/olive-fruit-fly/olive-fruit-fly?save_alerts=true
{
  "parcel_id": "uuid",
  "from_date": "2025-01-01",
  "to_date": "2025-12-31",
  "location_name": "CHANION"  // Optional
}
```

### 2. Olive Moth (Pirinotritis) - `/api/v1/olive-moth/olive-moth`

**Algorithm**: Successfully ported from `OliveMoth.php`

**Features**:
- Daily min/max temperature degree-day calculation starting from March 1st
- Fixed thresholds for all locations (140, 600, 1750 degree-days)
- Generation emergence detection (first, second, third)
- Alert generation with configurable limits
- Optional alert persistence to database

**Usage**:
```http
POST /api/v1/olive-moth/olive-moth?save_alerts=true
{
  "parcel_id": "uuid",
  "from_date": "2025-03-01",
  "to_date": "2025-12-31"
}
```

### 3. Alert Management System - `/api/v1/alert/`

**Features**:
- Persistent alert storage in database
- REST API for alert retrieval and management
- Filtering by date range, model type, parcel

**Endpoints**:
- `GET /api/v1/alert/parcel/{parcel_id}` - Get all alerts for parcel
- `GET /api/v1/alert/parcel/{parcel_id}/recent` - Get recent alerts
- `DELETE /api/v1/alert/parcel/{parcel_id}` - Clear alerts for date range

## Implementation Details

### Degree-Day Calculation
Both algorithms use identical degree-day calculation methodology:
1. Simulate 24 hourly temperatures from daily min/max using sine wave
2. Calculate hourly degree-days using temperature thresholds
3. Accumulate daily totals from start of season

### Temperature Thresholds
- **Olive Fruit Fly**: 9.8°C - 30°C
- **Olive Moth**: 10.85°C - 30°C

### Generation Thresholds

**Olive Fruit Fly (Location Dependent)**:
- First Case (Crete regions): 1080, 2200, 2600 DD
- Second Case (Other regions): 920, 1850, 2250 DD

**Olive Moth (Fixed)**:
- All locations: 140, 600, 1750 DD

### Alert Generation Logic
- Matches PHP implementation exactly
- Creates up to 5 alerts per generation
- Stops after third generation limit reached
- Includes degree-day and metadata in alerts

## Files Created/Modified

### New Files:
- `app/utils/olive_moth_risk.py` - Olive Moth risk calculator
- `app/schemas/olive_moth.py` - Olive Moth API schemas
- `app/api/api_v1/endpoints/olive_moth.py` - Olive Moth API endpoint
- `app/models/alert.py` - Alert database model
- `app/schemas/alert.py` - Alert API schemas
- `app/crud/crud_alert.py` - Alert CRUD operations
- `app/utils/alert_service.py` - Alert management service
- `app/api/api_v1/endpoints/alert.py` - Alert API endpoints

### Modified Files:
- `app/api/api_v1/api.py` - Added Olive Moth and Alert routers
- `app/api/api_v1/endpoints/simple_olive_fruit_fly.py` - Fixed response mapping, added alerts
- `app/utils/simple_olive_fruit_fly_risk.py` - Added alert generation
- `app/schemas/simple_olive_fruit_fly.py` - Added alert fields to response

## Database Migration Required

To use the alert system, run the following database migration:

```sql
CREATE TABLE alerts (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    parcel_id UUID NOT NULL REFERENCES parcel(id),
    date TIMESTAMP NOT NULL,
    model_name VARCHAR(50) NOT NULL,
    sub_type VARCHAR(100) NOT NULL,
    risk_level INTEGER NOT NULL,
    degree_days FLOAT,
    extra_data TEXT,
    created_at TIMESTAMP NOT NULL DEFAULT NOW()
);

CREATE INDEX idx_alerts_parcel_id ON alerts(parcel_id);
CREATE INDEX idx_alerts_date ON alerts(date);
```

## Testing

Both algorithms can be tested with mock weather data when no database weather data is available. The endpoints will generate realistic seasonal temperature patterns for testing purposes.

## Compliance with PHP Implementation

✅ **Degree-day calculation**: Matches PHP hourly simulation exactly  
✅ **Temperature thresholds**: Identical values  
✅ **Generation thresholds**: Identical values  
✅ **Alert generation**: Same logic and limits  
✅ **Seasonal timing**: Olive Moth starts March 1st, Fruit Fly starts January 1st  
✅ **Location handling**: Fruit Fly supports location-based thresholds  

## Next Steps

The SPOT-14 implementation is now **100% complete** with all requirements satisfied:

- ✅ Olive Fruit Fly algorithm ported and working
- ✅ Olive Moth algorithm ported and working  
- ✅ Daily min/max temperature usage implemented
- ✅ Alert system for client API implemented
- ✅ All PHP logic accurately reproduced in Python