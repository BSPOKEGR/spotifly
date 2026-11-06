<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class FarmCalendarService
{
    private string $baseUrl;
    private string $apiVersion;
    private int $timeout;
    private string $format;
    private array $retryConfig;
    private ?string $jwtToken = null;
    private ?string $userToken = null;

    public function __construct()
    {
        $this->baseUrl = rtrim(config('farmcalendar.api_url'), '/');
        $this->apiVersion = config('farmcalendar.api_version');
        $this->timeout = config('farmcalendar.timeout');
        $this->format = 'json'; //config('farmcalendar.format');
        $this->retryConfig = config('farmcalendar.retry');
    }

    /**
     * Set the user's JWT token to use for API calls.
     */
    public function setUserToken(?string $token): self
    {
        $this->userToken = $token;
        return $this;
    }

    /**
     * Get JWT token from Gatekeeper (cached for 1 hour).
     */
    private function getJwtToken(): ?string
    {
        // If user token is set, use it
        if ($this->userToken) {
            return $this->userToken;
        }

        if ($this->jwtToken) {
            Log::debug('Using cached instance JWT token');
            return $this->jwtToken;
        }

        // Try to get from cache first
        $cachedToken = Cache::get('farmcalendar_jwt_token');
        if ($cachedToken) {
            Log::debug('Using cached JWT token from store');
            $this->jwtToken = $cachedToken;
            return $cachedToken;
        }

        // Get new token from Gatekeeper
        $gatekeeperUrl = config('gatekeeper.api_url');
        $username = config('gatekeeper.username');
        $password = config('gatekeeper.password');

        Log::debug('Getting new JWT token from Gatekeeper', [
            'url' => $gatekeeperUrl,
            'username' => $username,
        ]);

        if (!$gatekeeperUrl || !$username || !$password) {
            Log::error('Gatekeeper credentials not configured', [
                'url' => $gatekeeperUrl,
                'username' => $username,
                'has_password' => !empty($password),
            ]);
            return null;
        }

        try {
            $response = Http::timeout(30)->post("{$gatekeeperUrl}/api/login/", [
                'username' => $username,
                'password' => $password,
            ]);

            Log::debug('Gatekeeper response', [
                'status' => $response->status(),
                'successful' => $response->successful(),
            ]);

            if ($response->successful() && $response->json('success')) {
                $token = $response->json('access');
                // Cache for 50 minutes (expires in 60)
                Cache::put('farmcalendar_jwt_token', $token, now()->addMinutes(50));
                $this->jwtToken = $token;
                Log::info('Successfully retrieved and cached JWT token from Gatekeeper');
                return $token;
            }

            Log::error('Failed to get JWT token from Gatekeeper', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
        } catch (\Exception $e) {
            Log::error('Error getting JWT token from Gatekeeper', [
                'error' => $e->getMessage(),
            ]);
        }

        return null;
    }

    /**
     * Create a configured HTTP client instance.
     */
    private function client(): PendingRequest
    {
        $client = Http::baseUrl($this->baseUrl)
            ->timeout($this->timeout)
            ->acceptJson()
            ->retry(
                $this->retryConfig['times'],
                $this->retryConfig['sleep'],
                function ($exception, $request) {
                    return $exception instanceof \Illuminate\Http\Client\RequestException
                        && $exception->response->status() >= 500;
                }
            )
            ->withHeaders([
                'Accept' => $this->format ==='jsonld'? 'application/ld+json' : 'application/json',
            ]);

        // Try to get JWT token from Gatekeeper
        $token = $this->getJwtToken();
        if ($token) {
            $client->withToken($token);
        } elseif (config('farmcalendar.auth.token')) {
            // Fall back to configured static token
            $client->withToken(config('farmcalendar.auth.token'));
        }

        return $client;
    }

    /**
     * Build the API endpoint URL.
     */
    private function endpoint(string $path): string
    {
        return "/api/{$this->apiVersion}/" . ltrim($path, '/');
    }

    /**
     * Add format parameter to query if needed.
     */
    private function withFormat(array $params = []): array
    {
        if ($this->format) {
            $params['format'] = $this->format;
        }
        return $params;
    }

    /**
     * Handle API response and log errors.
     * @throws RequestException
     */
    private function handleResponse(Response $response, string $operation): array
    {
        if ($response->successful()) {
            return $response->json();
        }

        Log::error("FarmCalendar API Error - {$operation}", [
            'status' => $response->status(),
            'body' => $response->body(),
        ]);

        $response->throw();
        return [];
    }

    // ====================================================================
    // Farm Management
    // ====================================================================

    /**
     * Get list of Farms.
     */
    public function getFarms(array $filters = []): array
    {
        return $this->getActivities('Farm', $filters);
    }

    public function getFarm(string $id, array $params = []): array
    {
        return $this->getActivity('Farm', $id, $params);
    }

    public function createFarm(array $data): array
    {
        return $this->createActivity('Farm', $data);
    }

    public function updateFarm(string $id, array $data): array
    {
        return $this->updateActivity('Farm', $id, $data);
    }

    public function patchFarm(string $id, array $data): array
    {
        return $this->patchActivity('Farm', $id, $data);
    }

    public function deleteFarm(string $id): bool
    {
        return $this->deleteActivity('Farm', $id);
    }

    // ====================================================================
    // Farm Parcels
    // ====================================================================
    public function getFarmParcelsMap(array $filters = []): array
    {
        $map = [];
        foreach ($this->getActivities('FarmParcels', $filters)??[] as $parcel) {
            $map[$parcel['@id']] = $parcel['identifier'];
            $map[str_replace('FarmParcel','Parcel',$parcel['@id'])] = $parcel['identifier'];
        }
        return $map;
    }
    public function getFarmParcels(array $filters = []): array
    {
        return $this->getActivities('FarmParcels', $filters);
    }

    public function getFarmParcel(string $id, array $params = []): array
    {
        return $this->getActivity('FarmParcels', $id, $params);
    }

    public function createFarmParcel(array $data): array
    {
        return $this->createActivity('FarmParcels', $data);
    }

    public function updateFarmParcel(string $id, array $data): array
    {
        return $this->updateActivity('FarmParcels', $id, $data);
    }

    public function patchFarmParcel(string $id, array $data): array
    {
        return $this->patchActivity('FarmParcels', $id, $data);
    }

    public function deleteFarmParcel(string $id): bool
    {
        return $this->deleteActivity('FarmParcels', $id);
    }

    // ====================================================================
    // Farm Crops
    // ====================================================================

    public function getFarmCrops(array $filters = []): array
    {
        return $this->getActivities('FarmCrops', $filters);
    }

    public function getFarmCrop(string $id, array $params = []): array
    {
        return $this->getActivity('FarmCrops', $id, $params);
    }

    public function createFarmCrop(array $data): array
    {
        return $this->createActivity('FarmCrops', $data);
    }

    public function updateFarmCrop(string $id, array $data): array
    {
        return $this->updateActivity('FarmCrops', $id, $data);
    }

    public function patchFarmCrop(string $id, array $data): array
    {
        return $this->patchActivity('FarmCrops', $id, $data);
    }

    public function deleteFarmCrop(string $id): bool
    {
        return $this->deleteActivity('FarmCrops', $id);
    }

    // ====================================================================
    // Farm Animals
    // ====================================================================

    public function getFarmAnimals(array $filters = []): array
    {
        return $this->getActivities('FarmAnimals', $filters);
    }

    public function getFarmAnimal(string $id, array $params = []): array
    {
        return $this->getActivity('FarmAnimals', $id, $params);
    }

    public function createFarmAnimal(array $data): array
    {
        return $this->createActivity('FarmAnimals', $data);
    }

    public function updateFarmAnimal(string $id, array $data): array
    {
        return $this->updateActivity('FarmAnimals', $id, $data);
    }

    public function patchFarmAnimal(string $id, array $data): array
    {
        return $this->patchActivity('FarmAnimals', $id, $data);
    }

    public function deleteFarmAnimal(string $id): bool
    {
        return $this->deleteActivity('FarmAnimals', $id);
    }

    // ====================================================================
    // Agricultural Machines
    // ====================================================================

    public function getAgriculturalMachines(array $filters = []): array
    {
        return $this->getActivities('AgriculturalMachines', $filters);
    }

    public function getAgriculturalMachine(string $id, array $params = []): array
    {
        return $this->getActivity('AgriculturalMachines', $id, $params);
    }

    public function createAgriculturalMachine(array $data): array
    {
        return $this->createActivity('AgriculturalMachines', $data);
    }

    public function updateAgriculturalMachine(string $id, array $data): array
    {
        return $this->updateActivity('AgriculturalMachines', $id, $data);
    }

    public function patchAgriculturalMachine(string $id, array $data): array
    {
        return $this->patchActivity('AgriculturalMachines', $id, $data);
    }

    public function deleteAgriculturalMachine(string $id): bool
    {
        return $this->deleteActivity('AgriculturalMachines', $id);
    }

    // ====================================================================
    // Generic Farm Assets
    // ====================================================================

    public function getGenericFarmAssets(array $filters = []): array
    {
        return $this->getActivities('GenericFarmAssets', $filters);
    }

    public function getGenericFarmAsset(string $id, array $params = []): array
    {
        return $this->getActivity('GenericFarmAssets', $id, $params);
    }

    public function createGenericFarmAsset(array $data): array
    {
        return $this->createActivity('GenericFarmAssets', $data);
    }

    public function updateGenericFarmAsset(string $id, array $data): array
    {
        return $this->updateActivity('GenericFarmAssets', $id, $data);
    }

    public function patchGenericFarmAsset(string $id, array $data): array
    {
        return $this->patchActivity('GenericFarmAssets', $id, $data);
    }

    public function deleteGenericFarmAsset(string $id): bool
    {
        return $this->deleteActivity('GenericFarmAssets', $id);
    }

    // ====================================================================
    // Farm Calendar Activities
    // ====================================================================

    public function getFarmCalendarActivities(array $filters = []): array
    {
        return $this->getActivities('FarmCalendarActivities', $filters);
    }

    public function getFarmCalendarActivity(string $id, array $params = []): array
    {
        return $this->getActivity('FarmCalendarActivities', $id, $params);
    }

    public function createFarmCalendarActivity(array $data): array
    {
        return $this->createActivity('FarmCalendarActivities', $data);
    }

    public function updateFarmCalendarActivity(string $id, array $data): array
    {
        return $this->updateActivity('FarmCalendarActivities', $id, $data);
    }

    public function patchFarmCalendarActivity(string $id, array $data): array
    {
        return $this->patchActivity('FarmCalendarActivities', $id, $data);
    }

    public function deleteFarmCalendarActivity(string $id): bool
    {
        return $this->deleteActivity('FarmCalendarActivities', $id);
    }

    // ====================================================================
    // Alerts
    // ====================================================================

    public function getAlerts(array $filters = []): array
    {
        return $this->getActivities('Alerts', $filters);
    }

    public function getAlert(string $id, array $params = []): array
    {
        return $this->getActivity('Alerts', $id, $params);
    }

    public function createAlert(array $data): array
    {
        return $this->createActivity('Alerts', $data);
    }

    public function updateAlert(string $id, array $data): array
    {
        return $this->updateActivity('Alerts', $id, $data);
    }

    public function patchAlert(string $id, array $data): array
    {
        return $this->patchActivity('Alerts', $id, $data);
    }

    public function deleteAlert(string $id): bool
    {
        return $this->deleteActivity('Alerts', $id);
    }

    // ====================================================================
    // Fertilization Operations
    // ====================================================================

    public function getFertilizationOperations(array $filters = []): array
    {
        return $this->getActivities('FertilizationOperations', $filters);
    }

    public function getFertilizationOperation(string $id, array $params = []): array
    {
        return $this->getActivity('FertilizationOperations', $id, $params);
    }

    public function createFertilizationOperation(array $data): array
    {
        return $this->createActivity('FertilizationOperations', $data);
    }

    public function updateFertilizationOperation(string $id, array $data): array
    {
        return $this->updateActivity('FertilizationOperations', $id, $data);
    }

    public function patchFertilizationOperation(string $id, array $data): array
    {
        return $this->patchActivity('FertilizationOperations', $id, $data);
    }

    public function deleteFertilizationOperation(string $id): bool
    {
        return $this->deleteActivity('FertilizationOperations', $id);
    }

    // ====================================================================
    // Fertilizers
    // ====================================================================

    public function getFertilizers(array $filters = []): array
    {
        return $this->getActivities('Fertilizers', $filters);
    }

    public function getFertilizer(string $id, array $params = []): array
    {
        return $this->getActivity('Fertilizers', $id, $params);
    }

    public function createFertilizer(array $data): array
    {
        return $this->createActivity('Fertilizers', $data);
    }

    public function updateFertilizer(string $id, array $data): array
    {
        return $this->updateActivity('Fertilizers', $id, $data);
    }

    public function patchFertilizer(string $id, array $data): array
    {
        return $this->patchActivity('Fertilizers', $id, $data);
    }

    public function deleteFertilizer(string $id): bool
    {
        return $this->deleteActivity('Fertilizers', $id);
    }

    // ====================================================================
    // Irrigation Operations
    // ====================================================================

    public function getIrrigationOperations(array $filters = []): array
    {
        return $this->getActivities('IrrigationOperations', $filters);
    }

    public function getIrrigationOperation(string $id, array $params = []): array
    {
        return $this->getActivity('IrrigationOperations', $id, $params);
    }

    public function createIrrigationOperation(array $data): array
    {
        return $this->createActivity('IrrigationOperations', $data);
    }

    public function updateIrrigationOperation(string $id, array $data): array
    {
        return $this->updateActivity('IrrigationOperations', $id, $data);
    }

    public function patchIrrigationOperation(string $id, array $data): array
    {
        return $this->patchActivity('IrrigationOperations', $id, $data);
    }

    public function deleteIrrigationOperation(string $id): bool
    {
        return $this->deleteActivity('IrrigationOperations', $id);
    }

    // ====================================================================
    // Crop Protection Operations
    // ====================================================================

    public function getCropProtectionOperations(array $filters = []): array
    {
        return $this->getActivities('CropProtectionOperations', $filters);
    }

    public function getCropProtectionOperation(string $id, array $params = []): array
    {
        return $this->getActivity('CropProtectionOperations', $id, $params);
    }

    public function createCropProtectionOperation(array $data): array
    {
        return $this->createActivity('CropProtectionOperations', $data);
    }

    public function updateCropProtectionOperation(string $id, array $data): array
    {
        return $this->updateActivity('CropProtectionOperations', $id, $data);
    }

    public function patchCropProtectionOperation(string $id, array $data): array
    {
        return $this->patchActivity('CropProtectionOperations', $id, $data);
    }

    public function deleteCropProtectionOperation(string $id): bool
    {
        return $this->deleteActivity('CropProtectionOperations', $id);
    }

    // ====================================================================
    // Pesticides
    // ====================================================================

    public function getPesticides(array $filters = []): array
    {
        return $this->getActivities('Pesticides', $filters);
    }

    public function getPesticide(string $id, array $params = []): array
    {
        return $this->getActivity('Pesticides', $id, $params);
    }

    public function createPesticide(array $data): array
    {
        return $this->createActivity('Pesticides', $data);
    }

    public function updatePesticide(string $id, array $data): array
    {
        return $this->updateActivity('Pesticides', $id, $data);
    }

    public function patchPesticide(string $id, array $data): array
    {
        return $this->patchActivity('Pesticides', $id, $data);
    }

    public function deletePesticide(string $id): bool
    {
        return $this->deleteActivity('Pesticides', $id);
    }

    // ====================================================================
    // Compost Operations
    // ====================================================================

    public function getCompostOperations(array $filters = []): array
    {
        return $this->getActivities('CompostOperations', $filters);
    }

    public function getCompostOperation(string $id, array $params = []): array
    {
        return $this->getActivity('CompostOperations', $id, $params);
    }

    public function createCompostOperation(array $data): array
    {
        return $this->createActivity('CompostOperations', $data);
    }

    public function updateCompostOperation(string $id, array $data): array
    {
        return $this->updateActivity('CompostOperations', $id, $data);
    }

    public function patchCompostOperation(string $id, array $data): array
    {
        return $this->patchActivity('CompostOperations', $id, $data);
    }

    public function deleteCompostOperation(string $id): bool
    {
        return $this->deleteActivity('CompostOperations', $id);
    }

    // ====================================================================
    // Add Raw Material Operations
    // ====================================================================

    public function getAddRawMaterialOperations(array $filters = []): array
    {
        return $this->getActivities('AddRawMaterialOperations', $filters);
    }

    public function getAddRawMaterialOperation(string $id, array $params = []): array
    {
        return $this->getActivity('AddRawMaterialOperations', $id, $params);
    }

    public function createAddRawMaterialOperation(array $data): array
    {
        return $this->createActivity('AddRawMaterialOperations', $data);
    }

    public function updateAddRawMaterialOperation(string $id, array $data): array
    {
        return $this->updateActivity('AddRawMaterialOperations', $id, $data);
    }

    public function patchAddRawMaterialOperation(string $id, array $data): array
    {
        return $this->patchActivity('AddRawMaterialOperations', $id, $data);
    }

    public function deleteAddRawMaterialOperation(string $id): bool
    {
        return $this->deleteActivity('AddRawMaterialOperations', $id);
    }

    // ====================================================================
    // Compost Turning Operations
    // ====================================================================

    public function getCompostTurningOperations(array $filters = []): array
    {
        return $this->getActivities('CompostTurningOperations', $filters);
    }

    public function getCompostTurningOperation(string $id, array $params = []): array
    {
        return $this->getActivity('CompostTurningOperations', $id, $params);
    }

    public function createCompostTurningOperation(array $data): array
    {
        return $this->createActivity('CompostTurningOperations', $data);
    }

    public function updateCompostTurningOperation(string $id, array $data): array
    {
        return $this->updateActivity('CompostTurningOperations', $id, $data);
    }

    public function patchCompostTurningOperation(string $id, array $data): array
    {
        return $this->patchActivity('CompostTurningOperations', $id, $data);
    }

    public function deleteCompostTurningOperation(string $id): bool
    {
        return $this->deleteActivity('CompostTurningOperations', $id);
    }

    // ====================================================================
    // Observations
    // ====================================================================

    public function getObservations(array $filters = []): array
    {
        return $this->getActivities('Observations', $filters);
    }

    public function getObservation(string $id, array $params = []): array
    {
        return $this->getActivity('Observations', $id, $params);
    }

    public function createObservation(array $data): array
    {
        return $this->createActivity('Observations', $data);
    }

    public function updateObservation(string $id, array $data): array
    {
        return $this->updateActivity('Observations', $id, $data);
    }

    public function patchObservation(string $id, array $data): array
    {
        return $this->patchActivity('Observations', $id, $data);
    }

    public function deleteObservation(string $id): bool
    {
        return $this->deleteActivity('Observations', $id);
    }

    // ====================================================================
    // Yield Prediction
    // ====================================================================

    public function getYieldPredictions(array $filters = []): array
    {
        return $this->getActivities('YieldPrediction', $filters);
    }

    public function getYieldPrediction(string $id, array $params = []): array
    {
        return $this->getActivity('YieldPrediction', $id, $params);
    }

    public function createYieldPrediction(array $data): array
    {
        return $this->createActivity('YieldPrediction', $data);
    }

    public function updateYieldPrediction(string $id, array $data): array
    {
        return $this->updateActivity('YieldPrediction', $id, $data);
    }

    public function patchYieldPrediction(string $id, array $data): array
    {
        return $this->patchActivity('YieldPrediction', $id, $data);
    }

    public function deleteYieldPrediction(string $id): bool
    {
        return $this->deleteActivity('YieldPrediction', $id);
    }

    // ====================================================================
    // Disease Detection
    // ====================================================================

    public function getDiseaseDetections(array $filters = []): array
    {
        return $this->getActivities('DiseaseDetection', $filters);
    }

    public function getDiseaseDetection(string $id, array $params = []): array
    {
        return $this->getActivity('DiseaseDetection', $id, $params);
    }

    public function createDiseaseDetection(array $data): array
    {
        return $this->createActivity('DiseaseDetection', $data);
    }

    public function updateDiseaseDetection(string $id, array $data): array
    {
        return $this->updateActivity('DiseaseDetection', $id, $data);
    }

    public function patchDiseaseDetection(string $id, array $data): array
    {
        return $this->patchActivity('DiseaseDetection', $id, $data);
    }

    public function deleteDiseaseDetection(string $id): bool
    {
        return $this->deleteActivity('DiseaseDetection', $id);
    }

    // ====================================================================
    // Vigor Estimation
    // ====================================================================

    public function getVigorEstimations(array $filters = []): array
    {
        return $this->getActivities('VigorEstimation', $filters);
    }

    public function getVigorEstimation(string $id, array $params = []): array
    {
        return $this->getActivity('VigorEstimation', $id, $params);
    }

    public function createVigorEstimation(array $data): array
    {
        return $this->createActivity('VigorEstimation', $data);
    }

    public function updateVigorEstimation(string $id, array $data): array
    {
        return $this->updateActivity('VigorEstimation', $id, $data);
    }

    public function patchVigorEstimation(string $id, array $data): array
    {
        return $this->patchActivity('VigorEstimation', $id, $data);
    }

    public function deleteVigorEstimation(string $id): bool
    {
        return $this->deleteActivity('VigorEstimation', $id);
    }

    // ====================================================================
    // Spraying Recommendation
    // ====================================================================

    public function getSprayingRecommendations(array $filters = []): array
    {
        return $this->getActivities('SprayingRecommendation', $filters);
    }

    public function getSprayingRecommendation(string $id, array $params = []): array
    {
        return $this->getActivity('SprayingRecommendation', $id, $params);
    }

    public function createSprayingRecommendation(array $data): array
    {
        return $this->createActivity('SprayingRecommendation', $data);
    }

    public function updateSprayingRecommendation(string $id, array $data): array
    {
        return $this->updateActivity('SprayingRecommendation', $id, $data);
    }

    public function patchSprayingRecommendation(string $id, array $data): array
    {
        return $this->patchActivity('SprayingRecommendation', $id, $data);
    }

    public function deleteSprayingRecommendation(string $id): bool
    {
        return $this->deleteActivity('SprayingRecommendation', $id);
    }

    // ====================================================================
    // Crop Stress Indicator Observations
    // ====================================================================

    public function getCropStressIndicatorObservations(array $filters = []): array
    {
        return $this->getActivities('CropStressIndicatorObservations', $filters);
    }

    public function getCropStressIndicatorObservation(string $id, array $params = []): array
    {
        return $this->getActivity('CropStressIndicatorObservations', $id, $params);
    }

    public function createCropStressIndicatorObservation(array $data): array
    {
        return $this->createActivity('CropStressIndicatorObservations', $data);
    }

    public function updateCropStressIndicatorObservation(string $id, array $data): array
    {
        return $this->updateActivity('CropStressIndicatorObservations', $id, $data);
    }

    public function patchCropStressIndicatorObservation(string $id, array $data): array
    {
        return $this->patchActivity('CropStressIndicatorObservations', $id, $data);
    }

    public function deleteCropStressIndicatorObservation(string $id): bool
    {
        return $this->deleteActivity('CropStressIndicatorObservations', $id);
    }

    // ====================================================================
    // Crop Growth Stage Observations
    // ====================================================================

    public function getCropGrowthStageObservations(array $filters = []): array
    {
        return $this->getActivities('CropGrowthStageObservations', $filters);
    }

    public function getCropGrowthStageObservation(string $id, array $params = []): array
    {
        return $this->getActivity('CropGrowthStageObservations', $id, $params);
    }

    public function createCropGrowthStageObservation(array $data): array
    {
        return $this->createActivity('CropGrowthStageObservations', $data);
    }

    public function updateCropGrowthStageObservation(string $id, array $data): array
    {
        return $this->updateActivity('CropGrowthStageObservations', $id, $data);
    }

    public function patchCropGrowthStageObservation(string $id, array $data): array
    {
        return $this->patchActivity('CropGrowthStageObservations', $id, $data);
    }

    public function deleteCropGrowthStageObservation(string $id): bool
    {
        return $this->deleteActivity('CropGrowthStageObservations', $id);
    }

    // ====================================================================
    // Nested Compost Operation Endpoints
    // ====================================================================

    /**
     * Get Add Raw Material Operations for a specific Compost Operation.
     */
    public function getCompostOperationAddRawMaterials(string $compostOperationId, array $filters = []): array
    {
        $response = $this->client()->get(
            $this->endpoint("CompostOperations/{$compostOperationId}/AddRawMaterialOperations/"),
            $this->withFormat($filters)
        );

        return $this->handleResponse($response, 'Get Compost Operation Add Raw Materials');
    }

    /**
     * Get Irrigation Operations for a specific Compost Operation.
     */
    public function getCompostOperationIrrigations(string $compostOperationId, array $filters = []): array
    {
        $response = $this->client()->get(
            $this->endpoint("CompostOperations/{$compostOperationId}/IrrigationOperations/"),
            $this->withFormat($filters)
        );

        return $this->handleResponse($response, 'Get Compost Operation Irrigations');
    }

    /**
     * Get Observations for a specific Compost Operation.
     */
    public function getCompostOperationObservations(string $compostOperationId, array $filters = []): array
    {
        $response = $this->client()->get(
            $this->endpoint("CompostOperations/{$compostOperationId}/Observations/"),
            $this->withFormat($filters)
        );

        return $this->handleResponse($response, 'Get Compost Operation Observations');
    }

    /**
     * Get Compost Turning Operations for a specific Compost Operation.
     */
    public function getCompostOperationTurnings(string $compostOperationId, array $filters = []): array
    {
        $response = $this->client()->get(
            $this->endpoint("CompostOperations/{$compostOperationId}/CompostTurningOperations/"),
            $this->withFormat($filters)
        );

        return $this->handleResponse($response, 'Get Compost Operation Turnings');
    }

    // ====================================================================
    // Generic Activity Methods
    // ====================================================================

    /**
     * Get activities by type with filters.
     */
    public function getActivities(string $activityType, array $filters = []): array
    {
        try {
            $response = $this->client()->get(
                $this->endpoint("{$activityType}/"),
                $this->withFormat($filters)
            );
        } catch (RequestException $e) {
            throw $e;
        }
        return $this->handleResponse($response, "Get {$activityType}");
    }

    /**
     * Create a new activity of the specified type.
     */
    public function createActivity(string $activityType, array $data): array
    {
        $response = $this->client()->post(
            $this->endpoint("{$activityType}/"),
            $data
        );
        return $this->handleResponse($response, "Create {$activityType}");
    }

    /**
     * Get a specific activity by type and ID.
     */
    public function getActivity(string $activityType, string $id, array $params = []): array
    {
        $response = $this->client()->get(
            $this->endpoint("{$activityType}/{$id}/"),
            $this->withFormat($params)
        );

        return $this->handleResponse($response, "Get {$activityType}");
    }

    /**
     * Update a specific activity.
     */
    public function updateActivity(string $activityType, string $id, array $data): array
    {
        $response = $this->client()->put(
            $this->endpoint("{$activityType}/{$id}/"),
            $data
        );

        return $this->handleResponse($response, "Update {$activityType}");
    }

    /**
     * Partially update a specific activity.
     */
    public function patchActivity(string $activityType, string $id, array $data): array
    {
        $response = $this->client()->patch(
            $this->endpoint("{$activityType}/{$id}/"),
            $data
        );

        return $this->handleResponse($response, "Patch {$activityType}");
    }

    /**
     * Delete a specific activity.
     */
    public function deleteActivity(string $activityType, string $id): bool
    {
        $response = $this->client()->delete(
            $this->endpoint("{$activityType}/{$id}/")
        );

        if ($response->successful()) {
            return true;
        }

        Log::error("FarmCalendar API Error - Delete {$activityType}", [
            'status' => $response->status(),
            'body' => $response->body(),
        ]);

        $response->throw();
        return false;
    }

    // ====================================================================
    // Farm Calendar Activity Types
    // ====================================================================

    /**
     * Get all activity types.
     */
    public function getActivityTypes(array $filters = []): array
    {
        $response = $this->client()->get(
            $this->endpoint('FarmCalendarActivityTypes/'),
            $this->withFormat($filters)
        );

        return $this->handleResponse($response, 'Get Activity Types');
    }

    /**
     * Get a specific activity type by ID.
     */
    public function getActivityType(string $id, array $params = []): array
    {
        $response = $this->client()->get(
            $this->endpoint("FarmCalendarActivityTypes/{$id}/"),
            $this->withFormat($params)
        );

        return $this->handleResponse($response, 'Get Activity Type');
    }

    // ====================================================================
    // Utility Methods
    // ====================================================================

    /**
     * Make a custom GET request to any endpoint.
     */
    public function get(string $endpoint, array $params = []): array
    {
        $response = $this->client()->get(
            $this->endpoint($endpoint),
            $this->withFormat($params)
        );

        return $this->handleResponse($response, "GET {$endpoint}");
    }

    /**
     * Make a custom POST request to any endpoint.
     */
    public function post(string $endpoint, array $data): array
    {
        $response = $this->client()->post(
            $this->endpoint($endpoint),
            $data
        );

        return $this->handleResponse($response, "POST {$endpoint}");
    }

    /**
     * Make a custom PUT request to any endpoint.
     */
    public function put(string $endpoint, array $data): array
    {
        $response = $this->client()->put(
            $this->endpoint($endpoint),
            $data
        );

        return $this->handleResponse($response, "PUT {$endpoint}");
    }

    /**
     * Make a custom PATCH request to any endpoint.
     */
    public function patch(string $endpoint, array $data): array
    {
        $response = $this->client()->patch(
            $this->endpoint($endpoint),
            $data
        );

        return $this->handleResponse($response, "PATCH {$endpoint}");
    }

    /**
     * Make a custom DELETE request to any endpoint.
     */
    public function delete(string $endpoint): bool
    {
        $response = $this->client()->delete(
            $this->endpoint($endpoint)
        );

        if ($response->successful()) {
            return true;
        }

        Log::error("FarmCalendar API Error - DELETE {$endpoint}", [
            'status' => $response->status(),
            'body' => $response->body(),
        ]);

        $response->throw();
        return false;
    }

    /**
     * Get the base URL being used.
     */
    public function getBaseUrl(): string
    {
        return $this->baseUrl;
    }

    /**
     * Get the API version being used.
     */
    public function getApiVersion(): string
    {
        return $this->apiVersion;
    }
}
