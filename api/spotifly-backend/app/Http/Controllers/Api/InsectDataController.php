<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\PestDiseaseService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use OpenApi\Attributes as OA;

class InsectDataController extends BaseController
{
    public function __construct(
        private PestDiseaseService $pestDiseaseService
    ) {}

    /**
     * Get insect/pest data for a parcel
     */
    #[OA\Get(
        path: '/api/parcels/{id}/insects',
        description: 'Retrieve pest and insect monitoring data for a specific parcel',
        summary: 'Get insect data for parcel',
        security: [['sanctum' => []]],
        tags: ['Insect Data'],
        parameters: [
            new OA\Parameter(
                name: 'id',
                description: 'Parcel ID',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer')
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
                            type: 'array',
                            items: new OA\Items(
                                properties: [
                                    new OA\Property(property: 'id', type: 'integer'),
                                    new OA\Property(property: 'name', type: 'string'),
                                    new OA\Property(property: 'count', type: 'integer'),
                                    new OA\Property(property: 'severity', type: 'string'),
                                    new OA\Property(property: 'trend', type: 'string'),
                                    new OA\Property(property: 'lastSeen', type: 'string', format: 'date-time')
                                ],
                                type: 'object'
                            )
                        )
                    ]
                )
            ),
            new OA\Response(response: 500, description: 'Server error')
        ]
    )]
    public function index(Request $request, int $parcelId): JsonResponse
    {
        try {
            // Set user's JWT token if available
            $token = $request->bearerToken();
            if ($token) {
                $this->pestDiseaseService->setUserToken($token);
            }

            // Get all pest models
            $pests = $this->pestDiseaseService->getPestModels();

            // Return empty array if no pest models available
            if (empty($pests)) {
                return response()->json(['data' => []]);
            }

            $insectData = $this->transformPestsToInsectData($pests, $parcelId);

            return response()->json(['data' => $insectData]);
        } catch (\Illuminate\Http\Client\RequestException $e) {
            $response = $this->handleRequestException($e, 'insect data', ['parcel_id' => $parcelId]);
            if ($response) {
                return $response;
            }

            Log::error('PD Service API error while fetching insect data', [
                'parcelId' => $parcelId,
                'status' => $e->response->status(),
                'body' => $e->response->body(),
            ]);

            // Return empty data instead of error when service is unavailable
            return response()->json(['data' => []]);
        } catch (\Exception $e) {
            Log::warning('Error fetching insect data, returning empty array', [
                'parcelId' => $parcelId,
                'error' => $e->getMessage(),
            ]);

            // Return empty data instead of error when no data is available
            return response()->json(['data' => []]);
        }
    }

    /**
     * Transform pest models to InsectData array
     *
     * @param array<int, array<string, mixed>> $pests
     * @return array<int, array<string, mixed>>
     */
    protected function transformPestsToInsectData(array $pests, int $parcelId): array
    {
        $insectData = [];

        foreach ($pests as $pest) {
            // Get risk index for this pest
            try {
                $modelIds = [$pest['id']];
                $riskData = $this->pestDiseaseService->calculateRiskIndexHigh($parcelId, $modelIds);

                $riskIndex = 0;
                if (!empty($riskData)) {
                    $firstRisk =  $riskData[0];
                    $riskIndex = $firstRisk['risk_index'] ?? 0;
                }

                // Determine severity based on risk index
                $severity = $this->calculateSeverity($riskIndex);

                // Determine trend (simplified - would need historical data)
                $trend = $this->calculateTrend($riskIndex);

                $insectData[] = [
                    'species' => $pest['name'] ?? 'Unknown Pest',
                    'count' => $pest['population'] ?? 0,
                    'date' => now()->toISOString(),
                    'severity' => $severity,
                    'trend' => $trend,
                ];
            } catch (\Exception $e) {
                Log::warning('Error calculating risk for pest', [
                    'pest_id' => $pest['id'],
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $insectData;
    }
}
