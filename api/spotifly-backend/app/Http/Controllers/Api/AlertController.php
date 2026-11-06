<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Libraries\ParcelHelper;
use App\Services\AlertService;
use App\Services\FarmCalendarService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use OpenApi\Attributes as OA;

class AlertController extends BaseController
{
    public function __construct(
        private readonly AlertService $alertService,
        private readonly FarmCalendarService $farmCalendar
    ) {}

    /**
     * Get all alerts, optionally filtered by parcel
     */
    #[OA\Get(
        path: '/api/alerts',
        description: 'Retrieve pest and disease alerts, optionally filtered by parcel',
        summary: 'Get all alerts',
        security: [['sanctum' => []]],
        tags: ['Alerts'],
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
                                    new OA\Property(property: 'id', type: 'string'),
                                    new OA\Property(property: 'parcelId', type: 'string', nullable: true),
                                    new OA\Property(property: 'parcelName', type: 'string', nullable: true),
                                    new OA\Property(property: 'date', type: 'string', format: 'date-time'),
                                    new OA\Property(property: 'type', type: 'string', example: 'pest'),
                                    new OA\Property(property: 'severity', type: 'string', example: 'high'),
                                    new OA\Property(property: 'message', type: 'string'),
                                    new OA\Property(property: 'read', type: 'boolean')
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
        $parcelMap = $this->farmCalendar->getFarmParcels();

        // get all parcel ids and names in a map
        $parcels = [];
        foreach ($parcelMap as $parcel) {
            $parcels[ParcelHelper::extractIdFromUrn($parcel['@id'])] = $parcel['identifier'];
        }

        // get alert models for the logged in user, optionally filtered by parcel
        $alerts = $this->alertService->getAlertsForParcels($parcels);

        return response()->json(['data' => $alerts]);
    }

    /**
     * Get a 10-day alert timeline
     */
    #[OA\Get(
        path: '/api/alerts/timeline',
        description: 'Returns one entry per day for the last 10 days. Each day either lists its alerts or reports no alerts.',
        summary: 'Get 10-day alert timeline',
        security: [['sanctum' => []]],
        tags: ['Alerts'],
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
                                    new OA\Property(property: 'date', type: 'string', format: 'date'),
                                    new OA\Property(property: 'alerts', type: 'array', nullable: true, items: new OA\Items(type: 'object')),
                                    new OA\Property(property: 'message', type: 'string', nullable: true, example: 'No alerts for this day'),
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
    public function timeline(Request $request): JsonResponse
    {
        $parcelMap = $this->farmCalendar->getFarmParcels();

        $parcels = [];
        foreach ($parcelMap as $parcel) {
            $parcels[ParcelHelper::extractIdFromUrn($parcel['@id'])] = $parcel['identifier'];
        }

        $timeline = $this->alertService->getAlertTimelineForParcels($parcels);

        return response()->json(['data' => $timeline]);
    }

    /**
     * Acknowledge an alert
     */
    #[OA\Post(
        path: '/api/alerts/{id}/acknowledge',
        description: 'Mark an alert as acknowledged',
        summary: 'Acknowledge alert',
        security: [['sanctum' => []]],
        tags: ['Alerts'],
        parameters: [
            new OA\Parameter(
                name: 'id',
                description: 'Alert ID',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'string')
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Alert acknowledged successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'Alert acknowledged successfully')
                    ]
                )
            ),
            new OA\Response(response: 500, description: 'Server error')
        ]
    )]
    public function acknowledge(Request $request, string $id): JsonResponse
    {
        $this->alertService->acknowledgeAlert($id);

        return response()->json(['message' => 'Alert acknowledged successfully']);
    }

    /**
     * Dismiss an alert
     */
    #[OA\Post(
        path: '/api/alerts/{id}/dismiss',
        description: 'Mark an alert as dismissed',
        summary: 'Dismiss alert',
        security: [['sanctum' => []]],
        tags: ['Alerts'],
        parameters: [
            new OA\Parameter(
                name: 'id',
                description: 'Alert ID',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'string')
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Alert dismissed successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'Alert dismissed successfully')
                    ]
                )
            ),
            new OA\Response(response: 500, description: 'Server error')
        ]
    )]
    public function dismiss(Request $request, string $id): JsonResponse
    {
        try {
            Cache::put("alert_{$id}_status", 'dismissed', now()->addDays(30));
            return response()->json(['message' => 'Alert dismissed successfully']);
        } catch (\Exception $e) {
            Log::error('Error dismissing alert', ['id' => $id, 'error' => $e->getMessage()]);
            return response()->json(['error' => 'An error occurred while dismissing alert'], 500);
        }
    }
}
