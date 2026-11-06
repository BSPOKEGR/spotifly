<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\FarmCalendarService;
use App\Services\PestDiseaseService;
use App\Services\WeatherService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use OpenApi\Attributes as OA;

class KPIController extends BaseController
{
    public function __construct(
        private FarmCalendarService $farmCalendar,
        private PestDiseaseService $pestDiseaseService,
        private WeatherService $weatherService
    ) {}

    /**
     * Get KPI data for a parcel
     */
    #[OA\Get(
        path: '/api/parcels/{id}/kpis',
        description: 'Retrieve aggregated key performance indicators for a specific parcel from all services',
        summary: 'Get KPIs for parcel',
        security: [['sanctum' => []]],
        tags: ['KPIs'],
        parameters: [
            new OA\Parameter(
                name: 'id',
                description: 'Parcel ID',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'string')
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Successful response',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(
                            property: 'data',
                            properties: [
                                new OA\Property(property: 'parcelId', type: 'string'),
                                new OA\Property(property: 'cropHealth', type: 'number'),
                                new OA\Property(property: 'soilMoisture', type: 'string'),
                                new OA\Property(property: 'pestPressure', type: 'number'),
                                new OA\Property(property: 'diseasePressure', type: 'number'),
                                new OA\Property(property: 'weatherRisk', type: 'string'),
                                new OA\Property(property: 'pendingActions', type: 'integer'),
                                new OA\Property(property: 'completedActions', type: 'integer'),
                                new OA\Property(property: 'activeAlerts', type: 'integer')
                            ],
                            type: 'object'
                        )
                    ]
                )
            ),
            new OA\Response(response: 500, description: 'Server error')
        ]
    )]
    public function show(Request $request, string $parcelId): JsonResponse
    {
        try {
            // Set user's JWT token if available
            $token = $request->bearerToken();
            if ($token) {
                $this->farmCalendar->setUserToken($token);
                $this->pestDiseaseService->setUserToken($token);
            }

            // Gather data from multiple services
            $kpi = $this->aggregateKPIData($parcelId);

            return response()->json([
                'data' => $kpi,
            ]);
        } catch (\Illuminate\Http\Client\RequestException $e) {
            $response = $this->handleRequestException($e, 'KPI data', ['parcel_id' => $parcelId]);
            if ($response) {
                return $response;
            }

            Log::error('Service API error while fetching KPI data', [
                'parcelId' => $parcelId,
                'status' => $e->response->status(),
                'body' => $e->response->body(),
            ]);

            return response()->json([
                'error' => 'Failed to fetch KPI data',
            ], 500);
        } catch (\Exception $e) {
            Log::error('Error fetching KPI data', [
                'parcelId' => $parcelId,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'error' => 'An error occurred while fetching KPI data',
            ], 500);
        }
    }

    /**
     * Aggregate KPI data from all services
     */
    private function aggregateKPIData(string $parcelId): array
    {
        $kpi = [
            'parcelId' => $parcelId,
            'date' => now()->toISOString(),
            'insectCount' => 0,
            'riskScore' => 0,
            'yieldPrediction' => 0,
            'moistureLevel' => 0,
            'pestPressure' => 0,
            'diseaseRisk' => 0,
        ];

        // Get parcel location for weather data
        try {
            $parcel = $this->farmCalendar->getFarmParcel($parcelId);
            if (!empty($parcel)) {
                // Extract coordinates from OCSM format
                $geometry = $parcel['hasGeometry'] ?? $parcel['geometry'] ?? null;
                if ($geometry) {
                    // Handle WKT format or GeoJSON
                    if (isset($geometry['asWKT'])) {
                        // Parse WKT format (e.g., "POINT(lon lat)")
                        preg_match('/POINT\(([^ ]+) ([^ ]+)\)/', $geometry['asWKT'], $matches);
                        $lon = (float)($matches[1] ?? 0);
                        $lat = (float)($matches[2] ?? 0);
                    } else {
                        // GeoJSON format
                        $coordinates = $geometry['coordinates'][0][0] ?? [0, 0];
                        $lon = $coordinates[0];
                        $lat = $coordinates[1];
                    }

                    // Get weather data
                    $kpi['moistureLevel'] = $this->getMoistureLevel($lat, $lon);
                }
            }
        } catch (\Exception $e) {
            Log::warning('Error fetching parcel for KPI', ['error' => $e->getMessage()]);
        }

        // Get pest data
        $pestMetrics = $this->getPestMetrics($parcelId);
        $kpi['insectCount'] = $pestMetrics['count'];
        $kpi['pestPressure'] = $pestMetrics['pressure'];

        // Get disease data
        $diseaseMetrics = $this->getDiseaseMetrics($parcelId);
        $kpi['diseaseRisk'] = $diseaseMetrics['risk'];

        // Calculate overall risk score
        $kpi['riskScore'] = round(
            ($kpi['pestPressure'] + $kpi['diseaseRisk']) / 2
        );

        // Yield prediction (simplified - could be more sophisticated)
        $kpi['yieldPrediction'] = $this->calculateYieldPrediction(
            $kpi['riskScore'],
            $kpi['moistureLevel']
        );

        return $kpi;
    }

    /**
     * Get moisture level from weather data
     */
    private function getMoistureLevel(float $lat, float $lon): int
    {
        try {
            $forecast = $this->weatherService->getForecast5Days($lat, $lon);

            if (!empty($forecast)) {
                $current = $forecast['forecast'][0] ?? $forecast['list'][0] ?? null;

                if ($current) {
                    $humidity = $current['main']['humidity'] ?? $current['humidity'] ?? 60;
                    return round($humidity);
                }
            }
        } catch (\Exception $e) {
            Log::warning('Error fetching weather data for KPI', [
                'lat' => $lat,
                'lon' => $lon,
                'error' => $e->getMessage(),
            ]);
        }

        return 60; // Default value
    }

    /**
     * Get pest metrics (count and pressure)
     */
    private function getPestMetrics(string $parcelId): array
    {
        $count = 0;
        $totalRisk = 0;
        $pestCount = 0;

        try {
            // Get all pest models
            $pests = $this->pestDiseaseService->getPestModels();

            if (!empty($pests)) {
                foreach ($pests as $pest) {
                    // Get risk index for this pest model on the parcel
                    try {
                        $riskData = $this->pestDiseaseService->calculateRiskIndexHigh(
                            $parcelId,
                            [$pest['id']]
                        );

                        if (!empty($riskData)) {
                            $firstRisk = is_array($riskData) ? $riskData[0] : $riskData;
                            $riskIndex = $firstRisk['risk_index'] ?? 0;
                            $totalRisk += $riskIndex;
                            $pestCount++;

                            // Estimate count based on risk (simplified)
                            if ($riskIndex > 30) {
                                $count += round($riskIndex / 10);
                            }
                        }
                    } catch (\Exception $e) {
                        // Skip this pest if calculation fails
                        continue;
                    }
                }
            }
        } catch (\Exception $e) {
            Log::warning('Error fetching pest metrics', [
                'parcelId' => $parcelId,
                'error' => $e->getMessage(),
            ]);
        }

        $pressure = $pestCount > 0 ? round($totalRisk / $pestCount) : 0;

        return [
            'count' => $count,
            'pressure' => $pressure,
        ];
    }

    /**
     * Get disease risk metrics
     */
    private function getDiseaseMetrics(string $parcelId): array
    {
        $totalRisk = 0;
        $diseaseCount = 0;

        try {
            // Get all disease models (using getPestModels as it returns models for both)
            $diseases = $this->pestDiseaseService->getPestModels();

            if (!empty($diseases)) {
                foreach ($diseases as $disease) {
                    // Get risk index for this disease model on the parcel
                    try {
                        $riskData = $this->pestDiseaseService->calculateRiskIndexHigh(
                            $parcelId,
                            [$disease['id']]
                        );

                        if (!empty($riskData)) {
                            $firstRisk = is_array($riskData) ? $riskData[0] : $riskData;
                            $riskIndex = $firstRisk['risk_index'] ?? 0;
                            $totalRisk += $riskIndex;
                            $diseaseCount++;
                        }
                    } catch (\Exception $e) {
                        // Skip this disease if calculation fails
                        continue;
                    }
                }
            }
        } catch (\Exception $e) {
            Log::warning('Error fetching disease metrics', [
                'parcelId' => $parcelId,
                'error' => $e->getMessage(),
            ]);
        }

        $risk = $diseaseCount > 0 ? round($totalRisk / $diseaseCount) : 0;

        return ['risk' => $risk];
    }


}
