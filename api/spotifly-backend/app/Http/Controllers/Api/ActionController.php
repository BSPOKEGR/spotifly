<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Models\ActionMetadata;
use App\Services\FarmCalendarService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use OpenApi\Attributes as OA;

class ActionController extends BaseController
{
    public function __construct(
        private FarmCalendarService $farmCalendar
    ) {}

    /**
     * Extract action ID (UUID) from a FarmCalendar activity array.
     */
    private function extractActionId(array $activity): string
    {
        $id = $activity['@id'] ?? $activity['id'] ?? '';
        if (str_contains($id, ':')) {
            $parts = explode(':', $id);
            $id = end($parts);
        }
        return (string)$id;
    }

    /**
     * Build a map of action_id => metadata array from a collection of ActionMetadata records.
     *
     * @param \Illuminate\Database\Eloquent\Collection $records
     * @return array<string, array<string, mixed>>
     */
    private function buildMetadataMap($records): array
    {
        $map = [];
        foreach ($records as $record) {
            $map[$record->action_id] = $record->metadata;
        }
        return $map;
    }

    /**
     * Extract metadata fields from the request based on action type.
     * Fields that are already stored in the FarmCalendar (date, parcel) are excluded.
     */
    private function extractMetadataFromRequest(array $validated, string $type): array
    {
        $plantProtectionFields = [
            'cultivation_period', 'time', 'target_insect', 'active_ingredient',
            'dosage', 'unit', 'spray_solution_volume', 'fuel', 'materials', 'labor', 'other',
        ];

        $observationFields = [
            'cultivation_period', 'time', 'olive_fruit_fly',
            'olive_fruit_fly_larvae_observations', 'olive_fruit_fly_adult_captures',
            'olive_molth', 'olive_molth_larvae_observations', 'olive_molth_adult_captures',
        ];

        $fields = match ($type) {
            'plant_protection' => $plantProtectionFields,
            'observation' => $observationFields,
            default => [],
        };

        $metadata = [];
        foreach ($fields as $field) {
            if (array_key_exists($field, $validated)) {
                $metadata[$field] = $validated[$field];
            }
        }
        return $metadata;
    }

    /**
     * Get all actions, optionally filtered by parcel
     */
    #[OA\Get(
        path: '/api/actions',
        description: 'Retrieve all farm actions/activities, optionally filtered by parcel',
        summary: 'Get all actions',
        security: [['sanctum' => []]],
        tags: ['Actions'],
        parameters: [
            new OA\Parameter(
                name: 'parcel_id',
                description: 'Filter actions by parcel ID',
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
                            type: 'array',
                            items: new OA\Items(
                                properties: [
                                    new OA\Property(property: 'id', type: 'integer'),
                                    new OA\Property(property: 'type', type: 'string', example: 'Irrigation'),
                                    new OA\Property(property: 'date', type: 'string', format: 'date'),
                                    new OA\Property(property: 'parcelId', type: 'string'),
                                    new OA\Property(property: 'description', type: 'string'),
                                    new OA\Property(property: 'completedAt', type: 'string', format: 'date-time', nullable: true),
                                    new OA\Property(property: 'metadata', type: 'object')
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
            $activities = $this->farmCalendar->getFarmCalendarActivities();
            $parcelMap = $this->farmCalendar->getFarmParcelsMap();

            // Build metadata map from DB
            $metadataRecords = ActionMetadata::all();
            $metadataMap = $this->buildMetadataMap($metadataRecords);

            $actions = $this->transformActivitiesToActions($activities, $parcelMap, $metadataMap);

            // Only return actions that are linked to a parcel
            $actions = array_values(array_filter($actions, fn($action) => !empty($action['parcelId'])));

            // Filter by parcel if specified
            $parcelId = $request->query('parcel_id');
            if ($parcelId) {
                $actions = array_values(array_filter(
                    $actions,
                    fn($action) => $action['parcelId'] === $parcelId
                ));
            }

            return response()->json(['data' => $actions]);
        } catch (\Illuminate\Http\Client\RequestException $e) {
            $handled = $this->handleRequestException($e, 'actions');
            if ($handled) {
                return $handled;
            }

            Log::error('Error fetching actions', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return response()->json(['error' => 'An error occurred while fetching actions'], 500);
        } catch (\Exception $e) {
            Log::error('Error fetching actions', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return response()->json(['error' => 'An error occurred while fetching actions'], 500);
        }
    }

    /**
     * Get single action by ID
     */
    #[OA\Get(
        path: '/api/actions/{id}',
        description: 'Retrieve a single farm action/activity by its ID',
        summary: 'Get action by ID',
        security: [['sanctum' => []]],
        tags: ['Actions'],
        parameters: [
            new OA\Parameter(
                name: 'id',
                description: 'Action ID',
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
                                new OA\Property(property: 'id', type: 'integer'),
                                new OA\Property(property: 'type', type: 'string', example: 'Irrigation'),
                                new OA\Property(property: 'date', type: 'string', format: 'date'),
                                new OA\Property(property: 'parcelId', type: 'string'),
                                new OA\Property(property: 'description', type: 'string'),
                                new OA\Property(property: 'completedAt', type: 'string', format: 'date-time', nullable: true),
                                new OA\Property(property: 'metadata', type: 'object')
                            ],
                            type: 'object'
                        )
                    ]
                )
            ),
            new OA\Response(response: 404, description: 'Action not found'),
            new OA\Response(response: 500, description: 'Server error')
        ]
    )]
    public function show(Request $request, string $id): JsonResponse
    {
        try {
            $activity = $this->farmCalendar->getFarmCalendarActivity($id);
            $parcelMap = $this->farmCalendar->getFarmParcelsMap();

            $metadataRecord = ActionMetadata::where('action_id', $id)->first();
            $metadata = $metadataRecord?->metadata;

            $action = $this->transformActivityToAction(
                $activity,
                ['parcel' => isset($activity['hasAgriParcel']['@id']) ? $parcelMap[$activity['hasAgriParcel']['@id']] ?? null : null],
                $metadata
            );

            return response()->json(['data' => $action]);
        } catch (\Illuminate\Http\Client\RequestException $e) {
            $handled = $this->handleRequestException($e, 'action', ['id' => $id]);
            if ($handled) {
                return $handled;
            }

            Log::error('Error fetching action', ['id' => $id, 'error' => $e->getMessage()]);
            return response()->json(['error' => 'Failed to fetch action'], 500);
        } catch (\Exception $e) {
            Log::error('Error fetching action', ['id' => $id, 'error' => $e->getMessage()]);
            return response()->json(['error' => 'An error occurred while fetching action'], 500);
        }
    }

    /**
     * Create new action
     */
    #[OA\Post(
        path: '/api/actions',
        description: 'Create a new farm action/activity',
        summary: 'Create action',
        security: [['sanctum' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['parcelId', 'type', 'title', 'scheduledDate'],
                properties: [
                    new OA\Property(property: 'parcelId', description: 'Parcel ID', type: 'string'),
                    new OA\Property(property: 'type', type: 'string', enum: ['plant_protection', 'observation'], example: 'plant_protection'),
                    new OA\Property(property: 'title', type: 'string', maxLength: 255, example: 'Apply plant protection'),
                    new OA\Property(property: 'description', type: 'string', nullable: true),
                    new OA\Property(property: 'scheduledDate', type: 'string', format: 'date', example: '2025-11-15'),
                    new OA\Property(property: 'responsible', type: 'string', maxLength: 255, nullable: true),
                    // plant_protection metadata
                    new OA\Property(property: 'cultivation_period', type: 'string', nullable: true),
                    new OA\Property(property: 'time', type: 'string', nullable: true),
                    new OA\Property(property: 'target_insect', type: 'string', nullable: true),
                    new OA\Property(property: 'active_ingredient', type: 'string', nullable: true),
                    new OA\Property(property: 'dosage', type: 'string', nullable: true),
                    new OA\Property(property: 'unit', type: 'string', nullable: true),
                    new OA\Property(property: 'spray_solution_volume', type: 'string', nullable: true),
                    new OA\Property(property: 'fuel', type: 'string', nullable: true),
                    new OA\Property(property: 'materials', type: 'string', nullable: true),
                    new OA\Property(property: 'labor', type: 'string', nullable: true),
                    new OA\Property(property: 'other', type: 'string', nullable: true),
                    // observation metadata
                    new OA\Property(property: 'olive_fruit_fly', type: 'boolean', nullable: true),
                    new OA\Property(property: 'olive_fruit_fly_larvae_observations', type: 'string', nullable: true),
                    new OA\Property(property: 'olive_fruit_fly_adult_captures', type: 'string', nullable: true),
                    new OA\Property(property: 'olive_molth', type: 'boolean', nullable: true),
                    new OA\Property(property: 'olive_molth_larvae_observations', type: 'string', nullable: true),
                    new OA\Property(property: 'olive_molth_adult_captures', type: 'string', nullable: true),
                ]
            )
        ),
        tags: ['Actions'],
        responses: [
            new OA\Response(
                response: 201,
                description: 'Action created successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'data', type: 'object'),
                        new OA\Property(property: 'message', type: 'string', example: 'Action created successfully')
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
            'parcelId' => 'required|string',
            'type' => 'required|string|in:plant_protection,observation',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'scheduledDate' => 'required|date',
            'responsible' => 'nullable|string|max:255',
            // Shared metadata fields
            'cultivation_period' => 'nullable|string',
            'time' => 'nullable|string',
            // plant_protection-specific fields
            'target_insect' => 'nullable|string',
            'active_ingredient' => 'nullable|string',
            'dosage' => 'nullable|string',
            'unit' => 'nullable|string',
            'spray_solution_volume' => 'nullable|string',
            'fuel' => 'nullable|string',
            'materials' => 'nullable|string',
            'labor' => 'nullable|string',
            'other' => 'nullable|string',
            // observation-specific fields
            'olive_fruit_fly' => 'nullable|boolean',
            'olive_fruit_fly_larvae_observations' => 'nullable|string',
            'olive_fruit_fly_adult_captures' => 'nullable|string',
            'olive_molth' => 'nullable|boolean',
            'olive_molth_larvae_observations' => 'nullable|string',
            'olive_molth_adult_captures' => 'nullable|string',
        ]);

        try {
            $activityTypeUrnMap = [
                'plant_protection' => 'urn:farmcalendar:FarmCalendarActivityType:00000000-0000-0000-0000-000000000001',
                'observation' => 'urn:farmcalendar:FarmCalendarActivityType:00000000-0000-0000-0000-000000000005',
            ];

            $activityData = [
                '@type' => 'FarmCalendarActivity',
                'activityType' => [
                    '@type' => 'FarmCalendarActivityType',
                    '@id' => $activityTypeUrnMap[$validated['type']] ?? 'urn:farmcalendar:FarmCalendarActivityType:00000000-0000-0000-0000-000000000000'
                ],
                'title' => $validated['title'],
                'hasStartDatetime' => $validated['scheduledDate'] . 'T00:00:00Z',
                'hasAgriParcel' => 'urn:ngsi-ld:AgriParcel:' . $validated['parcelId'],
                'usesAgriculturalMachinery' => [],
                'description' => $validated['description'] ?? $validated['title'],
            ];

            if (isset($validated['responsible'])) {
                $activityData['responsibleAgent'] = $validated['responsible'];
            }

            $activity = $this->farmCalendar->createFarmCalendarActivity($activityData);
            $actionId = $this->extractActionId($activity);

            // Save type-specific metadata to DB
            $metadata = $this->extractMetadataFromRequest($validated, $validated['type']);
            $metadata['action_type'] = $validated['type'];

            ActionMetadata::updateOrCreate(
                ['action_id' => $actionId],
                ['metadata' => $metadata]
            );

            $action = $this->transformActivityToAction($activity, $validated, $metadata);

            return response()->json([
                'data' => $action,
                'message' => 'Action created successfully',
            ], 201);
        } catch (\Illuminate\Http\Client\RequestException $e) {
            $handled = $this->handleRequestException($e, 'action');
            if ($handled) {
                return $handled;
            }

            Log::error('FarmCalendar API error', [
                'status' => $e->response->status(),
                'body' => $e->response->body(),
            ]);
            return response()->json([
                'error' => 'Failed to create action',
                'details' => $e->response->json(),
            ], $e->response->status());
        } catch (\Exception $e) {
            Log::error('Error creating action', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'An error occurred while creating action'], 500);
        }
    }

    /**
     * Update action
     */
    #[OA\Put(
        path: '/api/actions/{id}',
        description: 'Update an existing farm action/activity',
        summary: 'Update action',
        security: [['sanctum' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'title', type: 'string', maxLength: 255),
                    new OA\Property(property: 'description', type: 'string'),
                    new OA\Property(property: 'scheduledDate', type: 'string', format: 'date'),
                    new OA\Property(property: 'responsible', type: 'string', maxLength: 255),
                    new OA\Property(property: 'completedDate', type: 'string', format: 'date'),
                    // plant_protection metadata
                    new OA\Property(property: 'cultivation_period', type: 'string', nullable: true),
                    new OA\Property(property: 'time', type: 'string', nullable: true),
                    new OA\Property(property: 'target_insect', type: 'string', nullable: true),
                    new OA\Property(property: 'active_ingredient', type: 'string', nullable: true),
                    new OA\Property(property: 'dosage', type: 'string', nullable: true),
                    new OA\Property(property: 'unit', type: 'string', nullable: true),
                    new OA\Property(property: 'spray_solution_volume', type: 'string', nullable: true),
                    new OA\Property(property: 'fuel', type: 'string', nullable: true),
                    new OA\Property(property: 'materials', type: 'string', nullable: true),
                    new OA\Property(property: 'labor', type: 'string', nullable: true),
                    new OA\Property(property: 'other', type: 'string', nullable: true),
                    // observation metadata
                    new OA\Property(property: 'olive_fruit_fly', type: 'boolean', nullable: true),
                    new OA\Property(property: 'olive_fruit_fly_larvae_observations', type: 'string', nullable: true),
                    new OA\Property(property: 'olive_fruit_fly_adult_captures', type: 'string', nullable: true),
                    new OA\Property(property: 'olive_molth', type: 'boolean', nullable: true),
                    new OA\Property(property: 'olive_molth_larvae_observations', type: 'string', nullable: true),
                    new OA\Property(property: 'olive_molth_adult_captures', type: 'string', nullable: true),
                ]
            )
        ),
        tags: ['Actions'],
        parameters: [
            new OA\Parameter(
                name: 'id',
                description: 'Action ID',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'string')
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Action updated successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'data', type: 'object'),
                        new OA\Property(property: 'message', type: 'string', example: 'Action updated successfully')
                    ]
                )
            ),
            new OA\Response(response: 404, description: 'Action not found'),
            new OA\Response(response: 422, description: 'Validation error'),
            new OA\Response(response: 500, description: 'Server error')
        ]
    )]
    public function update(Request $request, string $id): JsonResponse
    {
        $validated = $request->validate([
            'title' => 'sometimes|string|max:255',
            'description' => 'sometimes|string',
            'scheduledDate' => 'sometimes|date',
            'responsible' => 'sometimes|string|max:255',
            'completedDate' => 'sometimes|date',
            // plant_protection metadata
            'cultivation_period' => 'sometimes|nullable|string',
            'time' => 'sometimes|nullable|string',
            'target_insect' => 'sometimes|nullable|string',
            'active_ingredient' => 'sometimes|nullable|string',
            'dosage' => 'sometimes|nullable|string',
            'unit' => 'sometimes|nullable|string',
            'spray_solution_volume' => 'sometimes|nullable|string',
            'fuel' => 'sometimes|nullable|string',
            'materials' => 'sometimes|nullable|string',
            'labor' => 'sometimes|nullable|string',
            'other' => 'sometimes|nullable|string',
            // observation metadata
            'olive_fruit_fly' => 'sometimes|nullable|boolean',
            'olive_fruit_fly_larvae_observations' => 'sometimes|nullable|string',
            'olive_fruit_fly_adult_captures' => 'sometimes|nullable|string',
            'olive_molth' => 'sometimes|nullable|boolean',
            'olive_molth_larvae_observations' => 'sometimes|nullable|string',
            'olive_molth_adult_captures' => 'sometimes|nullable|string',
        ]);

        try {
            $token = $request->bearerToken();
            if ($token) {
                $this->farmCalendar->setUserToken($token);
            }

            $existingActivity = $this->farmCalendar->getFarmCalendarActivity($id);

            $activityData = [
                '@type' => $existingActivity['@type'] ?? 'FarmCalendarActivity',
                'activityType' => $existingActivity['activityType'] ?? [],
                'hasAgriParcel' => $existingActivity['hasAgriParcel'] ?? '',
                'usesAgriculturalMachinery' => $existingActivity['usesAgriculturalMachinery'] ?? [],
            ];

            if (isset($validated['scheduledDate'])) {
                $activityData['hasStartDatetime'] = $validated['scheduledDate'] . 'T00:00:00Z';
            } else {
                $activityData['hasStartDatetime'] = $existingActivity['hasStartDatetime'] ?? '';
            }

            if (isset($validated['description'])) {
                $activityData['details'] = $validated['description'];
            } elseif (isset($existingActivity['description'])) {
                $activityData['details'] = $existingActivity['description'];
            }

            if (isset($validated['title'])) {
                $activityData['title'] = $validated['title'];
            } elseif (isset($existingActivity['title'])) {
                $activityData['title'] = $existingActivity['title'];
            }

            if (isset($validated['responsible'])) {
                $activityData['responsibleAgent'] = $validated['responsible'];
            } elseif (isset($existingActivity['responsibleAgent'])) {
                $activityData['responsibleAgent'] = $existingActivity['responsibleAgent'];
            }

            if (isset($validated['completedDate'])) {
                $activityData['hasEndDatetime'] = $validated['completedDate'] . 'T23:59:59Z';
            } elseif (isset($existingActivity['hasEndDatetime'])) {
                $activityData['hasEndDatetime'] = $existingActivity['hasEndDatetime'];
            }

            $activity = $this->farmCalendar->updateFarmCalendarActivity($id, $activityData);

            // Update metadata in DB: merge new values over existing ones
            $existingRecord = ActionMetadata::where('action_id', $id)->first();
            $actionType = $existingRecord?->metadata['action_type'] ?? null;
            $existingMeta = $existingRecord?->metadata ?? [];

            $newMetaFields = $actionType
                ? $this->extractMetadataFromRequest($validated, $actionType)
                : [];

            $mergedMetadata = array_merge($existingMeta, $newMetaFields);

            if (!empty($mergedMetadata)) {
                ActionMetadata::updateOrCreate(
                    ['action_id' => $id],
                    ['metadata' => $mergedMetadata]
                );
            }

            $action = $this->transformActivityToAction($activity, $validated, $mergedMetadata ?: null);

            return response()->json([
                'data' => $action,
                'message' => 'Action updated successfully',
            ]);
        } catch (\Illuminate\Http\Client\RequestException $e) {
            $handled = $this->handleRequestException($e, 'action', ['id' => $id]);
            if ($handled) {
                return $handled;
            }

            Log::error('FarmCalendar API error', [
                'status' => $e->response->status(),
                'body' => $e->response->body(),
            ]);
            return response()->json([
                'error' => 'Failed to update action',
                'details' => $e->response->json(),
            ], $e->response->status());
        } catch (\Exception $e) {
            Log::error('Error updating action', ['id' => $id, 'error' => $e->getMessage()]);
            return response()->json(['error' => 'An error occurred while updating action'], 500);
        }
    }

    /**
     * Delete action
     */
    #[OA\Delete(
        path: '/api/actions/{id}',
        description: 'Delete a farm action/activity',
        summary: 'Delete action',
        security: [['sanctum' => []]],
        tags: ['Actions'],
        parameters: [
            new OA\Parameter(
                name: 'id',
                description: 'Action ID',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'string')
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Action deleted successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'Action deleted successfully')
                    ]
                )
            ),
            new OA\Response(response: 404, description: 'Action not found'),
            new OA\Response(response: 500, description: 'Server error')
        ]
    )]
    public function destroy(Request $request, string $id): JsonResponse
    {
        try {
            $token = $request->bearerToken();
            if ($token) {
                $this->farmCalendar->setUserToken($token);
            }

            $this->farmCalendar->deleteFarmCalendarActivity($id);

            // Remove local metadata
            ActionMetadata::where('action_id', $id)->delete();

            return response()->json(['message' => 'Action deleted successfully']);
        } catch (\Illuminate\Http\Client\RequestException $e) {
            $handled = $this->handleRequestException($e, 'action', ['id' => $id]);
            if ($handled) {
                return $handled;
            }

            Log::error('FarmCalendar API error', [
                'status' => $e->response->status(),
                'body' => $e->response->body(),
            ]);
            return response()->json(['error' => 'Failed to delete action'], 500);
        } catch (\Exception $e) {
            Log::error('Error deleting action', ['id' => $id, 'error' => $e->getMessage()]);
            return response()->json(['error' => 'An error occurred while deleting action'], 500);
        }
    }

    /**
     * Mark action as completed
     */
    #[OA\Post(
        path: '/api/actions/{id}/complete',
        description: 'Mark a farm action/activity as completed',
        summary: 'Complete action',
        security: [['sanctum' => []]],
        tags: ['Actions'],
        parameters: [
            new OA\Parameter(
                name: 'id',
                description: 'Action ID',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'string')
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Action marked as completed',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'data', type: 'object'),
                        new OA\Property(property: 'message', type: 'string', example: 'Action marked as completed')
                    ]
                )
            ),
            new OA\Response(response: 404, description: 'Action not found'),
            new OA\Response(response: 500, description: 'Server error')
        ]
    )]
    public function complete(string $id): JsonResponse
    {
        try {
            $existingActivity = $this->farmCalendar->getFarmCalendarActivity($id);

            $activityData = [
                '@type' => $existingActivity['@type'] ?? 'FarmCalendarActivity',
                'activityType' => $existingActivity['activityType'] ?? [],
                'hasAgriParcel' => $existingActivity['hasAgriParcel'] ?? '',
                'hasStartDatetime' => $existingActivity['hasStartDatetime'] ?? '',
                'usesAgriculturalMachinery' => $existingActivity['usesAgriculturalMachinery'] ?? [],
                'hasEndDatetime' => now()->toISOString(),
            ];

            if (isset($existingActivity['title'])) {
                $activityData['title'] = $existingActivity['title'];
            }
            if (isset($existingActivity['description'])) {
                $activityData['description'] = $existingActivity['description'];
            }
            if (isset($existingActivity['responsibleAgent'])) {
                $activityData['responsibleAgent'] = $existingActivity['responsibleAgent'];
            }

            $activity = $this->farmCalendar->updateFarmCalendarActivity($id, $activityData);

            $metadataRecord = ActionMetadata::where('action_id', $id)->first();
            $metadata = $metadataRecord?->metadata;

            $action = $this->transformActivityToAction($activity, [], $metadata);

            return response()->json([
                'data' => $action,
                'message' => 'Action completed successfully',
            ]);
        } catch (\Illuminate\Http\Client\RequestException $e) {
            $handled = $this->handleRequestException($e, 'action', ['id' => $id]);
            if ($handled) {
                return $handled;
            }

            Log::error('FarmCalendar API error', [
                'status' => $e->response->status(),
                'body' => $e->response->body(),
            ]);
            return response()->json(['error' => 'Failed to complete action'], 500);
        } catch (\Exception $e) {
            Log::error('Error completing action', ['id' => $id, 'error' => $e->getMessage()]);
            return response()->json(['error' => 'An error occurred while completing action'], 500);
        }
    }
}
