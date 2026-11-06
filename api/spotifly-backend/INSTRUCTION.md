## Login:
https://spotifly-api.bspoke.gr/api/auth/login

### Instructions:
Use the login endpoint to get an access token, wich will be used to authenticate every other request.

### Payload:
```
{
  "username": "FarmerJohn@bspoke.gr",
  "password": "your-password"
}
```

### Response
```
{
    "success": true,
    "access_token": "<access_token>",
    "refresh_token": "<refresh_token>",
    "user": {
        "id": "<user-uuid>",
        "username": "FarmerJohn@bspoke.gr",
        "email": "FarmerJohn@bspoke.gr",
        "first_name": "",
        "last_name": "",
        "uuid": "<user-uuid>"
    }
}
```

-----------------------------------------------------------------

## Get Parcels:
https://spotifly-api.bspoke.gr/api/parcels

### Instructions:
Retrieve all user parcels and their ids(uuid) for retrieving the rest data.

### Payload:
None

### Response
```
{
    "data": [
        {
            "id": "ba479653-404e-4817-af53-6e1ee0ea6df6",
            "name": "Olive Grove North",
            "crop": "Olives",
            "area": 15.5,
            "riskLevel": "low",
            "polygon": [
                [
                    38.246639,
                    21.734573
                ],
                [
                    38.247123,
                    21.735892
                ],
                [
                    38.246234,
                    21.736445
                ],
                [
                    38.245789,
                    21.735234
                ],
                [
                    38.246639,
                    21.734573
                ]
            ],
            "location": {
                "lat": 38.246484800000005,
                "lng": 21.7353434
            },
            "ml_prediction": {
              "bactrocera-oleae": 10,
              "prays-olae": 1,
              "received_id": "ba479653-404e-4817-af53-6e1ee0ea6df6",
              "fetched_at": "2026-03-09T07:00:02.000000Z"
            },
            "owner": "Unknown",
            "plantingDate": null,
            "harvestDate": null,
            "pdParcelId": 1,
            "sensors": []
        }
    ]
}
```

-----------------------------------------------------------------

## Get Hourly Weather:
https://spotifly-api.bspoke.gr/api/weather/hourly?parcel_id={{parcel_id}}

### Instructions:
Weather data for parcel, covering for 5 days (hourly): temperature, humidity, wind speed, wind direction, and precipitation. No rain throughout.

### Payload:
None

### Response
```
{
    "data": {
        "ambient_temperature": {
            "data": {
                "2026-03-10T08:00:00+00:00": 14.3,
                "2026-03-10T09:00:00+00:00": 15.2,
            }
        },
        "ambient_humidity": {
            "data": {
                "2026-03-10T08:00:00+00:00": 39,
                "2026-03-10T09:00:00+00:00": 36,
            }
        },
        "wind_speed": {
            "data": {
                "2026-03-10T08:00:00+00:00": 15.5,
                "2026-03-10T09:00:00+00:00": 14.3,
            }
        },
        "wind_direction": {
            "data": {
                "2026-03-10T08:00:00+00:00": 59,
                "2026-03-10T09:00:00+00:00": 56,
            }
        },
        "precipitation": {
            "data": {
                "2026-03-10T08:00:00+00:00": 0,
                "2026-03-10T09:00:00+00:00": 0,
            }
        }
    },
    "source": "database",
    "parcel_id": "ba479653-404e-4817-af53-6e1ee0ea6df6",
    "records_count": 112
}
```

-----------------------------------------------------------------

## Get Daily Weather:
https://spotifly-api.bspoke.gr/api/weather/daily?parcel_id={{parcel_id}}

### Instructions:
Daily weather summary for a parcel, 5 days, with aggregated stats per parameter plus a condition/icon per day.

### Payload:
None

### Response
```
{
    "data": [
        {
            "date": "2026-03-10",
            "temperature": {
                "min": 11.6,
                "max": 16.1,
                "mean": 13.5
            },
            "humidity": {
                "min": 35,
                "max": 66,
                "mean": 46
            },
            "wind_speed": {
                "min": 6,
                "max": 20.6,
                "mean": 12.2
            },
            "precipitation": {
                "total": 0,
                "max": 0
            },
            "icon": "sun",
            "condition": "Clear"
        },
        {
            "date": "2026-03-11",
            "temperature": {
                "min": 10.3,
                "max": 16.5,
                "mean": 13
            },
            "humidity": {
                "min": 32,
                "max": 65,
                "mean": 53
            },
            "wind_speed": {
                "min": 2.9,
                "max": 13.2,
                "mean": 7.2
            },
            "precipitation": {
                "total": 0,
                "max": 0
            },
            "icon": "sun",
            "condition": "Clear"
        },
        {
            "date": "2026-03-12",
            "temperature": {
                "min": 9.5,
                "max": 15.4,
                "mean": 12.4
            },
            "humidity": {
                "min": 45,
                "max": 79,
                "mean": 61
            },
            "wind_speed": {
                "min": 2.4,
                "max": 8,
                "mean": 5.8
            },
            "precipitation": {
                "total": 0,
                "max": 0
            },
            "icon": "sun",
            "condition": "Clear"
        },
        {
            "date": "2026-03-13",
            "temperature": {
                "min": 10.1,
                "max": 17.9,
                "mean": 13.3
            },
            "humidity": {
                "min": 34,
                "max": 76,
                "mean": 59
            },
            "wind_speed": {
                "min": 1.5,
                "max": 13.5,
                "mean": 7.1
            },
            "precipitation": {
                "total": 0,
                "max": 0
            },
            "icon": "sun",
            "condition": "Clear"
        },
        {
            "date": "2026-03-14",
            "temperature": {
                "min": 9.9,
                "max": 17,
                "mean": 12.8
            },
            "humidity": {
                "min": 31,
                "max": 83,
                "mean": 62
            },
            "wind_speed": {
                "min": 3.6,
                "max": 10.7,
                "mean": 6.5
            },
            "precipitation": {
                "total": 0,
                "max": 0
            },
            "icon": "sun",
            "condition": "Clear"
        }
    ],
    "source": "database",
    "parcel_id": "ba479653-404e-4817-af53-6e1ee0ea6df6"
}
```

-----------------------------------------------------------------

## Get Actions List:
https://spotifly-api.bspoke.gr/api/actions?parcel_id={{parcel_id}}

### Instructions:
Get parcel actions list

### Payload:
None

### Response
```
{
    "data": [
        {
            "id": "3df2ac69-66e6-4b6c-b400-4e6f401f116f",
            "parcelId": "",
            "parcel": null,
            "type": "other",
            "title": "THI: 52.01",
            "description": "Temperature Humidiy Index on 02:31:12",
            "scheduledDate": "2026-03-10T00:31:12Z",
            "completedDate": null,
            "responsible": "Unassigned",
            "linkedAlerts": []
        },
        {
            "id": "db71e967-803d-449b-8eaa-b07c11924c46",
            "parcelId": "",
            "parcel": null,
            "type": "other",
            "title": "THI: 55.42",
            "description": "Temperature Humidiy Index on 18:31:12",
            "scheduledDate": "2026-03-09T16:31:12Z",
            "completedDate": null,
            "responsible": "Unassigned",
            "linkedAlerts": []
        },
    ]
}
```

-----------------------------------------------------------------

## Create Actions:
POST https://spotifly-api.bspoke.gr/api/actions?parcel_id={{parcel_id}}

### Instructions:
create parcel actions

### Payload plant_protection:
```
{                                                                            
    "parcelId": "{{parcel_id}}", 
    "type": "plant_protection",
    "title": "Spray for olive fruit fly",
    "scheduledDate": "2026-03-15",
    "description": "Apply pesticide on parcel A",
    "responsible": "John Doe",
    "cultivation_period": "2026",
    "time": "08:00",
    "target_insect": "Bactrocera oleae",
    "active_ingredient": "Spinosad",
    "dosage": "1.5",
    "unit": "L/ha",
    "spray_solution_volume": "300",
    "fuel": "15",
    "materials": "4",
    "labor": "2",
    "other": "3"
  }
```

### Payload observation:
```
    {
    "parcelId": "{{parcel_id}}", 
    "type": "observation",
    "title": "Weekly pest observation",
    "scheduledDate": "2026-03-15",
    "responsible": "Maria Papadaki",
    "cultivation_period": "Spring 2026",
    "time": "09:30",
    "olive_fruit_fly": true,
    "olive_fruit_fly_larvae_observations": "3 larvae found on 10 sampled fruits",
    "olive_fruit_fly_adult_captures": "12 adults in McPhail trap",
    "olive_molth_larvae_observations": "No larvae detected",
    "olive_molth_adult_captures": "2 adults in pheromone trap"
  }
```

-----------------------------------------------------------------

## Update Actions:
PUT https://spotifly-api.bspoke.gr/api/actions/{{action_id}}

### Instructions:
Update parcel actions

### Payload:
```
{
  "title": "rererer irrigation task",
  "description": "Updated description",
  "scheduledDate": "2026-03-16",
  "responsible": "Jane Smith",
  "completedDate": "2026-03-15"
}
```

-----------------------------------------------------------------

## Delete Actions:
DELETE https://spotifly-api.bspoke.gr/api/actions/{{action_id}}

### Instructions:
delete parcel actions

### Payload:
```
{
  "title": "rererer irrigation task",
  "description": "Updated description",
  "scheduledDate": "2026-03-16",
  "responsible": "Jane Smith",
  "completedDate": "2026-03-15"
}
```
-----------------------------------------------------------------

## Get Alerts:
GET https://spotifly-api.bspoke.gr/api/alerts

### Instructions:
Get alerts
### RESPONSE:
```
{
    "data": [
        {
            "id": 8,
            "parcel_id": "ba479653-404e-4817-af53-6e1ee0ea6df6",
            "parcel_name": "Olive Grove North",
            "date": "2026-03-02T00:00:00.000000Z",
            "type": "olive_fruit_fly",
            "severity": "CRITICAL",
            "message": "Alert for Olive Fruit Fly: Current risk level is Critical.",
            "read": false
        }
    ]
}
```
-----------------------------------------------------------------

## Acknowledge Alerts:
GET https://spotifly-api.bspoke.gr/api/alerts/{{alert_id}}/acknowledge

### Instructions:
Acknowledge alerts
### RESPONSE:
```
{
    "data": [
        {
            "id": 8,
            "parcel_id": "ba479653-404e-4817-af53-6e1ee0ea6df6",
            "parcel_name": "Olive Grove North",
            "date": "2026-03-02T00:00:00.000000Z",
            "type": "olive_fruit_fly",
            "severity": "CRITICAL",
            "message": "Alert for Olive Fruit Fly: Current risk level is Critical.",
            "read": false
        }
    ]
}
```
-----------------------------------------------------------------

## Current Pest ML Prediction:
GET https://spotifly-api.bspoke.gr/api/predictions/pest/latest 

### Instructions:
Acknowledge alerts
### RESPONSE:
```
{
    "data": {
        "id": 10,
        "parcel_id": "ba479653-404e-4817-af53-6e1ee0ea6df6",
        "response_data": {
            "bactrocera-oleae": 1,
            "prays-olae": 3,
            "received_id": "ba479653-404e-4817-af53-6e1ee0ea6df6"
        },
        "fetched_at": "2026-03-11T07:00:02.000000Z"
    }
}
```
-----------------------------------------------------------------

## All Pest ML Predictions:
GET https://spotifly-api.bspoke.gr/api/predictions/pest

### Instructions:
Acknowledge alerts
### RESPONSE:
```
{
    "data": [
        {
            "id": 9,
            "parcel_id": "ba479653-404e-4817-af53-6e1ee0ea6df6",
            "response_data": {
                "bactrocera-oleae": 14,
                "prays-olae": 2,
                "received_id": "ba479653-404e-4817-af53-6e1ee0ea6df6"
            },
            "fetched_at": "2026-03-10T07:00:02.000000Z"
        },
        {
            "id": 8,
            "parcel_id": "ba479653-404e-4817-af53-6e1ee0ea6df6",
            "response_data": {
                "bactrocera-oleae": 10,
                "prays-olae": 1,
                "received_id": "ba479653-404e-4817-af53-6e1ee0ea6df6"
            },
            "fetched_at": "2026-03-09T07:00:02.000000Z"
        },
        {
            "id": 7,
            "parcel_id": "ba479653-404e-4817-af53-6e1ee0ea6df6",
            "response_data": {
                "bactrocera-oleae": 2,
                "prays-olae": 5,
                "received_id": "ba479653-404e-4817-af53-6e1ee0ea6df6"
            },
            "fetched_at": "2026-03-08T07:00:01.000000Z"
        },
        {
            "id": 6,
            "parcel_id": "ba479653-404e-4817-af53-6e1ee0ea6df6",
            "response_data": {
                "bactrocera-oleae": 9,
                "prays-olae": 20,
                "received_id": "ba479653-404e-4817-af53-6e1ee0ea6df6"
            },
            "fetched_at": "2026-03-07T07:00:01.000000Z"
        },
        {
            "id": 5,
            "parcel_id": "ba479653-404e-4817-af53-6e1ee0ea6df6",
            "response_data": {
                "bactrocera-oleae": 11,
                "prays-olae": 10,
                "received_id": "ba479653-404e-4817-af53-6e1ee0ea6df6"
            },
            "fetched_at": "2026-03-06T07:00:01.000000Z"
        },
        {
            "id": 4,
            "parcel_id": "ba479653-404e-4817-af53-6e1ee0ea6df6",
            "response_data": {
                "bactrocera-oleae": 19,
                "prays-olae": 9,
                "received_id": "ba479653-404e-4817-af53-6e1ee0ea6df6"
            },
            "fetched_at": "2026-03-05T07:00:01.000000Z"
        },
        {
            "id": 3,
            "parcel_id": "ba479653-2344-4815-af53-6e1ee0ea6df6",
            "response_data": {
                "bactrocera-oleae": 33,
                "prays-olae": 13,
                "received_id": "ba479653-404e-4817-af53-6e1ee0ea6df6"
            },
            "fetched_at": "2026-03-03T13:48:01.000000Z"
        },
        {
            "id": 2,
            "parcel_id": "ba479653-404e-4817-af53-6e1ee0ea6df6",
            "response_data": {
                "bactrocera-oleae": 66,
                "prays-olae": 13,
                "received_id": "ba479653-404e-4817-af53-6e1ee0ea6df6"
            },
            "fetched_at": "2026-03-03T13:48:01.000000Z"
        },
        {
            "id": 1,
            "parcel_id": "ba479653-404e-4817-af53-6e1ee0ea6df6",
            "response_data": {
                "bactrocera-oleae": 13,
                "prays-olae": 20,
                "received_id": "ba479653-404e-4817-af53-6e1ee0ea6df6"
            },
            "fetched_at": "2026-02-03T13:40:10.000000Z"
        }
    ],
    "count": 9
}
```

