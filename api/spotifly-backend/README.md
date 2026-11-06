# Spotifly Weather API

Simple API for fetching weather forecasts for agricultural parcels.

## Quick Start

### 1. Login

```bash
POST https://spotifly-api.bspoke.gr/api/auth/login
Content-Type: application/json
```

**Request:**
```json
{
  "username": "jane.smith@company.com",
  "password": "test1234"
}
```

**Response:**
```json
{
  "access_token": "eyJ0eXAiOiJKV1QiLCJhbGc...",
  "token_type": "Bearer",
  "expires_in": 3600
}
```

---

### 2. Get Your Parcels

```bash
GET https://spotifly-api.bspoke.gr/api/parcels
Authorization: Bearer your-access-token
```

**Response:**
```json
{
  "data": [
    {
      "id": "778604d2-c410-4b80-8bfb-de3c2e54a735",
      "name": "North Field",
      "crop": "Wheat",
      "area": 25.5,
      "location": {
        "lat": 40.7128,
        "lng": -74.0060
      }
    }
  ]
}
```

---

### 3. Get Hourly Weather (5 days)

```bash
GET https://spotifly-api.bspoke.gr/api/weather?parcel_id=778604d2-c410-4b80-8bfb-de3c2e54a735
Authorization: Bearer your-access-token
```

**Response:**
```json
{
  "data": {
    "ambient_temperature": {
      "data": {
        "2026-02-09T00:00:00+00:00": 13.0,
        "2026-02-09T01:00:00+00:00": 12.8,
        "2026-02-09T02:00:00+00:00": 12.7
      }
    },
    "ambient_humidity": {
      "data": {
        "2026-02-09T00:00:00+00:00": 78,
        "2026-02-09T01:00:00+00:00": 77,
        "2026-02-09T02:00:00+00:00": 76
      }
    },
    "wind_speed": {
      "data": {
        "2026-02-09T00:00:00+00:00": 5.2,
        "2026-02-09T01:00:00+00:00": 5.5
      }
    },
    "wind_direction": {
      "data": {
        "2026-02-09T00:00:00+00:00": 205,
        "2026-02-09T01:00:00+00:00": 191
      }
    },
    "precipitation": {
      "data": {
        "2026-02-09T00:00:00+00:00": 0,
        "2026-02-09T01:00:00+00:00": 0
      }
    }
  },
  "source": "database",
  "parcel_id": "778604d2-c410-4b80-8bfb-de3c2e54a735",
  "records_count": 120
}
```

---

### 4. Get Daily Weather Summary (5 days)

```bash
GET https://spotifly-api.bspoke.gr/api/weather/daily?parcel_id=778604d2-c410-4b80-8bfb-de3c2e54a735
Authorization: Bearer your-access-token
```

**Response:**
```json
{
  "data": [
    {
      "date": "2026-02-09",
      "temperature": {
        "min": 12.1,
        "max": 15.2,
        "mean": 13.2
      },
      "humidity": {
        "min": 71,
        "max": 96,
        "mean": 85
      },
      "wind_speed": {
        "min": 0.7,
        "max": 9.9,
        "mean": 5.7
      },
      "precipitation": {
        "total": 7.9,
        "max": 1.4
      },
      "icon": "rain",
      "condition": "Rainy"
    },
    {
      "date": "2026-02-10",
      "temperature": {
        "min": 9.1,
        "max": 12.0,
        "mean": 10.3
      },
      "humidity": {
        "min": 78,
        "max": 92,
        "mean": 83
      },
      "wind_speed": {
        "min": 6.1,
        "max": 28.8,
        "mean": 18.4
      },
      "precipitation": {
        "total": 7.9,
        "max": 2.0
      },
      "icon": "rain",
      "condition": "Rainy"
    }
  ],
  "source": "database",
  "parcel_id": "778604d2-c410-4b80-8bfb-de3c2e54a735"
}
```

## Action endpoints
The API endpoints that allow you to perform actions on the farm calendar (e.g., creating, updating, deleting activities) are protected and require authentication. You can authenticate using the OpenAgri Gatekeeper or using the local session-based authentication if the Gatekeeper is not configured. Once authenticated, you can use the API to manage your farm calendar activities, assets, and other related resources. The API follows RESTful principles and uses JSON-LD format for data representation, conforming to the OpenAgri Common Semantic Model (OCSM).
### Actions index (GET `/api/actions`)
The index of the available action endpoints.
#### Parameters
- **parcel_id**: The unique identifier of the parcel for which you want to see the saved actions. This parameter is nullable and can be passed in the URL path.
#### Sample response
```json
{
    "data": [
        {
            "id": "7e305b4c-52ec-4ede-b1ec-bfa1ff73cb60",
            "parcelId": "f01c8143-6d66-457a-b0e4-8a9abf0d3eb0",
            "parcel": "North Field",
            "type": "monitoring",
            "title": "test action",
            "description": "",
            "scheduledDate": "2026-02-10T00:00:00Z",
            "completedDate": null,
            "responsible": "Unassigned",
            "linkedAlerts": []
        }
    ]
}
```
### Show action (GET `/api/actions/{id}`)
Retrieve a single farm action/activity by its ID.
#### Parameters
- **id**: The unique identifier of the action. This parameter is required and should be passed in the URL path.
#### Sample response
```json
{
    "data": {
        "id": "7e305b4c-52ec-4ede-b1ec-bfa1ff73cb60",
        "parcelId": "f01c8143-6d66-457a-b0e4-8a9abf0d3eb0",
        "parcel": "North Field",
        "type": "monitoring",
        "title": "test action",
        "description": "",
        "scheduledDate": "2026-02-10T00:00:00Z",
        "completedDate": null,
        "responsible": "Unassigned",
        "linkedAlerts": []
    }
}
```
### Create action (POST `/api/actions`)
Create a new farm action/activity.
#### Request body
```json
{
    "parcelId": "f01c8143-6d66-457a-b0e4-8a9abf0d3eb0",
    "type": "irrigation",
    "title": "Apply irrigation",
    "description": "Water crops in sector A",
    "scheduledDate": "2025-11-15",
    "responsible": "John Doe"
}
```
#### Parameters
- **parcelId** (required, string): The unique identifier of the parcel where the action will be performed.
- **type** (required, string): The type of action. Allowed values: `spraying`, `irrigation`, `fertilization`, `harvesting`, `monitoring`.
- **title** (required, string): The title of the action (max 255 characters).
- **description** (optional, string): Detailed description of the action.
- **scheduledDate** (required, date): The date when the action is scheduled to be performed (format: YYYY-MM-DD).
- **responsible** (optional, string): Name of the person responsible for the action (max 255 characters).
#### Sample response
```json
{
    "data": {
        "id": "8f416c5d-63fd-5fef-c2fd-cga2gg84dc71",
        "parcelId": "f01c8143-6d66-457a-b0e4-8a9abf0d3eb0",
        "parcel": null,
        "type": "irrigation",
        "title": "Apply irrigation",
        "description": "Water crops in sector A",
        "scheduledDate": "2025-11-15T00:00:00Z",
        "completedDate": null,
        "responsible": "John Doe",
        "linkedAlerts": []
    },
    "message": "Action created successfully"
}
```
### Update action (PUT `/api/actions/{id}`)
Update an existing farm action/activity.
#### Parameters
- **id**: The unique identifier of the action. This parameter is required and should be passed in the URL path.
#### Request body
All fields are optional. Only provide the fields you want to update.
```json
{
    "title": "Updated irrigation task",
    "description": "Updated description",
    "scheduledDate": "2025-11-16",
    "responsible": "Jane Smith",
    "completedDate": "2025-11-14"
}
```
#### Available fields
- **title** (optional, string): The title of the action (max 255 characters).
- **description** (optional, string): Detailed description of the action.
- **scheduledDate** (optional, date): The date when the action is scheduled (format: YYYY-MM-DD).
- **responsible** (optional, string): Name of the person responsible (max 255 characters).
- **completedDate** (optional, date): The date when the action was completed (format: YYYY-MM-DD).
#### Sample response
```json
{
    "data": {
        "id": "7e305b4c-52ec-4ede-b1ec-bfa1ff73cb60",
        "parcelId": "f01c8143-6d66-457a-b0e4-8a9abf0d3eb0",
        "parcel": "North Field",
        "type": "monitoring",
        "title": "Updated irrigation task",
        "description": "Updated description",
        "scheduledDate": "2025-11-16T00:00:00Z",
        "completedDate": "2025-11-14T23:59:59Z",
        "responsible": "Jane Smith",
        "linkedAlerts": []
    },
    "message": "Action updated successfully"
}
```
### Delete action (DELETE `/api/actions/{id}`)
Delete a farm action/activity.
#### Parameters
- **id**: The unique identifier of the action. This parameter is required and should be passed in the URL path.
#### Sample response
```json
{
    "message": "Action deleted successfully"
}
```

## Alert endpoints
The alert endpoints provide access to pest and disease alerts for your parcels. Alerts are generated based on weather conditions and crop data, helping you stay informed about potential threats to your crops.

### Alerts index (GET `/api/alerts`)
Retrieve all pest and disease alerts.

#### Sample request
```bash
GET https://spotifly-api.bspoke.gr/api/alerts
Authorization: Bearer your-access-token
```

#### Sample response
```json
{
    "data": [
        {
            "id": 16,
            "parcel_id": "f01c8143-6d66-457a-b0e4-8a9abf0d3eb0",
            "parcel_name": "North Field",
            "date": "2026-02-15T00:00:00.000000Z",
            "type": "olive_fruit_fly",
            "severity": "CRITICAL",
            "message": "Alert for Olive Fruit Fly: Current risk level is Critical.",
            "read": false
        },
        {
            "id": 15,
            "parcel_id": "f01c8143-6d66-457a-b0e4-8a9abf0d3eb0",
            "parcel_name": "North Field",
            "date": "2026-02-15T00:00:00.000000Z",
            "type": "olive_moth",
            "severity": "CRITICAL",
            "message": "Alert for Olive Moth: Current risk level is Critical.",
            "read": true
        }
    ]
}
```

### Acknowledge alert (POST `/api/alerts/{id}/acknowledge`)
Mark an alert as acknowledged. This indicates that you have seen and noted the alert.

#### Parameters
- **id** (required, string): The unique identifier of the alert. This parameter is required and should be passed in the URL path.

#### Sample request
```bash
POST https://spotifly-api.bspoke.gr/api/alerts/15/acknowledge
Authorization: Bearer your-access-token
```

#### Sample response
```json
{
    "message": "Alert acknowledged successfully"
}
```

### Get Latest Pest Prediction (GET `/api/predictions/pest/latest`)
Retrieve the most recent pest prediction for a specific parcel or across all parcels.

#### Parameters
- **parcel_id** (optional, string): The unique identifier of the parcel. If omitted, returns the latest prediction across all parcels.

#### Sample request
```bash
GET https://spotifly-api.bspoke.gr/api/predictions/pest/latest?parcel_id=ba479653-404e-4817-af53-6e1ee0ea6df6
Authorization: Bearer your-access-token


#### Sample response
```json
{
  "data": {
    "id": 2,
    "parcel_id": "ba479653-404e-4817-af53-6e1ee0ea6df6",
    "response_data": {
      "bactrocera-oleae": 66,
      "prays-olae": 13,
      "received_id": "ba479653-404e-4817-af53-6e1ee0ea6df6"
    },
    "fetched_at": "2026-03-03T13:48:01.000000Z"
  }
}
```
