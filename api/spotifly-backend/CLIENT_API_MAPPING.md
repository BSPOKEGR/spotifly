# Spotifly Backend API Mapping

This document maps the Vue.js client requirements to Laravel API endpoints that proxy external services.

## Client Architecture

- **Vue 3** with TypeScript
- **Pinia** stores for state management
- **Vue Router** for navigation
- **Sanctum** authentication expected from backend

## Client Routes

- `/login` - Login page
- `/signup` - Sign up page
- `/dashboard/parcels` - Parcels list with map
- `/dashboard/parcels/:id` - Parcel detail with tabs (overview, weather, alerts, actions)
- `/dashboard/actions` - Actions management page

## Client Stores & Required Endpoints

### 1. Auth Store (`useAuthStore`)

**State:**
- `user: User | null`
- `isAuthenticated: boolean`

**Actions:**
- `login(email: string, password: string)`
- `signup(name: string, email: string, password: string)`
- `logout()`
- `loadUser()`

**Required Laravel Endpoints:**
```
POST   /api/auth/register      - Create new user account
POST   /api/auth/login         - Login and get Sanctum token
POST   /api/auth/logout        - Logout (invalidate token)
GET    /api/auth/user          - Get current authenticated user
```

**Data Flow:**
- Direct Laravel authentication (not proxied)
- Uses Laravel Sanctum for token management
- Returns User type: `{ id, name, email, avatar, role }`

---

### 2. Parcel Store (`useParcelStore`)

**State:**
- `parcels: Parcel[]`
- `selectedParcel: Parcel | null`

**Actions:**
- `fetchParcels()` - Get all parcels for authenticated user
- `selectParcel(id: string)` - Set active parcel

**Required Laravel Endpoints:**
```
GET    /api/parcels            - List all parcels
GET    /api/parcels/{id}       - Get parcel details
POST   /api/parcels            - Create new parcel
PUT    /api/parcels/{id}       - Update parcel
DELETE /api/parcels/{id}       - Delete parcel
```

**Client Type Definition:**
```typescript
interface Parcel {
  id: string;
  name: string;
  crop: string;
  area: number;
  riskLevel: 'low' | 'medium' | 'high';
  polygon: [number, number][]; // [[lat, lng], ...]
  location: { lat: number; lng: number };
  owner: string;
  plantingDate: string;
  harvestDate: string;
  sensors: Sensor[];
}

interface Sensor {
  id: string;
  type: 'camera' | 'weather' | 'insect_trap' | 'soil';
  location: { lat: number; lng: number };
  status: 'active' | 'maintenance' | 'offline';
  lastReading: string;
}
```

**External Service Mapping:**
- **FarmCalendar API** - Parcels endpoint (`/parcels/`)
  - Maps to: Plot model (contains crop, area, location, polygon geometry)
  - `GET /api/v1/plots/` - List plots
  - `POST /api/v1/plots/` - Create plot
  - `GET /api/v1/plots/{id}/` - Get plot details
  - `PUT /api/v1/plots/{id}/` - Update plot
  - `DELETE /api/v1/plots/{id}/` - Delete plot

**Transformation Required:**
- FarmCalendar Plot → Client Parcel
- Extract geometry from GeoJSON `geometry.coordinates`
- Calculate risk level from Pest & Disease risk index
- Map FarmCalendar crop to client crop string
- Sensors data may need to be stored separately or mocked initially

---

### 3. Action Store (`useActionStore`)

**State:**
- `actions: Action[]`

**Actions:**
- `fetchActions(parcelId?: string)` - Get actions, optionally filtered by parcel
- `createAction(action: Partial<Action>)` - Create new action
- `updateAction(id: string, updates: Partial<Action>)` - Update action
- `deleteAction(id: string)` - Delete action
- `completeAction(id: string)` - Mark action as completed
- `filterByStatus(status: string)` - Client-side filtering

**Required Laravel Endpoints:**
```
GET    /api/actions                    - List all actions
GET    /api/actions?parcel_id={id}     - List actions by parcel
POST   /api/actions                    - Create new action
PUT    /api/actions/{id}               - Update action
DELETE /api/actions/{id}               - Delete action
PATCH  /api/actions/{id}/complete      - Mark action as completed
```

**Client Type Definition:**
```typescript
interface Action {
  id: string;
  parcelId: string;
  type: 'spraying' | 'irrigation' | 'fertilization' | 'harvesting' | 'monitoring';
  title: string;
  description: string;
  scheduledDate: string;
  completedDate?: string;
  responsible: string;
  status: 'scheduled' | 'in_progress' | 'completed' | 'cancelled';
  linkedAlerts: string[]; // Alert IDs
  priority: 'low' | 'medium' | 'high';
  estimatedDuration: number; // hours
  actualDuration?: number;
}
```

**External Service Mapping:**
- **FarmCalendar API** - Activities/Operations
  - Multiple activity types available (PlantingActivity, HarvestingActivity, IrrigationOperation, etc.)
  - Generic endpoint: `/api/v1/activities/`
  - Specific endpoints: `/api/v1/planting-activities/`, `/api/v1/harvesting-activities/`, etc.
  - Each has: date, plot reference, description, responsible person

**Transformation Required:**
- FarmCalendar Activity → Client Action
- Map activity type to client action type
- Convert ISO dates to client format
- Priority may need calculation or default value
- linkedAlerts needs separate tracking (stored in spotifly-backend DB)

---

### 4. Alert Store (`useAlertStore`)

**State:**
- `alerts: Alert[]`

**Actions:**
- `fetchAlerts(parcelId?: string)` - Get alerts, optionally filtered
- `acknowledgeAlert(id: string)` - Mark alert as acknowledged
- `dismissAlert(id: string)` - Dismiss alert
- `filterBySeverity(severity: string)` - Client-side filtering

**Required Laravel Endpoints:**
```
GET    /api/alerts                     - List all alerts
GET    /api/alerts?parcel_id={id}      - List alerts by parcel
PATCH  /api/alerts/{id}/acknowledge    - Acknowledge alert
DELETE /api/alerts/{id}                - Dismiss alert
```

**Client Type Definition:**
```typescript
interface Alert {
  id: string;
  parcelId: string;
  type: 'pest_outbreak' | 'disease_detected' | 'weather_warning' | 'harvest_ready' | 'sensor_offline';
  severity: 'low' | 'medium' | 'high' | 'critical';
  title: string;
  description: string;
  date: string;
  status: 'active' | 'acknowledged' | 'resolved';
  actionRequired: boolean;
}
```

**External Service Mapping:**
- **Pest & Disease API** - Risk calculations
  - `/api/v1/parcels/{parcel_id}/diseases/{disease_id}/risk_index/` - Disease risk
  - `/api/v1/parcels/{parcel_id}/pests/{pest_id}/risk_index/` - Pest risk
  - Returns: `{ risk_index: number, message: string }`
  
- **Weather Service API** - Weather warnings
  - `/api/v1/forecast_5days/{lat}/{lon}/` - 5-day forecast
  - Parse forecast for extreme conditions (heavy rain, frost, high wind)

**Transformation Required:**
- Generate alerts from multiple sources:
  - High risk_index from P&D → `pest_outbreak` or `disease_detected` alert
  - Extreme weather conditions → `weather_warning` alert
  - Harvest date approaching + crop maturity → `harvest_ready` alert
  - Sensor last_reading > 24h → `sensor_offline` alert
- Severity mapping: risk_index 0-30=low, 31-60=medium, 61-85=high, 86-100=critical
- Alerts stored in spotifly-backend database with generated IDs

---

### 5. Data Store (`useDataStore`)

**State:**
- `weatherData: WeatherData | null`
- `insectData: { [parcelId: string]: InsectData[] }`
- `kpiData: { [parcelId: string]: KPI[] }`

**Actions:**
- `fetchWeatherData(lat: number, lon: number)` - Get weather forecast
- `fetchInsectData(parcelId: string)` - Get pest/insect data for parcel
- `fetchKPIs(parcelId: string)` - Get KPI metrics for parcel

**Required Laravel Endpoints:**
```
GET    /api/weather?lat={lat}&lon={lon}        - Weather forecast
GET    /api/parcels/{id}/insects               - Insect/pest data
GET    /api/parcels/{id}/kpis                  - KPI metrics
```

**Client Type Definitions:**
```typescript
interface WeatherData {
  temperature: {
    current: number;
    min: number;
    max: number;
  };
  humidity: number;
  windSpeed: number;
  precipitation: number;
  chillingHours: number;
  gdd: number; // Growing Degree Days
  forecast: WeatherForecast[];
}

interface WeatherForecast {
  date: string;
  temperature: { min: number; max: number };
  condition: string;
  icon: string;
  precipitation: number; // percentage
}

interface InsectData {
  species: string;
  count: number;
  date: string;
  severity: 'low' | 'medium' | 'high';
  trend: 'increasing' | 'decreasing' | 'stable';
}

interface KPI {
  parcelId: string;
  date: string;
  insectCount: number;
  riskScore: number;
  yieldPrediction: number;
  moistureLevel: number;
  pestPressure: number;
  diseaseRisk: number;
}
```

**External Service Mapping:**

**Weather Data:**
- **Weather Service API**
  - `GET /api/v1/forecast_5days/{lat}/{lon}/` - 5-day forecast
  - `GET /api/v1/temperature_humidity_index/{lat}/{lon}/` - THI (current conditions)
  - Returns: forecast array with temp, humidity, precipitation, wind

**Insect Data:**
- **Pest & Disease API**
  - `GET /api/v1/parcels/{parcel_id}/pests/` - List pests for parcel
  - Returns: List of pest models with population data
  - Calculate trend from historical data or current risk_index

**KPI Data:**
- **Aggregated from multiple services:**
  - `insectCount` - Sum from P&D pests endpoint
  - `riskScore` - Average of disease/pest risk indices from P&D
  - `yieldPrediction` - Could use FarmCalendar crop data + GDD from weather
  - `moistureLevel` - From weather humidity or soil sensors (if available)
  - `pestPressure` - From P&D pest risk_index
  - `diseaseRisk` - From P&D disease risk_index

**Transformation Required:**
- Weather Service forecast → WeatherData format
- Map weather condition codes to human-readable strings and icons
- Calculate GDD from temperature data
- Pest & Disease models → InsectData array
- Aggregate multiple service responses → KPI object

---

## API Endpoints Summary

### Authentication (Direct Laravel)
- `POST /api/auth/register`
- `POST /api/auth/login`
- `POST /api/auth/logout`
- `GET /api/auth/user`

### Parcels (Proxy to FarmCalendar)
- `GET /api/parcels`
- `GET /api/parcels/{id}`
- `POST /api/parcels`
- `PUT /api/parcels/{id}`
- `DELETE /api/parcels/{id}`

### Actions (Proxy to FarmCalendar Activities)
- `GET /api/actions`
- `GET /api/actions?parcel_id={id}`
- `POST /api/actions`
- `PUT /api/actions/{id}`
- `DELETE /api/actions/{id}`
- `PATCH /api/actions/{id}/complete`

### Alerts (Generated from P&D + Weather)
- `GET /api/alerts`
- `GET /api/alerts?parcel_id={id}`
- `PATCH /api/alerts/{id}/acknowledge`
- `DELETE /api/alerts/{id}`

### Weather (Proxy to Weather Service)
- `GET /api/weather?lat={lat}&lon={lon}`

### Insects/Pests (Proxy to P&D Service)
- `GET /api/parcels/{id}/insects`

### KPIs (Aggregated from all services)
- `GET /api/parcels/{id}/kpis`

---

## Data Storage Strategy

### Laravel Database (spotifly-backend)
- **users** - User accounts (Laravel default)
- **parcels** (optional cache) - Cache FarmCalendar plots with additional metadata
- **alerts** - Generated alerts with status tracking
- **action_alert** - Many-to-many relationship for linkedAlerts
- **sensors** (optional) - Sensor metadata if not available from external services

### External Services (Read-Only Proxy)
- FarmCalendar - Source of truth for parcels, crops, activities
- Weather Service - Real-time weather data
- Pest & Disease - Pest/disease models and risk calculations

---

## Implementation Priority

1. **Authentication endpoints** - Required first for any API access
2. **Parcels endpoints** - Core entity, needed for navigation
3. **Weather endpoints** - Simple proxy, independent
4. **Insects/Pests endpoints** - Simple proxy to P&D
5. **Actions endpoints** - Requires parcel context
6. **Alerts generation** - Complex aggregation logic
7. **KPIs endpoints** - Complex aggregation from all sources

---

## Response Format

All endpoints should return JSON with consistent structure:

**Success:**
```json
{
  "data": { ... },
  "message": "Optional success message"
}
```

**List responses:**
```json
{
  "data": [...],
  "meta": {
    "total": 100,
    "page": 1,
    "per_page": 15
  }
}
```

**Error:**
```json
{
  "error": "Error message",
  "details": { ... }
}
```

---

## Authentication Flow

1. Client calls `POST /api/auth/login` with credentials
2. Laravel validates and returns Sanctum token
3. Client stores token in localStorage
4. All subsequent requests include `Authorization: Bearer {token}` header
5. Laravel middleware validates token before proxying to external services
6. External service calls use service-to-service auth (bearer/basic/username-password)

---

## Error Handling

- External service errors should be caught and transformed to client-friendly messages
- 404 on external service → 404 to client
- 401/403 on external service → 500 to client (service auth should not fail)
- Network errors → 503 Service Unavailable
- Validation errors → 422 Unprocessable Entity
