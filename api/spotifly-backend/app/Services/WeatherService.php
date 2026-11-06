<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class WeatherService
{
    private string $baseUrl;
    private int $timeout;
    private array $retryConfig;
    private ?string $userToken = null;
    private ?string $serviceToken = null;

    public function __construct()
    {
        $this->baseUrl = rtrim(config('weatherservice.api_url'), '/');
        $this->timeout = config('weatherservice.timeout');
        $this->retryConfig = config('weatherservice.retry');
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
     * Get or generate a weather service JWT token.
     */
    private function getServiceToken(): string
    {
        // Check if we have a cached token
        $cachedToken = Cache::get('weatherservice_token');
        if ($cachedToken) {
            return $cachedToken;
        }

        // Generate a new token
        try {
            $response = Http::asForm()
                ->post("{$this->baseUrl}/api/v1/auth/token", [
                    'username' => 'jane.smith@company.com',
                    'password' => 'test1234',
                ]);

            if (!$response->successful()) {
                throw new \RuntimeException("Failed to generate weather service token: {$response->body()}");
            }

            $data = $response->json();
            $token = $data['jwt_token'] ?? null;

            if (!$token) {
                throw new \RuntimeException("No jwt_token in response from weather service");
            }

            // Cache the token for 50 minutes (assuming 60 minute expiration on the service)
            Cache::put('weatherservice_token', $token, now()->addMinutes(50));

            return $token;
        } catch (\Exception $e) {
            Log::error("Weather service token generation failed", [
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
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

        // Weather service uses its own JWT authentication system
        // Automatically generate and cache tokens
        try {
            $token = $this->getServiceToken();
            $client = $client->withToken($token);
        } catch (\Exception $e) {
            // Log the error but continue without token
            // This allows the request to be attempted even if token generation fails
            Log::warning("Could not obtain weather service token: {$e->getMessage()}");
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

        Log::error("Weather Service API Error - {$operation}", [
            'status' => $response->status(),
            'body' => $response->body(),
        ]);

        $response->throw();
        return [];
    }

    // ====================================================================
    // Weather Forecast Endpoints
    // ====================================================================

    /**
     * Get 5-day weather forecast with 3-hour intervals.
     *
     * @param float $lat Latitude
     * @param float $lon Longitude
     * @return array Weather forecast data
     */
    public function getForecast5Days(float $lat, float $lon): array
    {
        $response = $this->client()->get('/api/data/forecast5', [
            'lat' => $lat,
            'lon' => $lon,
        ]);

        $result = $this->handleResponse($response, 'Get 5-Day Forecast');

        Log::info('Weather service response', [
            'lat' => $lat,
            'lon' => $lon,
            'response' => $result,
        ]);

        return $result;
    }

    /**
     * Get 5-day weather forecast with 1-hour intervals.
     *
     * @param float $lat Latitude
     * @param float $lon Longitude
     * @return array Weather forecast data
     */
    public function getForecastHourly5Days(float $lat, float $lon): array
    {
        ///api/data/forecast5/openmeteo
        $response = $this->client()->get('/api/data/forecast5/openmeteo', [
            'lat' => $lat,
            'lon' => $lon,
        ]);

        $result = $this->handleResponse($response, 'Get 5-Day Forecast');

        Log::info('Weather service response', [
            'lat' => $lat,
            'lon' => $lon,
            'response' => $result,
        ]);

        return $result;
    }

    /**
     * Get 5-day weather forecast in JSON-LD format.
     *
     * @param float $lat Latitude
     * @param float $lon Longitude
     * @return array Weather forecast data in JSON-LD format
     */
    public function getForecast5DaysLinkedData(float $lat, float $lon): array
    {
        $response = $this->client()->get('/api/linkeddata/forecast5', [
            'lat' => $lat,
            'lon' => $lon,
        ]);

        return $this->handleResponse($response, 'Get 5-Day Forecast (JSON-LD)');
    }



    /**
     * Get current weather conditions.
     *
     * @param float $lat Latitude
     * @param float $lon Longitude
     * @return array Current weather data
     */
    public function getCurrentWeather(float $lat, float $lon): array
    {
        $response = $this->client()->get('/api/data/weather', [
            'lat' => $lat,
            'lon' => $lon,
        ]);

        return $this->handleResponse($response, 'Get Current Weather');
    }

    // ====================================================================
    // Temperature-Humidity Index (THI) Endpoints
    // ====================================================================

    /**
     * Get Temperature Humidity Index.
     *
     * @param float $lat Latitude
     * @param float $lon Longitude
     * @return array THI data
     */
    public function getTemperatureHumidityIndex(float $lat, float $lon): array
    {
        $response = $this->client()->get('/api/data/thi', [
            'lat' => $lat,
            'lon' => $lon,
        ]);

        return $this->handleResponse($response, 'Get Temperature Humidity Index');
    }

    /**
     * Get Temperature Humidity Index in JSON-LD format.
     *
     * @param float $lat Latitude
     * @param float $lon Longitude
     * @return array THI data in JSON-LD format
     */
    public function getTemperatureHumidityIndexLinkedData(float $lat, float $lon): array
    {
        $response = $this->client()->get('/api/linkeddata/thi', [
            'lat' => $lat,
            'lon' => $lon,
        ]);

        return $this->handleResponse($response, 'Get Temperature Humidity Index (JSON-LD)');
    }

    // ====================================================================
    // UAV Flight Forecast Endpoints
    // ====================================================================

    /**
     * Get 5-day flight forecast for UAVs.
     *
     * @param float $lat Latitude
     * @param float $lon Longitude
     * @param array|null $uavModels Filter by UAV model types (optional)
     * @param string|null $statusFilter Filter by status conditions (optional)
     * @return array Flight forecast data
     */
    public function getFlightForecast5Days(
        float $lat,
        float $lon,
        ?array $uavModels = null,
        ?string $statusFilter = null
    ): array {
        $params = [
            'lat' => $lat,
            'lon' => $lon,
        ];

        if ($uavModels !== null) {
            $params['uavmodels'] = implode(',', $uavModels);
        }

        if ($statusFilter !== null) {
            $params['status_filter'] = $statusFilter;
        }

        $response = $this->client()->get('/api/data/flight_forecast5', $params);

        return $this->handleResponse($response, 'Get Flight Forecast 5 Days');
    }

    /**
     * Get 5-day flight forecast in JSON-LD format.
     *
     * @param float $lat Latitude
     * @param float $lon Longitude
     * @param array|null $uavModels Filter by UAV model types (optional)
     * @param string|null $statusFilter Filter by status conditions (optional)
     * @return array Flight forecast data in JSON-LD format
     */
    public function getFlightForecast5DaysLinkedData(
        float $lat,
        float $lon,
        ?array $uavModels = null,
        ?string $statusFilter = null
    ): array {
        $params = [
            'lat' => $lat,
            'lon' => $lon,
        ];

        if ($uavModels !== null) {
            $params['uavmodels'] = implode(',', $uavModels);
        }

        if ($statusFilter !== null) {
            $params['status_filter'] = $statusFilter;
        }

        $response = $this->client()->get('/api/linkeddata/flight_forecast5', $params);

        return $this->handleResponse($response, 'Get Flight Forecast 5 Days (JSON-LD)');
    }

    // ====================================================================
    // Spray Forecast Endpoints
    // ====================================================================

    /**
     * Get spray condition forecast.
     *
     * @param float $lat Latitude
     * @param float $lon Longitude
     * @return array Spray forecast data
     */
    public function getSprayForecast(float $lat, float $lon): array
    {
        $response = $this->client()->get('/api/data/spray_forecast', [
            'lat' => $lat,
            'lon' => $lon,
        ]);

        return $this->handleResponse($response, 'Get Spray Forecast');
    }

    /**
     * Get spray condition forecast in JSON-LD format.
     *
     * @param float $lat Latitude
     * @param float $lon Longitude
     * @return array Spray forecast data in JSON-LD format
     */
    public function getSprayForecastLinkedData(float $lat, float $lon): array
    {
        $response = $this->client()->get('/api/linkeddata/spray_forecast', [
            'lat' => $lat,
            'lon' => $lon,
        ]);

        return $this->handleResponse($response, 'Get Spray Forecast (JSON-LD)');
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
}
