<?php

namespace App\Http\Controllers\Api;

use App\Services\WeatherForecastService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use OpenApi\Attributes as OA;

class WeatherController extends BaseController
{
    public function __construct(
        private WeatherForecastService $forecastService
    ) {}

    /**
     * Get weather forecast for a parcel
     */
    #[OA\Get(
        path: '/api/weather',
        description: 'Retrieve 5-day hourly weather forecast for a specific parcel from database',
        summary: 'Get hourly weather forecast by parcel ID',
        security: [['sanctum' => []]],
        tags: ['Weather'],
        parameters: [
            new OA\Parameter(
                name: 'parcel_id',
                description: 'Parcel ID (required)',
                in: 'query',
                required: true,
                schema: new OA\Schema(type: 'string', example: 'plot-123')
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
                            type: 'object',
                            additionalProperties: new OA\AdditionalProperties(
                                properties: [
                                    new OA\Property(
                                        property: 'data',
                                        type: 'object',
                                        additionalProperties: new OA\AdditionalProperties(type: 'number')
                                    )
                                ],
                                type: 'object'
                            )
                        ),
                        new OA\Property(property: 'source', type: 'string', example: 'database'),
                        new OA\Property(property: 'parcel_id', type: 'string', example: 'plot-123'),
                        new OA\Property(property: 'records_count', type: 'integer', example: 120)
                    ]
                )
            ),
            new OA\Response(response: 422, description: 'Validation error - parcel_id is required'),
            new OA\Response(response: 404, description: 'No forecast data available for this parcel'),
            new OA\Response(response: 500, description: 'Server error')
        ]
    )]
    public function forecast(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'parcel_id' => 'required|string',
        ]);

        try {
            $result = $this->forecastService->getHourlyForecast($validated['parcel_id']);

            return response()->json($result);

        } catch (\RuntimeException $e) {
            return response()->json([
                'error' => $e->getMessage(),
                'message' => 'Please ensure the daily weather job has run to populate forecast data',
            ], 404);

        } catch (\Exception $e) {
            Log::error('Error fetching weather forecast', [
                'parcel_id' => $validated['parcel_id'],
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'error' => 'An error occurred while fetching weather data',
            ], 500);
        }
    }

    /**
     * Get daily weather forecast for a parcel with aggregates
     */
    #[OA\Get(
        path: '/api/weather/daily',
        description: 'Retrieve 5-day daily aggregated weather forecast for a specific parcel with min, max, mean values',
        summary: 'Get daily weather forecast by parcel ID',
        security: [['sanctum' => []]],
        tags: ['Weather'],
        parameters: [
            new OA\Parameter(
                name: 'parcel_id',
                description: 'Parcel ID (required)',
                in: 'query',
                required: true,
                schema: new OA\Schema(type: 'string', example: 'plot-123')
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Successful response with daily aggregates',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(
                            property: 'data',
                            type: 'array',
                            items: new OA\Items(
                                properties: [
                                    new OA\Property(property: 'date', type: 'string', example: '2026-02-09'),
                                    new OA\Property(property: 'icon', type: 'string', example: 'sun'),
                                    new OA\Property(property: 'condition', type: 'string', example: 'Clear'),
                                    new OA\Property(
                                        property: 'temperature',
                                        properties: [
                                            new OA\Property(property: 'min', type: 'number', example: 8.5),
                                            new OA\Property(property: 'max', type: 'number', example: 18.8),
                                            new OA\Property(property: 'mean', type: 'number', example: 13.2)
                                        ],
                                        type: 'object'
                                    ),
                                    new OA\Property(
                                        property: 'humidity',
                                        properties: [
                                            new OA\Property(property: 'min', type: 'number', example: 44),
                                            new OA\Property(property: 'max', type: 'number', example: 87),
                                            new OA\Property(property: 'mean', type: 'number', example: 75.5)
                                        ],
                                        type: 'object'
                                    ),
                                    new OA\Property(
                                        property: 'wind_speed',
                                        properties: [
                                            new OA\Property(property: 'min', type: 'number'),
                                            new OA\Property(property: 'max', type: 'number'),
                                            new OA\Property(property: 'mean', type: 'number')
                                        ],
                                        type: 'object'
                                    ),
                                    new OA\Property(
                                        property: 'precipitation',
                                        properties: [
                                            new OA\Property(property: 'total', type: 'number'),
                                            new OA\Property(property: 'max', type: 'number')
                                        ],
                                        type: 'object'
                                    )
                                ],
                                type: 'object'
                            )
                        ),
                        new OA\Property(property: 'source', type: 'string', example: 'database'),
                        new OA\Property(property: 'parcel_id', type: 'string', example: 'plot-123')
                    ]
                )
            ),
            new OA\Response(response: 422, description: 'Validation error - parcel_id is required'),
            new OA\Response(response: 404, description: 'No forecast data available for this parcel'),
            new OA\Response(response: 500, description: 'Server error')
        ]
    )]
    public function dailyForecast(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'parcel_id' => 'required|string',
        ]);

        try {
            $result = $this->forecastService->getDailyForecast($validated['parcel_id']);

            return response()->json($result);

        } catch (\RuntimeException $e) {
            return response()->json([
                'error' => $e->getMessage(),
                'message' => 'Please ensure the daily weather job has run to populate forecast data',
            ], 404);

        } catch (\Exception $e) {
            Log::error('Error fetching daily weather forecast', [
                'parcel_id' => $validated['parcel_id'],
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'error' => 'An error occurred while fetching weather data',
            ], 500);
        }
    }
}
