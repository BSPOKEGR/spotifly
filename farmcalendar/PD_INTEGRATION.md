# FarmCalendar - PD Service Integration

This document describes the integration between the FarmCalendar service and the Pest & Disease (PD) service for parcel synchronization.

## Overview

When parcels are created, updated, or deleted in FarmCalendar, they are automatically synchronized with the PD service. This integration handles the ID mapping between FarmCalendar's UUID-based parcels and PD's integer-based parcels.

## Architecture

### ID Mapping

- **FarmCalendar**: Uses UUID for parcel IDs (e.g., `a1b2c3d4-e5f6-7890-abcd-ef1234567890`)
- **PD Service**: Uses integer for parcel IDs (e.g., `1`, `2`, `3`)
- **Solution**: FarmCalendar stores the PD parcel ID in a new field `pd_parcel_id`

### Database Schema Change

A new field has been added to the `FarmParcel` model:

```python
pd_parcel_id = models.IntegerField(
    blank=True, 
    null=True, 
    help_text="Parcel ID in the Pest & Disease service"
)
```

## Components

### 1. Model Update
**File**: `farmcalendar/farm_management/models/farm_parcels.py`

Added `pd_parcel_id` field to store the integer ID from PD service.

### 2. PD Service Client
**File**: `farmcalendar/farm_calendar/utils/pd_service.py`

Provides functions to interact with the PD service API:
- `sync_parcel_to_pd(farm_parcel)` - Creates a parcel in PD and returns the integer ID
- `delete_parcel_from_pd(pd_parcel_id)` - Deletes a parcel from PD
- `PDServiceClient` - Low-level API client for PD service

### 3. ViewSet Hooks
**File**: `farmcalendar/apis/views/farm_parcels.py`

The `FarmParcelViewSet` has been enhanced with:

- `perform_create()` - Syncs new parcels to PD after creation
- `perform_update()` - Syncs parcels that don't have a PD ID yet
- `perform_destroy()` - Deletes parcels from PD when deleted from FarmCalendar

### 4. Serializer Update
**File**: `farmcalendar/apis/serializers/farm_parcels.py`

Added `pdParcelId` field (read-only) to the FarmParcelSerializer to expose the PD parcel ID in API responses.

### 5. Management Command
**File**: `farmcalendar/farm_management/management/commands/sync_parcels_to_pd.py`

Django management command to sync existing parcels:

```bash
# Sync all parcels that don't have a PD ID
python manage.py sync_parcels_to_pd

# Force re-sync all parcels
python manage.py sync_parcels_to_pd --force

# Sync a specific parcel
python manage.py sync_parcels_to_pd --parcel-id <uuid>
```

### 6. Database Migration
**File**: `farmcalendar/farm_management/migrations/0008_add_pd_parcel_id.py`

Migration to add the `pd_parcel_id` field to the database.

## Configuration

Set the PD service URL in your environment variables:

```bash
export PD_SERVICE_URL="http://localhost:8003"
```

Default: `http://localhost:8003`

## Setup Instructions

### 1. Apply Database Migration

```bash
cd /home/theokir/projects/bspoke/spotifly/farmcalendar
python manage.py migrate
```

### 2. Sync Existing Parcels

If you have existing parcels that need to be synced to PD:

```bash
python manage.py sync_parcels_to_pd
```

### 3. Test the Integration

Create a new parcel via the API and verify:

```bash
# Create a parcel
curl -X POST http://localhost:8002/api/v1/farm-parcels/ \
  -H "Authorization: Bearer <token>" \
  -H "Content-Type: application/json" \
  -d '{
    "identifier": "TEST-001",
    "farm": "urn:ngsi-ld:Farm:...",
    "latitude": 37.9838,
    "longitude": 23.7275,
    "parcel_type": "olive_grove"
  }'

# Check the response - it should include pdParcelId
```

## Behavior

### On Parcel Creation
1. FarmParcel is created in FarmCalendar database
2. System calls PD API to create a parcel with the same coordinates
3. PD returns an integer ID
4. The integer ID is stored in `pd_parcel_id` field
5. If PD sync fails, the parcel is still created in FarmCalendar (logged as warning)

### On Parcel Update
1. FarmParcel is updated in FarmCalendar database
2. If the parcel doesn't have a `pd_parcel_id` yet, it tries to create it in PD
3. Note: PD service currently doesn't have an update endpoint, so existing PD parcels are not updated

### On Parcel Deletion
1. FarmParcel is deleted from FarmCalendar database
2. If `pd_parcel_id` exists, system calls PD API to delete the parcel
3. If PD deletion fails, the parcel is still deleted from FarmCalendar (logged as warning)

## Error Handling

- All PD sync operations are wrapped in try-catch blocks
- Failures in PD sync do not prevent FarmCalendar operations from completing
- All errors are logged for debugging
- The system is resilient to PD service being unavailable

## Logging

Logs are written for:
- Successful syncs: `INFO` level
- Failed syncs: `ERROR` level  
- Skipped operations: `WARNING` level

Check Django logs for PD sync activity:

```python
logger = logging.getLogger(__name__)
```

## API Response Example

When you fetch a parcel from FarmCalendar API:

```json
{
  "@type": "Parcel",
  "@id": "urn:ngsi-ld:FarmParcel:a1b2c3d4-...",
  "identifier": "PARCEL-001",
  "latitude": 37.9838,
  "longitude": 23.7275,
  "pdParcelId": 42,
  ...
}
```

The `pdParcelId` field shows the corresponding integer ID in the PD service.

## Limitations

1. **No Update Sync**: PD service doesn't have a parcel update endpoint, so changes to existing parcels are not synced
2. **Coordinates Required**: Parcels without latitude/longitude cannot be synced to PD
3. **No Automatic Retry**: If PD sync fails, it's not automatically retried (can be manually retried via management command)
4. **ID Retrieval**: After creating a parcel in PD, we need to fetch all parcels to get the ID (PD API returns a message, not the created object)

## Future Enhancements

1. Add update endpoint to PD service and sync updates
2. Implement async task queue for more reliable sync operations
3. Add retry mechanism with exponential backoff
4. Implement bidirectional sync (PD → FarmCalendar)
5. Add webhook support for real-time updates
