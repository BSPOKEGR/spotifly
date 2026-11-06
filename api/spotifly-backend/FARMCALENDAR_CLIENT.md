# Farm Calendar HTTP Client

This HTTP client provides a Laravel service to consume the FarmCalendar API.

## Configuration

### Environment Variables

Add the following variables to your `.env` file:

```env
FARMCALENDAR_API_URL=http://farmcalendar:8000
FARMCALENDAR_API_VERSION=v1
FARMCALENDAR_API_TIMEOUT=30
FARMCALENDAR_API_FORMAT=jsonld

# Optional authentication
FARMCALENDAR_AUTH_TYPE=bearer
FARMCALENDAR_API_TOKEN=your-token-here

# Or for basic auth
# FARMCALENDAR_AUTH_TYPE=basic
# FARMCALENDAR_API_USERNAME=username
# FARMCALENDAR_API_PASSWORD=password

# Retry configuration
FARMCALENDAR_RETRY_TIMES=3
FARMCALENDAR_RETRY_SLEEP=1000
```

## Usage

### Dependency Injection

The `FarmCalendarService` is registered as a singleton in the service container. You can inject it into your controllers, commands, or other services:

```php
<?php

namespace App\Http\Controllers;

use App\Services\FarmCalendarService;
use Illuminate\Http\JsonResponse;

class FarmActivityController extends Controller
{
    public function __construct(
        private readonly FarmCalendarService $farmCalendar
    ) {
    }

    public function index(): JsonResponse
    {
        $operations = $this->farmCalendar->getAddRawMaterialOperations();
        
        return response()->json($operations);
    }
}
```

### Using the Facade Pattern (Alternative)

You can also resolve the service from the container:

```php
use App\Services\FarmCalendarService;

$farmCalendar = app(FarmCalendarService::class);
$operations = $farmCalendar->getAddRawMaterialOperations();
```

## Available Methods

The service provides comprehensive CRUD operations for all FarmCalendar API endpoints:

### Farm Management

```php
// Farms
$farms = $farmCalendar->getFarms(['name' => 'My Farm']);
$farm = $farmCalendar->getFarm($id);
$farm = $farmCalendar->createFarm($data);
$farm = $farmCalendar->updateFarm($id, $data);
$farm = $farmCalendar->patchFarm($id, ['name' => 'Updated Name']);
$farmCalendar->deleteFarm($id);

// Farm Parcels
$parcels = $farmCalendar->getFarmParcels();
$parcel = $farmCalendar->getFarmParcel($id);
$parcel = $farmCalendar->createFarmParcel($data);
$parcel = $farmCalendar->updateFarmParcel($id, $data);
$parcel = $farmCalendar->patchFarmParcel($id, $data);
$farmCalendar->deleteFarmParcel($id);

// Farm Crops
$crops = $farmCalendar->getFarmCrops();
$crop = $farmCalendar->getFarmCrop($id);
$crop = $farmCalendar->createFarmCrop($data);
$crop = $farmCalendar->updateFarmCrop($id, $data);
$crop = $farmCalendar->patchFarmCrop($id, $data);
$farmCalendar->deleteFarmCrop($id);

// Farm Animals
$animals = $farmCalendar->getFarmAnimals();
$animal = $farmCalendar->getFarmAnimal($id);
$animal = $farmCalendar->createFarmAnimal($data);
$animal = $farmCalendar->updateFarmAnimal($id, $data);
$animal = $farmCalendar->patchFarmAnimal($id, $data);
$farmCalendar->deleteFarmAnimal($id);
```

### Farm Assets

```php
// Agricultural Machines
$machines = $farmCalendar->getAgriculturalMachines();
$machine = $farmCalendar->getAgriculturalMachine($id);
$machine = $farmCalendar->createAgriculturalMachine($data);
$machine = $farmCalendar->updateAgriculturalMachine($id, $data);
$machine = $farmCalendar->patchAgriculturalMachine($id, $data);
$farmCalendar->deleteAgriculturalMachine($id);

// Generic Farm Assets
$assets = $farmCalendar->getGenericFarmAssets();
$asset = $farmCalendar->getGenericFarmAsset($id);
$asset = $farmCalendar->createGenericFarmAsset($data);
$asset = $farmCalendar->updateGenericFarmAsset($id, $data);
$asset = $farmCalendar->patchGenericFarmAsset($id, $data);
$farmCalendar->deleteGenericFarmAsset($id);
```

### Farm Calendar Activities

```php
// Farm Calendar Activities
$activities = $farmCalendar->getFarmCalendarActivities(['fromDate' => '2025-01-01']);
$activity = $farmCalendar->getFarmCalendarActivity($id);
$activity = $farmCalendar->createFarmCalendarActivity($data);
$activity = $farmCalendar->updateFarmCalendarActivity($id, $data);
$activity = $farmCalendar->patchFarmCalendarActivity($id, $data);
$farmCalendar->deleteFarmCalendarActivity($id);

// Activity Types
$types = $farmCalendar->getActivityTypes();
$type = $farmCalendar->getActivityType($id);
```

### Alerts

```php
$alerts = $farmCalendar->getAlerts();
$alert = $farmCalendar->getAlert($id);
$alert = $farmCalendar->createAlert($data);
$alert = $farmCalendar->updateAlert($id, $data);
$alert = $farmCalendar->patchAlert($id, $data);
$farmCalendar->deleteAlert($id);
```

### Operations

```php
// Fertilization Operations
$operations = $farmCalendar->getFertilizationOperations();
$operation = $farmCalendar->getFertilizationOperation($id);
$operation = $farmCalendar->createFertilizationOperation($data);
$operation = $farmCalendar->updateFertilizationOperation($id, $data);
$operation = $farmCalendar->patchFertilizationOperation($id, $data);
$farmCalendar->deleteFertilizationOperation($id);

// Irrigation Operations
$operations = $farmCalendar->getIrrigationOperations();
$operation = $farmCalendar->getIrrigationOperation($id);
$operation = $farmCalendar->createIrrigationOperation($data);
$operation = $farmCalendar->updateIrrigationOperation($id, $data);
$operation = $farmCalendar->patchIrrigationOperation($id, $data);
$farmCalendar->deleteIrrigationOperation($id);

// Crop Protection Operations
$operations = $farmCalendar->getCropProtectionOperations();
$operation = $farmCalendar->getCropProtectionOperation($id);
$operation = $farmCalendar->createCropProtectionOperation($data);
$operation = $farmCalendar->updateCropProtectionOperation($id, $data);
$operation = $farmCalendar->patchCropProtectionOperation($id, $data);
$farmCalendar->deleteCropProtectionOperation($id);

// Compost Operations
$operations = $farmCalendar->getCompostOperations();
$operation = $farmCalendar->getCompostOperation($id);
$operation = $farmCalendar->createCompostOperation($data);
$operation = $farmCalendar->updateCompostOperation($id, $data);
$operation = $farmCalendar->patchCompostOperation($id, $data);
$farmCalendar->deleteCompostOperation($id);

// Add Raw Material Operations
$operations = $farmCalendar->getAddRawMaterialOperations();
$operation = $farmCalendar->getAddRawMaterialOperation($id);
$operation = $farmCalendar->createAddRawMaterialOperation($data);
$operation = $farmCalendar->updateAddRawMaterialOperation($id, $data);
$operation = $farmCalendar->patchAddRawMaterialOperation($id, $data);
$farmCalendar->deleteAddRawMaterialOperation($id);

// Compost Turning Operations
$operations = $farmCalendar->getCompostTurningOperations();
$operation = $farmCalendar->getCompostTurningOperation($id);
$operation = $farmCalendar->createCompostTurningOperation($data);
$operation = $farmCalendar->updateCompostTurningOperation($id, $data);
$operation = $farmCalendar->patchCompostTurningOperation($id, $data);
$farmCalendar->deleteCompostTurningOperation($id);
```

### Materials & Inputs

```php
// Fertilizers
$fertilizers = $farmCalendar->getFertilizers();
$fertilizer = $farmCalendar->getFertilizer($id);
$fertilizer = $farmCalendar->createFertilizer($data);
$fertilizer = $farmCalendar->updateFertilizer($id, $data);
$fertilizer = $farmCalendar->patchFertilizer($id, $data);
$farmCalendar->deleteFertilizer($id);

// Pesticides
$pesticides = $farmCalendar->getPesticides();
$pesticide = $farmCalendar->getPesticide($id);
$pesticide = $farmCalendar->createPesticide($data);
$pesticide = $farmCalendar->updatePesticide($id, $data);
$pesticide = $farmCalendar->patchPesticide($id, $data);
$farmCalendar->deletePesticide($id);
```

### Observations

```php
// General Observations
$observations = $farmCalendar->getObservations();
$observation = $farmCalendar->getObservation($id);
$observation = $farmCalendar->createObservation($data);
$observation = $farmCalendar->updateObservation($id, $data);
$observation = $farmCalendar->patchObservation($id, $data);
$farmCalendar->deleteObservation($id);

// Yield Prediction
$predictions = $farmCalendar->getYieldPredictions();
$prediction = $farmCalendar->getYieldPrediction($id);
$prediction = $farmCalendar->createYieldPrediction($data);
$prediction = $farmCalendar->updateYieldPrediction($id, $data);
$prediction = $farmCalendar->patchYieldPrediction($id, $data);
$farmCalendar->deleteYieldPrediction($id);

// Disease Detection
$detections = $farmCalendar->getDiseaseDetections();
$detection = $farmCalendar->getDiseaseDetection($id);
$detection = $farmCalendar->createDiseaseDetection($data);
$detection = $farmCalendar->updateDiseaseDetection($id, $data);
$detection = $farmCalendar->patchDiseaseDetection($id, $data);
$farmCalendar->deleteDiseaseDetection($id);

// Vigor Estimation
$estimations = $farmCalendar->getVigorEstimations();
$estimation = $farmCalendar->getVigorEstimation($id);
$estimation = $farmCalendar->createVigorEstimation($data);
$estimation = $farmCalendar->updateVigorEstimation($id, $data);
$estimation = $farmCalendar->patchVigorEstimation($id, $data);
$farmCalendar->deleteVigorEstimation($id);

// Spraying Recommendation
$recommendations = $farmCalendar->getSprayingRecommendations();
$recommendation = $farmCalendar->getSprayingRecommendation($id);
$recommendation = $farmCalendar->createSprayingRecommendation($data);
$recommendation = $farmCalendar->updateSprayingRecommendation($id, $data);
$recommendation = $farmCalendar->patchSprayingRecommendation($id, $data);
$farmCalendar->deleteSprayingRecommendation($id);

// Crop Stress Indicator Observations
$observations = $farmCalendar->getCropStressIndicatorObservations();
$observation = $farmCalendar->getCropStressIndicatorObservation($id);
$observation = $farmCalendar->createCropStressIndicatorObservation($data);
$observation = $farmCalendar->updateCropStressIndicatorObservation($id, $data);
$observation = $farmCalendar->patchCropStressIndicatorObservation($id, $data);
$farmCalendar->deleteCropStressIndicatorObservation($id);

// Crop Growth Stage Observations
$observations = $farmCalendar->getCropGrowthStageObservations();
$observation = $farmCalendar->getCropGrowthStageObservation($id);
$observation = $farmCalendar->createCropGrowthStageObservation($data);
$observation = $farmCalendar->updateCropGrowthStageObservation($id, $data);
$observation = $farmCalendar->patchCropGrowthStageObservation($id, $data);
$farmCalendar->deleteCropGrowthStageObservation($id);
```

### Nested Compost Operation Resources

```php
// Get sub-resources for a specific Compost Operation
$materials = $farmCalendar->getCompostOperationAddRawMaterials($compostOperationId);
$irrigations = $farmCalendar->getCompostOperationIrrigations($compostOperationId);
$observations = $farmCalendar->getCompostOperationObservations($compostOperationId);
$turnings = $farmCalendar->getCompostOperationTurnings($compostOperationId);
```

### Add Raw Material Operations (Detailed Example)

The following shows a detailed example of working with Add Raw Material Operations:

```php
// Get all Add Raw Material Operations
$operations = $farmCalendar->getAddRawMaterialOperations([
    'activity_type' => 'some-activity-type-id',
    'title' => 'Operation Title'
]);

// Get a specific operation
$operation = $farmCalendar->getAddRawMaterialOperation($id);

// Create a new operation
$newOperation = $farmCalendar->createAddRawMaterialOperation([
    '@type' => 'AddRawMaterialOperation',
    'activityType' => [
        '@type' => 'FarmCalendarActivityType',
        '@id' => 'urn:farmcalendar:FarmCalendarActivityType:00000000-0000-0000-0000-000000000007'
    ],
    'title' => 'Add Raw Material Operation',
    'details' => 'some details',
    'hasStartDatetime' => '2025-02-21T12:05:02Z',
    'hasEndDatetime' => null,
    'responsibleAgent' => null,
    'usesAgriculturalMachinery' => [],
    'hasCompostMaterial' => [
        [
            '@type' => 'CompostMaterial',
            'typeName' => 'Straw',
            'quantityValue' => [
                '@type' => 'QuantityValue',
                'unit' => 'kg',
                'numericValue' => 10
            ]
        ]
    ]
]);

// Update an operation
$updated = $farmCalendar->updateAddRawMaterialOperation($id, $data);

// Partially update an operation
$patched = $farmCalendar->patchAddRawMaterialOperation($id, [
    'title' => 'Updated Title'
]);

// Delete an operation
$farmCalendar->deleteAddRawMaterialOperation($id);
```

### Generic Activity Methods

For other activity types, use the generic methods:

```php
// Get activities of any type
$activities = $farmCalendar->getActivities('SomeActivityType', [
    'title' => 'filter by title'
]);

// Create an activity
$activity = $farmCalendar->createActivity('SomeActivityType', $data);

// Get a specific activity
$activity = $farmCalendar->getActivity('SomeActivityType', $id);

// Update an activity
$updated = $farmCalendar->updateActivity('SomeActivityType', $id, $data);

// Patch an activity
$patched = $farmCalendar->patchActivity('SomeActivityType', $id, $data);

// Delete an activity
$farmCalendar->deleteActivity('SomeActivityType', $id);
```

### Activity Types

```php
// Get all activity types
$types = $farmCalendar->getActivityTypes();

// Get a specific activity type
$type = $farmCalendar->getActivityType($id);
```

### Custom Requests

For endpoints not covered by the specific methods:

```php
// Custom GET request
$data = $farmCalendar->get('SomeEndpoint/', ['param' => 'value']);

// Custom POST request
$result = $farmCalendar->post('SomeEndpoint/', $data);

// Custom PUT request
$result = $farmCalendar->put('SomeEndpoint/', $data);

// Custom PATCH request
$result = $farmCalendar->patch('SomeEndpoint/', $data);

// Custom DELETE request
$farmCalendar->delete('SomeEndpoint/');
```

## Using DTOs

The package includes Data Transfer Objects for type-safe data handling:

```php
use App\Dto\FarmCalendar\AddRawMaterialOperationData;
use App\Dto\FarmCalendar\ActivityTypeData;
use App\Dto\FarmCalendar\CompostMaterialData;
use App\Dto\FarmCalendar\QuantityValueData;

// Parse OCSM response into a DTO
$response = $farmCalendar->getAddRawMaterialOperation($id);
$operationData = AddRawMaterialOperationData::fromOcsm($response['@graph'][0]);

// Access typed properties
echo $operationData->title;
echo $operationData->hasStartDatetime;

// Convert DTO back to OCSM format for API requests
$ocsmData = $operationData->toOcsm();
$farmCalendar->updateAddRawMaterialOperation($id, $ocsmData);
```

## Error Handling

The service will throw exceptions on failed requests. You should handle them appropriately:

```php
use Illuminate\Http\Client\RequestException;

try {
    $operation = $farmCalendar->getAddRawMaterialOperation($id);
} catch (RequestException $e) {
    // Handle the error
    Log::error('Failed to get farm operation', [
        'status' => $e->response->status(),
        'message' => $e->getMessage(),
    ]);
    
    return response()->json([
        'error' => 'Failed to retrieve operation'
    ], 500);
}
```

## Example Controller

Here's a complete example of a controller using the service:

```php
<?php

namespace App\Http\Controllers;

use App\Dto\FarmCalendar\AddRawMaterialOperationData;
use App\Services\FarmCalendarService;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FarmOperationController extends Controller
{
    public function __construct(
        private readonly FarmCalendarService $farmCalendar
    ) {
    }

    public function index(Request $request): JsonResponse
    {
        try {
            $filters = $request->only(['activity_type', 'title']);
            $operations = $this->farmCalendar->getAddRawMaterialOperations($filters);
            
            return response()->json($operations);
        } catch (RequestException $e) {
            return response()->json([
                'error' => 'Failed to retrieve operations'
            ], $e->response->status());
        }
    }

    public function show(string $id): JsonResponse
    {
        try {
            $operation = $this->farmCalendar->getAddRawMaterialOperation($id);
            
            return response()->json($operation);
        } catch (RequestException $e) {
            return response()->json([
                'error' => 'Operation not found'
            ], $e->response->status());
        }
    }

    public function store(Request $request): JsonResponse
    {
        try {
            $operation = $this->farmCalendar->createAddRawMaterialOperation(
                $request->all()
            );
            
            return response()->json($operation, 201);
        } catch (RequestException $e) {
            return response()->json([
                'error' => 'Failed to create operation',
                'details' => $e->response->json()
            ], $e->response->status());
        }
    }

    public function update(Request $request, string $id): JsonResponse
    {
        try {
            $operation = $this->farmCalendar->updateAddRawMaterialOperation(
                $id,
                $request->all()
            );
            
            return response()->json($operation);
        } catch (RequestException $e) {
            return response()->json([
                'error' => 'Failed to update operation'
            ], $e->response->status());
        }
    }

    public function destroy(string $id): JsonResponse
    {
        try {
            $this->farmCalendar->deleteAddRawMaterialOperation($id);
            
            return response()->json(null, 204);
        } catch (RequestException $e) {
            return response()->json([
                'error' => 'Failed to delete operation'
            ], $e->response->status());
        }
    }
}
```

## Testing

You can mock the service in your tests:

```php
use App\Services\FarmCalendarService;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class FarmOperationTest extends TestCase
{
    public function test_can_get_operations(): void
    {
        Http::fake([
            'farmcalendar:8000/api/v1/AddRawMaterialOperations/*' => Http::response([
                '@context' => ['https://w3id.org/ocsm/main-context.jsonld'],
                '@graph' => [
                    [
                        '@type' => 'AddRawMaterialOperation',
                        '@id' => 'urn:farmcalendar:AddRawMaterialOperation:test-id',
                        'title' => 'Test Operation'
                    ]
                ]
            ], 200),
        ]);

        $farmCalendar = app(FarmCalendarService::class);
        $operations = $farmCalendar->getAddRawMaterialOperations();

        $this->assertNotEmpty($operations);
    }
}
```

## API Documentation

The FarmCalendar API includes Swagger/OpenAPI documentation:

- **Swagger UI**: `http://localhost:8002/api/v1/schema/swagger-ui/`
- **ReDoc**: `http://localhost:8002/api/v1/schema/redoc/`
- **OpenAPI Schema**: `http://localhost:8002/api/v1/schema/`

## Quick Reference

### All Available Endpoints

The service provides full CRUD operations (Create, Read, Update, Patch, Delete) for:

**Farm Management:**
- Farms
- Farm Parcels
- Farm Crops
- Farm Animals

**Assets:**
- Agricultural Machines
- Generic Farm Assets

**Calendar:**
- Farm Calendar Activities
- Farm Calendar Activity Types

**Operations:**
- Fertilization Operations
- Irrigation Operations
- Crop Protection Operations
- Compost Operations
- Add Raw Material Operations
- Compost Turning Operations

**Materials:**
- Fertilizers
- Pesticides

**Observations:**
- General Observations
- Yield Predictions
- Disease Detections
- Vigor Estimations
- Spraying Recommendations
- Crop Stress Indicator Observations
- Crop Growth Stage Observations

**Alerts:**
- Alerts

**Nested Resources:**
- Compost Operation Sub-resources (Add Raw Materials, Irrigations, Observations, Turnings)

### Method Naming Convention

All endpoints follow a consistent naming pattern:

- `get{Resource}s()` - Get list with optional filters
- `get{Resource}($id)` - Get single item by ID
- `create{Resource}($data)` - Create new item
- `update{Resource}($id, $data)` - Full update (PUT)
- `patch{Resource}($id, $data)` - Partial update (PATCH)
- `delete{Resource}($id)` - Delete item

### Common Filter Parameters

Most list endpoints support these filter parameters:

- `activity_type` - Filter by activity type UUID
- `title` - Filter by title
- `fromDate` - Filter activities from this date
- `toDate` - Filter activities to this date
- `parcel` - Filter by parcel UUID
- `format` - Response format (Json or JsonLD)

