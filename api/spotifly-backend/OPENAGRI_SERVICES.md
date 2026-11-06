# OpenAgri Services Integration

This document provides information on how to use the OpenAgri service HTTP clients integrated into Spotifly Backend.

## Available Services

- **Farm Calendar** - Farm management, activities, operations, observations
- **Weather Service** - Weather forecasts, THI, UAV flight conditions, spray forecasts
- **Pest & Disease Management** - Pest/disease models, risk calculations, GDD calculations

---

## Weather Service Client

### Configuration

Add to your `.env` file:

```env
WEATHERSERVICE_API_URL=http://weatherservice-app-1:8010
WEATHERSERVICE_API_TIMEOUT=30
WEATHERSERVICE_AUTH_TYPE=bearer
WEATHERSERVICE_API_TOKEN=your-token-here
WEATHERSERVICE_RETRY_TIMES=3
WEATHERSERVICE_RETRY_SLEEP=1000
```

### Usage

```php
use App\Services\WeatherService;

class WeatherController extends Controller
{
    public function __construct(
        private readonly WeatherService $weatherService
    ) {}

    public function getForecast(Request $request)
    {
        $lat = $request->input('lat');
        $lon = $request->input('lon');
        
        // Get 5-day weather forecast
        $forecast = $this->weatherService->getForecastHourly5Days($lat, $lon);
        
        return response()->json($forecast);
    }
}
```

### Available Methods

#### Weather Forecasts

```php
// Get 5-day forecast (standard JSON)
$forecast = $weatherService->getForecast5Days(37.9838, 23.7275);

// Get 5-day forecast (JSON-LD format)
$forecast = $weatherService->getForecast5DaysLinkedData(37.9838, 23.7275);

// Get current weather
$weather = $weatherService->getCurrentWeather(37.9838, 23.7275);
```

#### Temperature-Humidity Index

```php
// Get THI (standard JSON)
$thi = $weatherService->getTemperatureHumidityIndex(37.9838, 23.7275);

// Get THI (JSON-LD format)
$thi = $weatherService->getTemperatureHumidityIndexLinkedData(37.9838, 23.7275);
```

#### UAV Flight Forecasts

```php
// Get flight forecast for all UAV models
$flightForecast = $weatherService->getFlightForecast5Days(37.9838, 23.7275);

// Filter by UAV models and status
$flightForecast = $weatherService->getFlightForecast5Days(
    lat: 37.9838,
    lon: 23.7275,
    uavModels: ['DJI_Phantom_4', 'DJI_Mavic_3'],
    statusFilter: 'optimal'
);

// Get flight forecast (JSON-LD format)
$flightForecast = $weatherService->getFlightForecast5DaysLinkedData(37.9838, 23.7275);
```

#### Spray Forecasts

```php
// Get spray condition forecast (standard JSON)
$sprayForecast = $weatherService->getSprayForecast(37.9838, 23.7275);

// Get spray forecast (JSON-LD format)
$sprayForecast = $weatherService->getSprayForecastLinkedData(37.9838, 23.7275);
```

---

## Pest & Disease Management Client

### Configuration

Add to your `.env` file:

```env
PESTDISEASE_API_URL=http://pd-pdm-1:8003
PESTDISEASE_API_VERSION=v1
PESTDISEASE_API_TIMEOUT=30
PESTDISEASE_API_USERNAME=your-username
PESTDISEASE_API_PASSWORD=your-password
PESTDISEASE_RETRY_TIMES=3
PESTDISEASE_RETRY_SLEEP=1000
```

### Authentication

The Pest & Disease service requires authentication. Login before making requests:

```php
use App\Services\PestDiseaseService;

$pestService = app(PestDiseaseService::class);

// Login (uses credentials from config)
$authResponse = $pestService->login();

// Or login with custom credentials
$authResponse = $pestService->login('username', 'password');

// The access token is automatically stored for subsequent requests
```

### Usage Example

```php
use App\Services\PestDiseaseService;

class PestManagementController extends Controller
{
    public function __construct(
        private readonly PestDiseaseService $pestService
    ) {}

    public function calculateRisk(Request $request)
    {
        // Authenticate first
        if (!$this->pestService->isAuthenticated()) {
            $this->pestService->login();
        }
        
        $parcelId = $request->input('parcel_id');
        $modelIds = $request->input('model_ids'); // array of UUIDs
        
        // Calculate risk index (all risk levels)
        $riskData = $this->pestService->calculateRiskIndexVerbose($parcelId, $modelIds);
        
        return response()->json($riskData);
    }
}
```

### Available Methods

#### Parcel Management

```php
// Create parcel with coordinates
$parcel = $pestService->createParcel('My Parcel', 37.9838, 23.7275);

// Create parcel with WKT polygon
$parcel = $pestService->createParcelWkt(
    'My Parcel',
    'POLYGON ((25.2 16.2, 16.2 16.15, 17.2 15.2, 20.1 20.1, 25.2 16.2))'
);

// Get all parcels
$parcels = $pestService->getParcels();

// Get specific parcel
$parcel = $pestService->getParcel($parcelId);

// Delete parcel
$pestService->deleteParcel($parcelId);
```

#### Disease Model Management

```php
// Create disease model
$disease = $pestService->createDisease([
    'name' => 'Colorado potato beetle',
    'eppo_code' => 'LPTNDE',
    'base_gdd' => 0,
    'description' => 'Major pest of potato crops',
    'gdd_points' => [
        ['start' => 0, 'end' => 120, 'description' => 'Not susceptible'],
        ['start' => 120, 'end' => 185, 'description' => 'Apply Btt'],
    ]
]);

// Get all diseases
$diseases = $pestService->getDiseases();

// Get specific disease
$disease = $pestService->getDisease($diseaseId);

// Delete disease
$pestService->deleteDisease($diseaseId);
```

#### Pest Model Management

```php
// Create pest model
$pestModel = $pestService->createPestModel([
    'name' => 'Phytophthora sojae',
    'description' => 'Oomycete pathogen of soybean',
    'geo_areas_of_application' => 'POLYGON ((25.2 16.2, 16.2 16.15, 17.2 15.2, 20.1 20.1, 25.2 16.2))',
    'cultivations' => ['soybean', 'bluebonnet', 'lupinus albus']
]);

// Get all pest models
$models = $pestService->getPestModels();

// Get specific pest model
$model = $pestService->getPestModel($modelId);

// Delete pest model
$pestService->deletePestModel($modelId);
```

#### Rule Management

```php
// Create rule for pest model
$rule = $pestService->createRule([
    'pest_model_id' => $modelId,
    'condition' => 'temperature > 20 AND humidity > 70',
    'risk_level' => 'High'
]);

// Get all rules
$rules = $pestService->getRules();

// Delete rule
$pestService->deleteRule($ruleId);
```

#### Data Upload

```php
// Upload weather data CSV for a parcel
$uploadResponse = $pestService->uploadWeatherData(
    filePath: '/path/to/weather_data.csv',
    parcelId: $parcelId
);
```

**CSV Format Requirements:**
- Must contain `date` (YYYY-MM-DD) and `time` (HH:MM:SS) columns
- At least one weather parameter column:
  - `atmospheric_temperature`
  - `atmospheric_relative_humidity`
  - `precipitation`
  - `wind_speed`, `wind_direction`
  - `soil_temperature_10cm`, `soil_temperature_20cm`, etc.

#### Growing Degree Days (GDD) Calculation

```php
// Calculate GDD for pest models
$gddData = $pestService->calculateGDD(
    parcelId: $parcelId,
    modelIds: ['uuid-1', 'uuid-2'],
    fromDate: '2025-01-15',
    toDate: '2025-01-20'
);

// Response includes GDD values and descriptors for each date
```

#### Risk Index Calculation

```php
// Calculate risk index (all risk levels) - returns JSON-LD
$riskData = $pestService->calculateRiskIndexVerbose($parcelId, [$modelId1, $modelId2]);

// Calculate risk index (high risk only) - returns JSON-LD
$highRiskData = $pestService->calculateRiskIndexHigh($parcelId, [$modelId1, $modelId2]);
```

**Response Format:** JSON-LD with OCSM ontology
- Returns `@graph` array with pest models
- Each model contains `hasPredictedInfestationRisks` array
- Risk levels: `Low`, `Medium`, `High`
- Includes timestamps for each prediction

---

## Error Handling

All services throw exceptions on failed requests. Handle them appropriately:

```php
use Illuminate\Http\Client\RequestException;

try {
    $forecast = $weatherService->getForecast5Days($lat, $lon);
} catch (RequestException $e) {
    Log::error('Weather Service Error', [
        'status' => $e->response->status(),
        'message' => $e->getMessage(),
    ]);
    
    return response()->json([
        'error' => 'Failed to retrieve weather forecast'
    ], 500);
}
```

---

## Complete Example: Pest Risk Workflow

```php
use App\Services\PestDiseaseService;
use App\Services\WeatherService;

class PestRiskController extends Controller
{
    public function __construct(
        private readonly PestDiseaseService $pestService,
        private readonly WeatherService $weatherService
    ) {}

    public function analyzePestRisk(Request $request)
    {
        try {
            // 1. Authenticate
            if (!$this->pestService->isAuthenticated()) {
                $this->pestService->login();
            }

            // 2. Create or get parcel
            $parcel = $this->pestService->createParcel(
                name: 'Field A',
                latitude: 37.9838,
                longitude: 23.7275
            );

            // 3. Get weather data
            $weather = $this->weatherService->getCurrentWeather(37.9838, 23.7275);

            // 4. Create pest model
            $pestModel = $this->pestService->createPestModel([
                'name' => 'Downy Mildew',
                'description' => 'Plasmopara viticola',
                'geo_areas_of_application' => 'POLYGON ((...))',
                'cultivations' => ['grape']
            ]);

            // 5. Calculate risk index
            $riskData = $this->pestService->calculateRiskIndexVerbose(
                parcelId: $parcel['id'],
                modelIds: [$pestModel['id']]
            );

            return response()->json([
                'parcel' => $parcel,
                'weather' => $weather,
                'pest_model' => $pestModel,
                'risk_analysis' => $riskData
            ]);

        } catch (RequestException $e) {
            return response()->json([
                'error' => 'Failed to analyze pest risk',
                'details' => $e->getMessage()
            ], $e->response->status());
        }
    }
}
```

---

## API Documentation Links

- **Farm Calendar**: http://localhost:8002/api/v1/schema/swagger-ui/
- **Weather Service**: http://localhost:8010/docs
- **Pest & Disease**: http://localhost:8003/docs

## Service Container Access

All services are registered as singletons and can be accessed via:

```php
// Dependency injection (recommended)
public function __construct(
    private readonly WeatherService $weatherService,
    private readonly PestDiseaseService $pestService,
    private readonly FarmCalendarService $farmCalendar
) {}

// Or resolve from container
$weatherService = app(WeatherService::class);
$pestService = app(PestDiseaseService::class);
$farmCalendar = app(FarmCalendarService::class);
```
