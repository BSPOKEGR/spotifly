<?php

namespace App\Http\Controllers\Api;

use App\Libraries\ParcelHelper;
use App\Services\FarmCalendarService;
use App\Services\PestPredictionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use OpenApi\Attributes as OA;

class ParcelController extends BaseController
{
    public function __construct(
        private FarmCalendarService $farmCalendar,
        private PestPredictionService $pestPrediction,
    )
    {
    }

    /**
     * Get all parcels for authenticated user
     */
    #[OA\Get(
        path: '/api/parcels',
        description: 'Retrieve all farm parcels for the authenticated user',
        summary: 'Get all parcels',
        security: [['sanctum' => []]],
        tags: ['Parcels'],
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
                                    new OA\Property(property: 'id', type: 'integer', example: 1),
                                    new OA\Property(property: 'name', type: 'string', example: 'North Field'),
                                    new OA\Property(property: 'crop', type: 'string', example: 'Wheat'),
                                    new OA\Property(property: 'area', type: 'number', example: 25.5),
                                    new OA\Property(
                                        property: 'polygon',
                                        type: 'array',
                                        items: new OA\Items(
                                            type: 'array',
                                            items: new OA\Items(type: 'number'),
                                            example: [40.7128, -74.0060]
                                        )
                                    ),
                                    new OA\Property(
                                        property: 'location',
                                        properties: [
                                            new OA\Property(property: 'lat', type: 'number', example: 40.7128),
                                            new OA\Property(property: 'lng', type: 'number', example: -74.0060)
                                        ],
                                        type: 'object'
                                    ),
                                    new OA\Property(property: 'owner', type: 'string', example: 'John Doe'),
                                    new OA\Property(property: 'plantingDate', type: 'string', format: 'date', example: '2025-03-15'),
                                    new OA\Property(property: 'harvestDate', type: 'string', format: 'date', example: '2025-09-20')
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
            // Get farm parcels from FarmCalendar
            $parcels = $this->farmCalendar->getFarmParcels();
            $sensors = $this->farmCalendar->getAgriculturalMachines();
            $transformedParcels = $this->transformPlotsToParcels($parcels, $sensors);

            // Attach latest pest prediction per parcel
            $parcelIds = array_column($transformedParcels, 'id');
            $pestPredictions = $this->pestPrediction->getPredictionsForParcels($parcelIds);
            $pestMap = [];
            foreach ($pestPredictions as $prediction) {
                $responseData = $prediction['response_data'] ?? [];
                $pestMap[$prediction['parcel_id']] = array_merge($responseData, [
                    'fetched_at' => $prediction['fetched_at'],
                ]);
            }
            $transformedParcels = array_map(function ($parcel) use ($pestMap) {
                $parcel['ml_prediction'] = $pestMap[$parcel['id']] ?? null;
                return $parcel;
            }, $transformedParcels);

            return response()->json([
                'data' => $transformedParcels,
            ]);
        } catch (\Illuminate\Http\Client\RequestException $e) {
            // Try to handle common HTTP errors
            $response = $this->handleRequestException($e, 'parcels');
            if ($response) {
                return $response;
            }

            // Log and return 500 for unhandled errors
            Log::error('Error fetching parcels from FarmCalendar', [
                'status' => $e->response->status() ?? null,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'error' => 'An error occurred while fetching parcels',
                'message' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        } catch (\Exception $e) {
            Log::error('Error fetching parcels', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            return response()->json([
                'error' => 'An error occurred while fetching parcels',
                'message' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Get single parcel by ID
     */
    #[OA\Get(
        path: '/api/parcels/{id}',
        description: 'Retrieve a single farm parcel by its ID',
        summary: 'Get parcel by ID',
        security: [['sanctum' => []]],
        tags: ['Parcels'],
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
                                new OA\Property(property: 'id', type: 'integer', example: 1),
                                new OA\Property(property: 'name', type: 'string', example: 'North Field'),
                                new OA\Property(property: 'crop', type: 'string', example: 'Wheat'),
                                new OA\Property(property: 'area', type: 'number', example: 25.5),
                                new OA\Property(
                                    property: 'polygon',
                                    type: 'array',
                                    items: new OA\Items(
                                        type: 'array',
                                        items: new OA\Items(type: 'number')
                                    )
                                ),
                                new OA\Property(
                                    property: 'location',
                                    properties: [
                                        new OA\Property(property: 'lat', type: 'number'),
                                        new OA\Property(property: 'lng', type: 'number')
                                    ],
                                    type: 'object'
                                )
                            ],
                            type: 'object'
                        )
                    ]
                )
            ),
            new OA\Response(response: 404, description: 'Parcel not found'),
            new OA\Response(response: 500, description: 'Server error')
        ]
    )]
    public function show(Request $request, string $id): JsonResponse
    {
        try {
            $plot = $this->farmCalendar->getFarmParcel($id);
            $sensors = $this->farmCalendar->getAgriculturalMachines([
                'parcel' => $id,
            ]);
            $parcel = $this->transformPlotToParcel($plot, $sensors);

            return response()->json([
                'data' => $parcel,
            ]);
        } catch (\Illuminate\Http\Client\RequestException $e) {
            // Try to handle common HTTP errors
            $response = $this->handleRequestException($e, 'parcel');
            if ($response) {
                return $response;
            }

            Log::error('Error fetching parcel', [
                'id' => $id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'error' => 'Failed to fetch parcel',
            ], 500);
        } catch (\Exception $e) {
            Log::error('Error fetching parcel', [
                'id' => $id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'error' => 'An error occurred while fetching parcel',
            ], 500);
        }
    }

    /**
     * Create new parcel
     */
    #[OA\Post(
        path: '/api/parcels',
        description: 'Create a new farm parcel',
        summary: 'Create new parcel',
        security: [['sanctum' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['name', 'crop', 'area', 'polygon', 'location'],
                properties: [
                    new OA\Property(property: 'name', type: 'string', example: 'North Field'),
                    new OA\Property(property: 'crop', type: 'string', example: 'Wheat'),
                    new OA\Property(property: 'area', type: 'number', example: 25.5),
                    new OA\Property(
                        property: 'polygon',
                        type: 'array',
                        items: new OA\Items(
                            type: 'array',
                            items: new OA\Items(type: 'number'),
                            example: [40.7128, -74.0060]
                        ),
                        minItems: 3
                    ),
                    new OA\Property(
                        property: 'location',
                        properties: [
                            new OA\Property(property: 'lat', type: 'number', example: 40.7128),
                            new OA\Property(property: 'lng', type: 'number', example: -74.0060)
                        ],
                        type: 'object'
                    ),
                    new OA\Property(property: 'owner', type: 'string', example: 'John Doe', nullable: true),
                    new OA\Property(property: 'plantingDate', type: 'string', format: 'date', example: '2025-03-15', nullable: true),
                    new OA\Property(property: 'harvestDate', type: 'string', format: 'date', example: '2025-09-20', nullable: true)
                ]
            )
        ),
        tags: ['Parcels'],
        responses: [
            new OA\Response(
                response: 201,
                description: 'Parcel created successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'data', type: 'object'),
                        new OA\Property(property: 'message', type: 'string', example: 'Parcel created successfully')
                    ]
                )
            ),
            new OA\Response(response: 422, description: 'Validation error'),
            new OA\Response(response: 500, description: 'Server error')
        ]
    )]
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'crop' => 'required|string|max:255',
            'area' => 'required|numeric|min:0',
            'polygon' => 'required|array|min:3',
            'polygon.*' => 'array|size:2',
            'location' => 'required|array',
            'location.lat' => 'required|numeric',
            'location.lng' => 'required|numeric',
            'owner' => 'nullable|string|max:255',
            'plantingDate' => 'nullable|date',
            'harvestDate' => 'nullable|date',
            'farm_id' => 'nullable|string', // Optional farm UUID
        ]);

        try {
            // Set user's JWT token if available
            $token = $request->bearerToken();
            if ($token) {
                $this->farmCalendar->setUserToken($token);
            }

            // Convert polygon coordinates to WKT POLYGON format
            $wktCoords = array_map(function ($coord) {
                return $coord[1] . ' ' . $coord[0]; // lng lat format for WKT
            }, $validated['polygon']);

            // Close the polygon if not already closed
            if ($wktCoords[0] !== end($wktCoords)) {
                $wktCoords[] = $wktCoords[0];
            }

            $wktPolygon = 'POLYGON((' . implode(', ', $wktCoords) . '))';

            // Transform to FarmCalendar FarmParcel format with all required fields
            $parcelData = [
                '@type' => 'Parcel',
                'identifier' => $validated['name'], // Using name as identifier
                'description' => isset($validated['owner']) ? "Parcel {$validated['name']} owned by {$validated['owner']}" : "Parcel {$validated['name']}",
                'validFrom' => $validated['plantingDate'] ?? now()->toISOString(),
                'validTo' => $validated['harvestDate'] ?? now()->addYear()->toISOString(),
                'area' => (string)$validated['area'],
                'hasIrrigationFlow' => '0.00',
                'category' => $validated['crop'],
                'inRegion' => 'Unknown',
                'hasToponym' => $validated['name'],
                'isNitroArea' => false,
                'isNatura2000Area' => false,
                'isPdopgArea' => false,
                'isIrrigated' => false,
                'isCultivatedInLevels' => false,
                'isGroundSlope' => false,
                'hasGeometry' => [
                    '@type' => 'Geometry',
                    'asWKT' => $wktPolygon,
                ],
                'location' => [
                    '@type' => 'Point',
                    'lat' => $validated['location']['lat'],
                    'long' => $validated['location']['lng'],
                ],
            ];

            // Add farm reference if provided, otherwise use user's first farm
            if (isset($validated['farm_id'])) {
                $parcelData['farm'] = [
                    '@type' => 'Farm',
                    '@id' => 'urn:farmcalendar:Farm:' . $validated['farm_id'],
                ];
            } else {
                // Get user's first available farm
                try {
                    $farms = $this->farmCalendar->getFarms();
                    if (!empty($farms) && isset($farms[0]['@id'])) {
                        $parcelData['farm'] = [
                            '@type' => 'Farm',
                            '@id' => $farms[0]['@id'],
                        ];
                    } else {
                        // No farms exist for the user – create a dummy farm and use it
                        $user = $request->user();
                        $fullName = trim(($user->first_name ?? '') . ' ' . ($user->last_name ?? '')) ?: 'Default Owner';

                        $dummyFarmPayload = [
                            '@type' => 'Farm',
                            'status' => 1,
                            'deleted_at' => null,
                            'name' => $user->farm_name ?? ($fullName . "'s Farm"),
                            'description' => 'Auto-created farm for first parcel',
                            'administrator' => $fullName,
                            'telephone' => $user->phone ?? '+10000000000',
                            'vatID' => $user->vat_id ?? 'VAT-UNKNOWN',
                            'contactPerson' => [
                                'firstname' => $user->first_name ?? 'Default',
                                'lastname'  => $user->last_name  ?? 'Owner',
                            ],
                            'address' => [
                                'adminUnitL1' => $user->address_region ?? 'Unknown Region',
                                'adminUnitL2' => $user->address_prefecture ?? 'Unknown Prefecture',
                                'addressArea' => $user->address_area ?? 'Unknown Area',
                                'municipality' => $user->municipality ?? 'Unknown Municipality',
                                'community' => $user->community ?? 'Unknown Community',
                                'locatorName' => $user->street ?? 'Unknown',
                            ],
                        ];
                        $createdFarm = $this->farmCalendar->createFarm($dummyFarmPayload);
                        if (!isset($createdFarm['@graph'][0]['@id'])) {
                            Log::error('Dummy farm creation did not return @id', ['response' => $createdFarm]);
                            return response()->json([
                                'error' => 'Failed to create a default farm',
                                'message' => 'Please try again or provide a farm_id explicitly.',
                            ], 500);
                        }

                        $parcelData['farm'] = [
                            '@type' => 'Farm',
                            '@id' => $createdFarm['@graph'][0]['@id'],
                        ];
                    }
                } catch (\Exception $e) {
                    Log::error('Error ensuring user farm before parcel creation', ['error' => $e->getMessage()]);
                    return response()->json([
                        'error' => 'Failed to ensure a farm for this user',
                        'message' => 'Please provide a farm_id in your request or try again later.',
                    ], 500);
                }
            }

            $plot = $this->farmCalendar->createFarmParcel($parcelData);
            $id = ParcelHelper::extractIdFromUrn($plot['@id'] ?? '');
            $sensors = $this->farmCalendar->getAgriculturalMachines([
                'parcel' => $id,
            ]);
            $parcel = $this->transformPlotToParcel($plot, $sensors);

            return response()->json([
                'data' => $parcel,
                'message' => 'Parcel created successfully',
            ], 201);
        } catch (\Illuminate\Http\Client\RequestException $e) {
            // Try to handle common HTTP errors
            $response = $this->handleRequestException($e, 'parcel');
            if ($response) {
                return $response;
            }

            Log::error('FarmCalendar API error creating parcel', [
                'status' => $e->response->status(),
                'body' => $e->response->body(),
            ]);

            return response()->json([
                'error' => 'Failed to create parcel',
                'details' => config('app.debug') ? $e->response->json() : null,
            ], 500);
        } catch (\Exception $e) {
            Log::error('Error creating parcel', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'error' => 'An error occurred while creating parcel',
            ], 500);
        }
    }

    /**
     * Update parcel
     */
    #[OA\Put(
        path: '/api/parcels/{id}',
        description: 'Update an existing farm parcel',
        summary: 'Update parcel',
        security: [['sanctum' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'name', type: 'string', example: 'North Field'),
                    new OA\Property(property: 'crop', type: 'string', example: 'Wheat'),
                    new OA\Property(property: 'area', type: 'number', example: 25.5),
                    new OA\Property(
                        property: 'polygon',
                        type: 'array',
                        items: new OA\Items(
                            type: 'array',
                            items: new OA\Items(type: 'number')
                        )
                    ),
                    new OA\Property(
                        property: 'location',
                        properties: [
                            new OA\Property(property: 'lat', type: 'number'),
                            new OA\Property(property: 'lng', type: 'number')
                        ],
                        type: 'object'
                    ),
                    new OA\Property(property: 'owner', type: 'string', nullable: true),
                    new OA\Property(property: 'plantingDate', type: 'string', format: 'date', nullable: true),
                    new OA\Property(property: 'harvestDate', type: 'string', format: 'date', nullable: true)
                ]
            )
        ),
        tags: ['Parcels'],
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
                description: 'Parcel updated successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'data', type: 'object'),
                        new OA\Property(property: 'message', type: 'string', example: 'Parcel updated successfully')
                    ]
                )
            ),
            new OA\Response(response: 404, description: 'Parcel not found'),
            new OA\Response(response: 422, description: 'Validation error'),
            new OA\Response(response: 500, description: 'Server error')
        ]
    )]
    public function update(Request $request, string $id): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'crop' => 'sometimes|string|max:255',
            'area' => 'sometimes|numeric|min:0',
            'polygon' => 'sometimes|array|min:3',
            'polygon.*' => 'array|size:2',
            'location' => 'sometimes|array',
            'location.lat' => 'required_with:location|numeric',
            'location.lng' => 'required_with:location|numeric',
            'owner' => 'nullable|string|max:255',
            'plantingDate' => 'nullable|date',
            'harvestDate' => 'nullable|date',
        ]);

        try {
            // Set user's JWT token if available
            $token = $request->bearerToken();
            if ($token) {
                $this->farmCalendar->setUserToken($token);
            }

            // First, fetch the existing parcel to get its current structure
            $existingPlot = $this->farmCalendar->getFarmParcel($id);

            // Transform to FarmCalendar FarmParcel format (OCSM/JSON-LD)
            // Start with existing data structure
            $parcelData = [
                '@type' => 'Parcel',
                'identifier' => $existingPlot['identifier'] ?? '',
                'description' => $existingPlot['description'] ?? '',
                'validFrom' => $existingPlot['validFrom'] ?? null,
                'validTo' => $existingPlot['validTo'] ?? null,
                'area' => $existingPlot['area'] ?? '0',
                'hasIrrigationFlow' => $existingPlot['hasIrrigationFlow'] ?? '0.00',
                'category' => $existingPlot['category'] ?? '',
                'inRegion' => $existingPlot['inRegion'] ?? 'Unknown',
                'hasToponym' => $existingPlot['hasToponym'] ?? '',
                'isNitroArea' => $existingPlot['isNitroArea'] ?? false,
                'isNatura2000Area' => $existingPlot['isNatura2000Area'] ?? false,
                'isPdopgArea' => $existingPlot['isPdopgArea'] ?? false,
                'isIrrigated' => $existingPlot['isIrrigated'] ?? false,
                'isCultivatedInLevels' => $existingPlot['isCultivatedInLevels'] ?? false,
                'isGroundSlope' => $existingPlot['isGroundSlope'] ?? false,
                'hasGeometry' => $existingPlot['hasGeometry'] ?? [],
                'location' => $existingPlot['location'] ?? [],
            ];

            // Preserve farm reference if it exists
            if (isset($existingPlot['farm'])) {
                $parcelData['farm'] = $existingPlot['farm'];
            }

            // Preserve crops reference if it exists
            if (isset($existingPlot['hasAgriCrop'])) {
                $parcelData['hasAgriCrop'] = $existingPlot['hasAgriCrop'];
            }

            // Update with new values
            if (isset($validated['name'])) {
                $parcelData['identifier'] = $validated['name'];
                $parcelData['hasToponym'] = $validated['name'];
                // Update description
                $parcelData['description'] = ($validated['owner'] ?? 'Unknown') . " - " . $validated['name'];
            }

            if (isset($validated['crop'])) {
                $parcelData['category'] = $validated['crop'];
            }

            if (isset($validated['area'])) {
                $parcelData['area'] = (string)$validated['area'];
            }

            if (isset($validated['owner'])) {
                $currentName = $validated['name'] ?? $existingPlot['identifier'] ?? '';
                $parcelData['description'] = $validated['owner'] . " - " . $currentName;
            }

            if (isset($validated['plantingDate'])) {
                $parcelData['validFrom'] = $validated['plantingDate'];
            }

            if (isset($validated['harvestDate'])) {
                $parcelData['validTo'] = $validated['harvestDate'];
            }

            if (isset($validated['polygon'])) {
                // Convert polygon coordinates to WKT POLYGON format
                $wktCoords = array_map(function ($coord) {
                    return $coord[1] . ' ' . $coord[0]; // lng lat format for WKT
                }, $validated['polygon']);

                // Close the polygon if not already closed
                if ($wktCoords[0] !== end($wktCoords)) {
                    $wktCoords[] = $wktCoords[0];
                }

                $wktPolygon = 'POLYGON((' . implode(', ', $wktCoords) . '))';

                $parcelData['hasGeometry'] = [
                    '@type' => 'Geometry',
                    'asWKT' => $wktPolygon,
                ];
            }

            if (isset($validated['location'])) {
                $parcelData['location'] = [
                    '@type' => 'Point',
                    'lat' => $validated['location']['lat'],
                    'long' => $validated['location']['lng'],
                ];
            }

            $plot = $this->farmCalendar->updateFarmParcel($id, $parcelData);
            $id = ParcelHelper::extractIdFromUrn($plot['@id'] ?? '');
            $sensors = $this->farmCalendar->getAgriculturalMachines([
                'parcel' => $id,
            ]);
            $parcel = $this->transformPlotToParcel($plot, $sensors);

            return response()->json([
                'data' => $parcel,
                'message' => 'Parcel updated successfully',
            ]);
        } catch (\Illuminate\Http\Client\RequestException $e) {
            if ($e->response->status() === 404) {
                return response()->json([
                    'error' => 'Parcel not found',
                ], 404);
            }

            // Try to handle common HTTP errors
            $response = $this->handleRequestException($e, 'parcel');
            if ($response) {
                return $response;
            }

            Log::error('FarmCalendar API error updating parcel', [
                'status' => $e->response->status(),
                'body' => $e->response->body(),
            ]);

            return response()->json([
                'error' => 'Failed to update parcel',
                'details' => config('app.debug') ? $e->response->json() : null,
            ], 500);
        } catch (\Exception $e) {
            Log::error('Error updating parcel', [
                'id' => $id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'error' => 'An error occurred while updating parcel',
            ], 500);
        }
    }

    /**
     * Delete parcel
     */
    #[OA\Delete(
        path: '/api/parcels/{id}',
        description: 'Delete a farm parcel by its ID',
        summary: 'Delete parcel',
        security: [['sanctum' => []]],
        tags: ['Parcels'],
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
                description: 'Parcel deleted successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'Parcel deleted successfully')
                    ]
                )
            ),
            new OA\Response(response: 404, description: 'Parcel not found'),
            new OA\Response(response: 500, description: 'Server error')
        ]
    )]
    public function destroy(Request $request, string $id): JsonResponse
    {
        try {
            // Set user's JWT token if available
            $token = $request->bearerToken();
            if ($token) {
                $this->farmCalendar->setUserToken($token);
            }

            $this->farmCalendar->deleteFarmParcel($id);

            return response()->json([
                'message' => 'Parcel deleted successfully',
            ]);
        } catch (\Illuminate\Http\Client\RequestException $e) {
            $response = $this->handleRequestException($e, 'parcel');
            if ($response) {
                return $response;
            }

            Log::error('FarmCalendar API error during parcel deletion', [
                'parcel_id' => $id,
                'status' => $e->response->status(),
                'body' => $e->response->body(),
            ]);

            return response()->json([
                'error' => 'Failed to delete parcel',
            ], 500);
        } catch (\Exception $e) {
            Log::error('Error deleting parcel', [
                'id' => $id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'error' => 'An error occurred while deleting parcel',
            ], 500);
        }
    }


}
