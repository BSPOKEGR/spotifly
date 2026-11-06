<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Services\PestPredictionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

class PestPredictionController extends BaseController
{
    public function __construct(
        private readonly PestPredictionService $predictionService
    ) {}

    /**
     * Get the latest pest prediction
     */
    #[OA\Get(
        path: '/api/predictions/pest/latest',
        description: 'Retrieve the latest pest prediction from ML API, optionally filtered by parcel',
        summary: 'Get latest pest prediction',
        security: [['sanctum' => []]],
        tags: ['Predictions'],
        parameters: [
            new OA\Parameter(
                name: 'parcel_id',
                description: 'Filter by parcel ID',
                in: 'query',
                required: false,
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
                                new OA\Property(property: 'id', type: 'integer'),
                                new OA\Property(property: 'parcel_id', type: 'string'),
                                new OA\Property(property: 'response_data', type: 'object'),
                                new OA\Property(property: 'fetched_at', type: 'string', format: 'date-time')
                            ],
                            type: 'object'
                        )
                    ]
                )
            ),
            new OA\Response(response: 404, description: 'No predictions found'),
            new OA\Response(response: 500, description: 'Server error')
        ]
    )]
    public function latest(Request $request): JsonResponse
    {
        $parcelId = $request->query('parcel_id');
        $prediction = $this->predictionService->getLatestPrediction($parcelId);

        if (!$prediction) {
            return response()->json([
                'message' => 'No predictions found',
                'data' => null
            ], 404);
        }

        return response()->json([
            'data' => [
                'id' => $prediction->id,
                'parcel_id' => $prediction->parcel_id,
                'response_data' => $prediction->response_data,
                'fetched_at' => $prediction->fetched_at->toISOString(),
            ]
        ]);
    }

    /**
     * Get all predictions (with optional limit and parcel filter)
     */
    #[OA\Get(
        path: '/api/predictions/pest',
        description: 'Retrieve all pest predictions with optional limit and parcel filter',
        summary: 'Get all pest predictions',
        security: [['sanctum' => []]],
        tags: ['Predictions'],
        parameters: [
            new OA\Parameter(
                name: 'limit',
                description: 'Maximum number of predictions to return',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'integer', default: 10)
            ),
            new OA\Parameter(
                name: 'parcel_id',
                description: 'Filter by parcel ID',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'string')
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Successful response',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'data', type: 'array', items: new OA\Items(type: 'object')),
                        new OA\Property(property: 'count', type: 'integer')
                    ]
                )
            ),
            new OA\Response(response: 500, description: 'Server error')
        ]
    )]
    public function index(Request $request): JsonResponse
    {
        $limit = $request->query('limit', 10);
        $parcelId = $request->query('parcel_id');

        $predictions = $this->predictionService->getAllPredictions((int)$limit, $parcelId);

        return response()->json([
            'data' => $predictions,
            'count' => count($predictions)
        ]);
    }

    /**
     * Get predictions for a specific parcel
     */
    #[OA\Get(
        path: '/api/parcels/{parcelId}/predictions/pest',
        description: 'Retrieve pest predictions for a specific parcel',
        summary: 'Get parcel pest predictions',
        security: [['sanctum' => []]],
        tags: ['Predictions'],
        parameters: [
            new OA\Parameter(
                name: 'parcelId',
                description: 'Parcel ID',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'string')
            ),
            new OA\Parameter(
                name: 'limit',
                description: 'Maximum number of predictions to return',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'integer', default: 10)
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Successful response',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'data', type: 'array', items: new OA\Items(type: 'object')),
                        new OA\Property(property: 'count', type: 'integer')
                    ]
                )
            ),
            new OA\Response(response: 500, description: 'Server error')
        ]
    )]
    public function forParcel(Request $request, string $parcelId): JsonResponse
    {
        $limit = $request->query('limit', 10);
        $predictions = $this->predictionService->getAllPredictions((int)$limit, $parcelId);

        return response()->json([
            'data' => $predictions,
            'count' => count($predictions)
        ]);
    }
}
