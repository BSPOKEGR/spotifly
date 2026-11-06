<?php

declare(strict_types=1);

namespace App\Services;

use Carbon\Carbon;
use Carbon\Month;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PestDiseaseService
{
    const string OLIVE_MOTH = 'olive_moth';
    const string OLIVE_FRUIT_FLY = 'olive_fruit_fly';
    private string $baseUrl;
    private string $apiVersion;
    private int $timeout;
    private array $retryConfig;
    private ?string $accessToken = null;
    private ?string $userToken = null;

    public function __construct()
    {
        $this->baseUrl = rtrim(config('pestdisease.api_url'), '/');
        $this->apiVersion = config('pestdisease.api_version');
        $this->timeout = config('pestdisease.timeout');
        $this->retryConfig = config('pestdisease.retry');
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
     * Build the API endpoint URL.
     */
    private function endpoint(string $path): string
    {
        return "/api/{$this->apiVersion}/" . ltrim($path, '/');
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
            );

        // Use user token if available, otherwise use authenticated access token
        if ($this->userToken) {
            $client->withToken($this->userToken);
        } elseif ($this->accessToken) {
            $client->withToken($this->accessToken);
        }

        return $client;
    }

    /**
     * Handle API response and log errors.
     */
    private function handleResponse(Response $response, string $operation): array
    {
        if ($response->successful()) {
            return $response->json();
        }

        Log::error("Pest & Disease API Error - {$operation}", [
            'status' => $response->status(),
            'body' => $response->body(),
        ]);

        $response->throw();

        return [];
    }

    // ====================================================================
    // Authentication
    // ====================================================================

    /**
     * Login and get access token.
     *
     * @param string|null $username Override username from config
     * @param string|null $password Override password from config
     * @return array Token response
     */
    public function login(?string $username = null, ?string $password = null): array
    {
        $username = $username ?? config('pestdisease.auth.username');
        $password = $password ?? config('pestdisease.auth.password');

        $response = Http::baseUrl($this->baseUrl)
            ->timeout($this->timeout)
            ->post($this->endpoint('login/access-token/'), [
                'username' => $username,
                'password' => $password,
            ]);

        $data = $this->handleResponse($response, 'Login');

        // Store the access token for subsequent requests
        if (isset($data['access_token'])) {
            $this->accessToken = $data['access_token'];
        }

        return $data;
    }

    /**
     * Set the access token manually.
     */
    public function setAccessToken(string $token): void
    {
        $this->accessToken = $token;
    }

    // ====================================================================
    // Parcel Management
    // ====================================================================

    /**
     * Create a parcel with latitude and longitude.
     */
    public function createParcel(string $name, float $latitude, float $longitude): array
    {
        $response = $this->client()->post($this->endpoint('parcel/'), [
            'name' => $name,
            'latitude' => $latitude,
            'longitude' => $longitude,
        ]);

        return $this->handleResponse($response, 'Create Parcel');
    }

    /**
     * Create a parcel with WKT polygon format.
     */
    public function createParcelWkt(string $name, string $wktPolygon): array
    {
        $response = $this->client()->post($this->endpoint('parcel/wkt-format/'), [
            'name' => $name,
            'wkt_polygon' => $wktPolygon,
        ]);

        return $this->handleResponse($response, 'Create Parcel (WKT)');
    }

    /**
     * Get list of parcels.
     */
    public function getParcels(): array
    {
        $response = $this->client()->get($this->endpoint('parcel/'));

        return $this->handleResponse($response, 'Get Parcels');
    }

    /**
     * Get a specific parcel.
     */
    public function getParcel(string $parcelId): array
    {
        $response = $this->client()->get($this->endpoint("parcel/{$parcelId}/"));

        return $this->handleResponse($response, 'Get Parcel');
    }

    /**
     * Delete a parcel.
     */
    public function deleteParcel(string $parcelId): bool
    {
        $response = $this->client()->delete($this->endpoint("parcel/{$parcelId}/"));

        if ($response->successful()) {
            return true;
        }

        Log::error('Pest & Disease API Error - Delete Parcel', [
            'status' => $response->status(),
            'body' => $response->body(),
        ]);

        $response->throw();
        return false;
    }

    // ====================================================================
    // Disease Model Management
    // ====================================================================

    /**
     * Create a disease model.
     */
    public function createDisease(array $data): array
    {
        $response = $this->client()->post($this->endpoint('disease/'), $data);

        return $this->handleResponse($response, 'Create Disease');
    }

    /**
     * Get list of diseases.
     */
    public function getDiseases(): array
    {
        $response = $this->client()->get($this->endpoint('disease/'));

        return $this->handleResponse($response, 'Get Diseases');
    }

    /**
     * Get a specific disease.
     */
    public function getDisease(string $diseaseId): array
    {
        $response = $this->client()->get($this->endpoint("disease/{$diseaseId}/"));

        return $this->handleResponse($response, 'Get Disease');
    }

    /**
     * Delete a disease.
     */
    public function deleteDisease(string $diseaseId): bool
    {
        $response = $this->client()->delete($this->endpoint("disease/{$diseaseId}/"));

        return $response->successful();
    }

    // ====================================================================
    // Pest Model Management
    // ====================================================================

    /**
     * Create a pest model.
     */
    public function createPestModel(array $data): array
    {
        $response = $this->client()->post($this->endpoint('pest-model/'), $data);

        return $this->handleResponse($response, 'Create Pest Model');
    }

    /**
     * Get list of pest models.
     */
    public function getPestModels(): array
    {
        $response = $this->client()->get($this->endpoint('pest-model/'));

        return $this->handleResponse($response, 'Get Pest Models');
    }

    /**
     * Get a specific pest model.
     */
    public function getPestModel(string $modelId): array
    {
        $response = $this->client()->get($this->endpoint("pest-model/{$modelId}/"));

        return $this->handleResponse($response, 'Get Pest Model');
    }

    /**
     * Delete a pest model.
     */
    public function deletePestModel(string $modelId): bool
    {
        $response = $this->client()->delete($this->endpoint("pest-model/{$modelId}/"));

        return $response->successful();
    }

    // ====================================================================
    // Rule Management
    // ====================================================================

    /**
     * Create a rule for a pest model.
     */
    public function createRule(array $data): array
    {
        $response = $this->client()->post($this->endpoint('rule/'), $data);

        return $this->handleResponse($response, 'Create Rule');
    }

    /**
     * Get list of rules.
     */
    public function getRules(): array
    {
        $response = $this->client()->get($this->endpoint('rule/'));

        return $this->handleResponse($response, 'Get Rules');
    }

    /**
     * Delete a rule.
     */
    public function deleteRule(string $ruleId): bool
    {
        $response = $this->client()->delete($this->endpoint("rule/{$ruleId}/"));

        return $response->successful();
    }

    // ====================================================================
    // Data Upload
    // ====================================================================

    /**
     * Upload weather data CSV file.
     *
     * @param string $filePath Path to the CSV file
     * @param string $parcelId Parcel ID
     * @return array Upload response
     */
    public function uploadWeatherData(string $filePath, string $parcelId): array
    {
        $response = $this->client()
            ->attach('file', file_get_contents($filePath), basename($filePath))
            ->post($this->endpoint('data/upload/'), [
                'parcel_location' => $parcelId,
            ]);

        return $this->handleResponse($response, 'Upload Weather Data');
    }

    // ====================================================================
    // Growing Degree Days (GDD) Calculation
    // ====================================================================

    /**
     * Calculate Growing Degree Days for pest models.
     *
     * @param string $parcelId Parcel ID
     * @param array $modelIds List of pest model IDs
     * @param string $fromDate Start date (YYYY-MM-DD)
     * @param string $toDate End date (YYYY-MM-DD)
     * @return array GDD calculation results
     */
    public function calculateGDD(
        string $parcelId,
        array $modelIds,
        string $fromDate,
        string $toDate
    ): array {
        $modelIdsString = implode(',', $modelIds);

        $response = $this->client()->get(
            $this->endpoint("tool/calculate-gdd/parcel/{$parcelId}/model/{$modelIdsString}/verbose/{$fromDate}/from/{$toDate}/to/")
        );

        return $this->handleResponse($response, 'Calculate GDD');
    }

    // ====================================================================
    // Risk Index Calculation
    // ====================================================================

    /**
     * Calculate risk index for pest/disease models (verbose - all risk levels).
     *
     * @param string $parcelId Parcel ID
     * @param array $modelIds List of pest/disease model IDs
     * @return array Risk index results in JSON-LD format
     */
    public function calculateRiskIndexVerbose(string $parcelId, array $modelIds): array
    {
        $modelIdsString = implode(',', $modelIds);

        $response = $this->client()->post(
            $this->endpoint("tool/calculate-risk-index/weather/{$parcelId}/model/{$modelIdsString}/verbose/")
        );

        return $this->handleResponse($response, 'Calculate Risk Index (Verbose)');
    }

    /**
     * Calculate risk index for pest/disease models (high risk only).
     *
     * @param int $parcelId Parcel ID
     * @param array $modelIds List of pest/disease model IDs
     * @return array High risk results in JSON-LD format
     */
    public function calculateRiskIndexHigh(int $parcelId, array $modelIds): array
    {
        $modelIdsString = implode(',', $modelIds);

        $response = $this->client()->post(
            $this->endpoint("tool/calculate-risk-index/weather/{$parcelId}/model/{$modelIdsString}/high/")
        );

        return $this->handleResponse($response, 'Calculate Risk Index (High)');
    }

    // ====================================================================
    // Utility Methods
    // ====================================================================

    /**
     * Get the base URL being used.
     */
    public function getBaseUrl(): string
    {
        return $this->baseUrl;
    }

    /**
     * Check if the service is authenticated.
     */
    public function isAuthenticated(): bool
    {
        return $this->accessToken !== null;
    }

    public function getPestAlerts(
        array $parcelLocation,
        Carbon $toDate
    ): array {
        $fromDate = Carbon::today()->setMonth(Month::January)->setDay(1);

        $payload = [
            'parcel_id' => 0,
            'lat' => $parcelLocation['lat'],
            'lon' => $parcelLocation['lng'],
            'from_date' => $fromDate->toDateString(),
            'to_date' => $toDate->toDateString(),
        ];
        $oliveFruitFlyAlerts = $this->getOliveFruitFlyAlerts($payload);

        $fromDate = Carbon::today()->setMonth(Month::March)->setDay(1);
        $payload['from_date'] = $fromDate->toDateString();

        $oliveMothAlerts = $this->getOliveMothAlerts($payload);

        return [
            self::OLIVE_MOTH => $oliveMothAlerts,
            self::OLIVE_FRUIT_FLY => $oliveFruitFlyAlerts,
        ];
    }

    private function getOliveMothAlerts(array $payload): array
    {
        try {
            $response = $this->client()->post(
                $this->endpoint("olive-moth/olive-moth"),
                $payload
            );
        } catch (\Exception $e) {
            Log::error('Error fetching Olive Moth alerts', [
                'error' => $e->getMessage(),
                'payload' => $payload,
            ]);

            return [];
        }

        return $this->handleResponse($response, 'Get Olive Moth Alerts');
    }

    private function getOliveFruitFlyAlerts(array $payload): array
    {
        $response = $this->client()->post(
            $this->endpoint("olive-fruit-fly/olive-fruit-fly"),
            $payload
        );

        return $this->handleResponse($response, 'Get Olive Fruit Fly Alerts');
    }
}
