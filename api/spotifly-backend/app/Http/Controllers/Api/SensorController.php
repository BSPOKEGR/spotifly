<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\FarmCalendarService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use OpenApi\Attributes as OA;

class SensorController extends BaseController
{
    public function __construct(
        private FarmCalendarService $farmCalendarService
    )
    {
    }

    /**
     * Get sensors (agricultural machines) for a specific parcel
     */
    #[OA\Get(
        path: '/api/parcels/{parcelId}/sensors',
        description: 'Retrieve all agricultural machines (sensors) associated with a specific parcel',
        summary: 'Get sensors for a parcel',
        security: [['sanctum' => []]],
        tags: ['Sensors'],
        parameters: [
            new OA\Parameter(
                name: 'parcelId',
                description: 'The UUID of the parcel',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'string', example: '994cbbc6-d682-42b8-882f-25fca07dc08c')
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
                                    new OA\Property(property: '@id', type: 'string', example: 'urn:farmcalendar:AgriculturalMachine:123e4567-e89b-12d3-a456-426614174000'),
                                    new OA\Property(property: '@type', type: 'string', example: 'AgriculturalMachine'),
                                    new OA\Property(property: 'id', type: 'string', example: '123e4567-e89b-12d3-a456-426614174000'),
                                    new OA\Property(property: 'name', type: 'string', example: 'Tractor John Deere 6120M'),
                                    new OA\Property(property: 'description', type: 'string', example: 'Main tractor for field operations'),
                                    new OA\Property(property: 'machineType', type: 'string', example: 'Tractor'),
                                    new OA\Property(property: 'manufacturer', type: 'string', example: 'John Deere'),
                                    new OA\Property(property: 'model', type: 'string', example: '6120M'),
                                    new OA\Property(property: 'serialNumber', type: 'string', example: 'JD-2021-12345'),
                                    new OA\Property(property: 'acquisitionDate', type: 'string', format: 'date', example: '2021-03-15'),
                                    new OA\Property(property: 'isActive', type: 'boolean', example: true),
                                    new OA\Property(property: 'status', type: 'string', example: 'active'),
                                    new OA\Property(property: 'parcelId', type: 'string', example: '123e4567-e89b-12d3-a456-426614174000'),
                                ],
                                type: 'object'
                            )
                        )
                    ]
                )
            ),
            new OA\Response(response: 404, description: 'Parcel not found'),
            new OA\Response(response: 500, description: 'Server error')
        ]
    )]
    public function getSensorsForParcel(Request $request, string $parcelId): JsonResponse
    {
        try {
            // Get the parcel to verify it exists
            $parcel = $this->farmCalendarService->getFarmParcel($parcelId);

            if (!$parcel) {
                return response()->json([
                    'error' => 'Parcel not found',
                ], 404);
            }

            // Get all agricultural machines
            $machines = $this->farmCalendarService->getAgriculturalMachines([
                'parcel' => $parcelId,
            ]);

            // Transform the data to a simpler format
            $sensors = array_map(function ($machine) {
                return $this->transformMachine($machine);
            }, $machines);

            return response()->json([
                'data' => $sensors,
            ]);

        } catch (\Illuminate\Http\Client\RequestException $e) {
            $response = $this->handleRequestException($e, 'sensors', ['parcel_id' => $parcelId]);
            if ($response) {
                return $response;
            }

            Log::error('FarmCalendar API error while fetching sensors for parcel', [
                'parcelId' => $parcelId,
                'status' => $e->response->status(),
                'body' => $e->response->body(),
            ]);

            return response()->json([
                'error' => 'Failed to fetch sensors for parcel',
            ], 500);
        } catch (\Exception $e) {
            Log::error('Error fetching sensors for parcel', [
                'parcelId' => $parcelId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'error' => 'An error occurred while fetching sensors',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get all sensors (agricultural machines)
     */
    #[OA\Get(
        path: '/api/sensors',
        description: 'Retrieve all agricultural machines (sensors) in the system',
        summary: 'Get all sensors',
        security: [['sanctum' => []]],
        tags: ['Sensors'],
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
                                    new OA\Property(property: '@id', type: 'string'),
                                    new OA\Property(property: '@type', type: 'string'),
                                    new OA\Property(property: 'id', type: 'string'),
                                    new OA\Property(property: 'name', type: 'string'),
                                    new OA\Property(property: 'description', type: 'string'),
                                    new OA\Property(property: 'machineType', type: 'string'),
                                    new OA\Property(property: 'manufacturer', type: 'string'),
                                    new OA\Property(property: 'model', type: 'string'),
                                    new OA\Property(property: 'isActive', type: 'boolean'),
                                    new OA\Property(property: 'status', type: 'string'),
                                    new OA\Property(property: 'parcelId', type: 'string', example: '123e4567-e89b-12d3-a456-426614174000'),
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
    public function index(Request $request): JsonResponse
    {
        try {
            $machines = $this->farmCalendarService->getAgriculturalMachines();

            // Transform the data
            $sensors = array_map(function ($machine) {
                return $this->transformMachine($machine);
            }, $machines);

            return response()->json([
                'data' => $sensors,
            ]);

        } catch (\Illuminate\Http\Client\RequestException $e) {
            $response = $this->handleRequestException($e, 'sensors');
            if ($response) {
                return $response;
            }

            Log::error('FarmCalendar API error while fetching sensors', [
                'status' => $e->response->status(),
                'body' => $e->response->body(),
            ]);

            return response()->json([
                'error' => 'Failed to fetch sensors',
            ], 500);
        } catch (\Exception $e) {
            Log::error('Error fetching sensors', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'error' => 'An error occurred while fetching sensors',
            ], 500);
        }
    }


}
