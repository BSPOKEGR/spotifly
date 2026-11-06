<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Libraries\ParcelHelper;

abstract class BaseController extends Controller
{
    /**
     * Transform FarmCalendar parcels array to client parcels array
     *
     * @param array<string, mixed> $plots
     * @return array<int, array<string, mixed>>
     */
    protected function transformPlotsToParcels(array $plots, array $sensors): array
    {
        $sensorMap = [];
        foreach ($sensors as $sensor) {
            $sensorCasted = $this->transformMachine($sensor);
            $parcelId = $sensorCasted['parcelId'] ?? null;
            if ($parcelId) {
                if (!isset($sensorMap[$parcelId])) {
                    $sensorMap[$parcelId] = [];
                }
                $sensorMap[$parcelId][] = $sensorCasted;
            }
        }
        return array_map(
            fn($plot) => $this->transformPlotToParcel($plot, $sensorMap[ParcelHelper::extractIdFromUrn($plot['@id'] ?? '')] ?? []),
            $plots
        );
    }

    /**
     * Transform single FarmCalendar parcel to client parcel format
     *
     * @param array<string, mixed> $plot
     * @return array<string, mixed>
     */
    protected function transformPlotToParcel(array $plot, array $sensors): array
    {
        // Extract ID from URN (urn:farmcalendar:FarmParcel:UUID)
        $id = ParcelHelper::extractIdFromUrn($plot['@id'] ?? '');

        // Parse WKT POLYGON to coordinates array
        $polygon = ParcelHelper::parseWKTPolygon($plot['hasGeometry']['asWKT'] ?? '');

        // Calculate centroid for location
        $location = ParcelHelper::calculateCentroid($polygon);

        // Get crop from category or hasAgriCrop
        $crop = $plot['category'] ?? 'Unknown';
        return [
            'id' => $id,
            'name' => $plot['identifier'] ?? 'Unnamed Parcel',
            'crop' => $crop,
            'area' => (float)($plot['area'] ?? 0),
            'riskLevel' => 'low', // TODO: Calculate from Pest & Disease risk index
            'polygon' => $polygon,
            'location' => $location,
            'owner' => $plot['owner'] ?? 'Unknown',
            'plantingDate' => $plot['sowing_date'] ?? null,
            'harvestDate' => $plot['harvest_date'] ?? null,
            'pdParcelId' => $plot['pdParcelId'] ?? null,
            'sensors' => array_map(fn($sensor) => $this->transformMachine($sensor), $sensors),
        ];
    }


    /**
     * Calculate yield prediction based on risk and moisture
     */
    protected function calculateYieldPrediction(int $riskScore, int $moistureLevel): float
    {
        // Simplified yield prediction algorithm
        // Base yield: 100 units
        $baseYield = 100;

        // Reduce yield based on risk score
        $riskPenalty = $riskScore * 0.5; // 50% reduction at 100 risk score

        // Adjust for moisture (optimal around 60-70%)
        $moisturePenalty = 0;
        if ($moistureLevel < 40) {
            $moisturePenalty = (40 - $moistureLevel) * 0.3; // Dry conditions
        } elseif ($moistureLevel > 80) {
            $moisturePenalty = ($moistureLevel - 80) * 0.2; // Too wet
        }

        $predictedYield = $baseYield - $riskPenalty - $moisturePenalty;

        return max(0, round($predictedYield));
    }

    /**
     * Calculate Growing Degree Days from forecast
     */
    protected function calculateGDD(array $forecast): float
    {
        $gdd = 0;
        $baseTemp = 10; // Base temperature for GDD calculation (°C)

        foreach ($forecast as $item) {
            $temp = $item['temperature'] ?? 20;
            $dailyGDD = max(0, $temp - $baseTemp);
            $gdd += $dailyGDD;
        }

        return round($gdd);
    }

    /**
     * Calculate chilling hours (hours below 7°C)
     */
    protected function calculateChillingHours(array $forecast): int
    {
        $chillingHours = 0;
        $chillingTemp = 7; // Threshold temperature for chilling hours

        foreach ($forecast as $item) {
            $temp = $item['temperature'] ?? 20;
            if ($temp < $chillingTemp) {
                $chillingHours += 3; // Assuming 3-hour intervals
            }
        }

        return $chillingHours;
    }

    /**
     * Transform Weather Service forecast to client WeatherData format
     */
    protected function transformForecastToWeatherData(array $forecastData, float $lat, float $lon): array
    {
        // Weather service returns array of measurements directly, not wrapped
        // Check if it's a direct array of measurements or wrapped
        if (isset($forecastData[0]) && is_array($forecastData[0])) {
            // Direct array of measurements
            $measurements = $forecastData;
        } else {
            // Try wrapped formats
            $measurements = $forecastData['forecast'] ?? $forecastData['list'] ?? [];
        }

        // Group measurements by date to get one forecast per day
        $groupedByDate = [];
        foreach ($measurements as $measurement) {
            $timestamp = $measurement['timestamp'] ?? null;
            if (!$timestamp) continue;

            $date = date('Y-m-d', strtotime($timestamp));

            if (!isset($groupedByDate[$date])) {
                $groupedByDate[$date] = [
                    'date' => $date,
                    'temperatures' => [],
                    'humidities' => [],
                    'wind_speeds' => [],
                    'precipitations' => [],
                ];
            }

            $type = $measurement['measurement_type'] ?? '';
            $value = $measurement['value'] ?? null;

            if ($value !== null) {
                if ($type === 'ambient_temperature') {
                    $groupedByDate[$date]['temperatures'][] = $value;
                } elseif ($type === 'ambient_humidity') {
                    $groupedByDate[$date]['humidities'][] = $value;
                } elseif ($type === 'wind_speed') {
                    $groupedByDate[$date]['wind_speeds'][] = $value;
                } elseif ($type === 'precipitation') {
                    $groupedByDate[$date]['precipitations'][] = $value;
                }
            }
        }

        // Calculate averages for each day
        $grouped = [];
        foreach ($groupedByDate as $date => $data) {
            $grouped[$date] = [
                'date' => $date,
                'temperature' => !empty($data['temperatures']) ? array_sum($data['temperatures']) / count($data['temperatures']) : 20,
                'humidity' => !empty($data['humidities']) ? array_sum($data['humidities']) / count($data['humidities']) : 60,
                'wind_speed' => !empty($data['wind_speeds']) ? array_sum($data['wind_speeds']) / count($data['wind_speeds']) : 10,
                'precipitation' => !empty($data['precipitations']) ? array_sum($data['precipitations']) / count($data['precipitations']) : 0,
            ];
        }

        $forecast = array_values($grouped);

        // Get current/first forecast item for current conditions
        $current = !empty($forecast) ? $forecast[0] : null;

        // Extract current conditions
        $currentTemp = $current['temperature'] ?? 20;
        $currentHumidity = $current['humidity'] ?? 60;
        $currentWind = $current['wind_speed'] ?? 10;
        $currentPrecip = $current['precipitation'] ?? 0;

        // Calculate min/max from forecast
        $temps = array_filter(array_map(fn($item) => $item['temperature'] ?? null, $forecast));

        $minTemp = !empty($temps) ? min($temps) : $currentTemp - 5;
        $maxTemp = !empty($temps) ? max($temps) : $currentTemp + 5;

        // Calculate GDD (Growing Degree Days) - simplified calculation
        $gdd = $this->calculateGDD($forecast);

        // Calculate chilling hours (hours below 7°C) - approximate
        $chillingHours = $this->calculateChillingHours($forecast);

        // Transform forecast items
        $transformedForecast = array_slice(
            array_map(fn($item) => $this->transformForecastItem($item), $forecast),
            0,
            5 // Limit to 5 days
        );

        return [
            'temperature' => [
                'current' => round($currentTemp, 1),
                'min' => round($minTemp, 1),
                'max' => round($maxTemp, 1),
            ],
            'humidity' => round($currentHumidity),
            'windSpeed' => round($currentWind, 1),
            'precipitation' => round($currentPrecip, 1),
            'chillingHours' => $chillingHours,
            'gdd' => $gdd,
            'forecast' => $transformedForecast,
        ];
    }


    /**
     * Transform FarmCalendar activities array to actions array
     *
     * @param array<string, mixed> $activities
     * @param array<string, mixed> $parcelMap
     * @param array<string, array<string, mixed>> $metadataMap action_id => metadata array
     * @return array<int, array<string, mixed>>
     */
    protected function transformActivitiesToActions(array $activities, array $parcelMap, array $metadataMap = []): array
    {
        // Handle different response formats from FarmCalendar API
        // JSON-LD format: {"@graph": [...]}
        // JSON format: [...] or {"results": [...]}
        $activitiesData = $activities;
        if (isset($activities['@graph'])) {
            $activitiesData = $activities['@graph'];
        } elseif (isset($activities['results'])) {
            $activitiesData = $activities['results'];
        }
        if (!is_array($activitiesData)) {
            return [];
        }

        return array_map(function ($activity) use ($parcelMap, $metadataMap) {
            $id = $activity['@id'] ?? $activity['id'] ?? '';
            if (str_contains($id, ':')) {
                $parts = explode(':', $id);
                $id = end($parts);
            }
            $metadata = $metadataMap[(string)$id] ?? null;
            return $this->transformActivityToAction(
                $activity,
                ['parcel' => isset($activity['hasAgriParcel']['@id']) ? $parcelMap[$activity['hasAgriParcel']['@id']] ?? null : null],
                $metadata
            );
        }, $activitiesData);
    }

    /**
     * Transform single FarmCalendar activity to action format
     *
     * @param array<string, mixed> $activity
     * @param array<string, mixed> $additional
     * @param array<string, mixed>|null $metadata
     * @return array<string, mixed>
     */
    protected function transformActivityToAction(array $activity, array $additional = [], ?array $metadata = null): array
    {
        // Extract ID from @id URN format or use id field
        $id = $activity['@id'] ?? $activity['id'] ?? '';
        if (str_contains($id, ':')) {
            $parts = explode(':', $id);
            $id = end($parts);
        }

        // Extract parcel ID from hasAgriParcel or parcel field
        $parcelId = '';
        if (isset($activity['hasAgriParcel']['@id'])) {
            $parcelIdFull = $activity['hasAgriParcel']['@id'];
            $parts = explode(':', $parcelIdFull);
            $parcelId = end($parts);
        } elseif (isset($activity['parcel'])) {
            $parcelId = $activity['parcel'];
        } elseif (isset($additional['parcelId'])) {
            $parcelId = $additional['parcelId'];
        }
        if (isset($additional['parcel'])) {
            $parcel = $additional['parcel'];
        }

        if (isset($activity['activityType']) && isset($activity['activityType']['@id'])) {
            $activityTypeUrnMap = [
                'spraying' => 'urn:farmcalendar:FarmCalendarActivityType:00000000-0000-0000-0000-000000000001',
                'irrigation' => 'urn:farmcalendar:FarmCalendarActivityType:00000000-0000-0000-0000-000000000002',
                'fertilization' => 'urn:farmcalendar:FarmCalendarActivityType:00000000-0000-0000-0000-000000000003',
                'harvesting' => 'urn:farmcalendar:FarmCalendarActivityType:00000000-0000-0000-0000-000000000004',
                'monitoring' => 'urn:farmcalendar:FarmCalendarActivityType:00000000-0000-0000-0000-000000000005',
            ];

            $activityType = $activity['activityType']['@id'];

            $activityType = array_search($activityType, $activityTypeUrnMap);

            if (!$activityType) {
                $activityType = 'other';
            }
        } else {
            $activityType = $activity['activityType']['@id'];
        }

        return [
            'id' => (string)$id,
            'parcelId' => (string)$parcelId,
            'parcel' => $parcel ?? null,
            'type' => $activityType,
            'title' => $activity['title'] ?? $activity['details'] ?? 'Unnamed Action',
            'description' => $activity['details'] ?? $activity['description'] ?? '',
            'scheduledDate' => $activity['hasStartDatetime'] ?? $activity['date'] ?? now()->toISOString(),
            'completedDate' => $activity['hasEndDatetime'] ?? $activity['completed_date'] ?? null,
            'responsible' => $activity['responsibleAgent'] ?? $activity['responsible'] ?? $additional['responsible'] ?? 'Unassigned',
            'linkedAlerts' => [],
            'metadata' => $this->castMetadataFields($metadata ?? []),
        ];
    }


    private function castMetadataFields(array $metadata): array
    {
        foreach (['olive_fruit_fly', 'olive_molth'] as $field) {
            if (array_key_exists($field, $metadata) && $metadata[$field] !== null) {
                $metadata[$field] = (bool) $metadata[$field];
            }
        }
        return $metadata;
    }

    /**
     * Calculate severity level from risk index
     */
    protected function calculateSeverity(float $riskIndex): string
    {
        if ($riskIndex >= 61) {
            return 'high';
        } elseif ($riskIndex >= 31) {
            return 'medium';
        } else {
            return 'low';
        }
    }

    /**
     * Calculate trend from risk index (simplified)
     */
    protected function calculateTrend(float $riskIndex): string
    {
        // Simplified logic - in reality would compare with historical data
        if ($riskIndex >= 70) {
            return 'increasing';
        } elseif ($riskIndex <= 20) {
            return 'decreasing';
        } else {
            return 'stable';
        }
    }

    /**
     * Calculate severity from risk index
     */
    protected function calculateAlertSeverity(float $riskIndex): string
    {
        if ($riskIndex >= 86) {
            return 'critical';
        } elseif ($riskIndex >= 61) {
            return 'high';
        } elseif ($riskIndex >= 31) {
            return 'medium';
        } else {
            return 'low';
        }
    }

    /**
     * Transform single forecast item to client format
     */
    protected function transformForecastItem(array $item): array
    {
        // Handle both weather service format and grouped format
        $temp = $item['temperature'] ?? 20;
        $tempMin = $temp - 3;
        $tempMax = $temp + 3;
        $precipitation = $item['precipitation'] ?? 0;

        // Extract weather condition - default for now since service doesn't provide
        $weatherMain = 'Clear';
        $weatherDesc = 'clear sky';

        // Get date
        $date = $item['date'] ?? date('Y-m-d');

        return [
            'date' => $date,
            'temperature' => [
                'min' => round($tempMin, 1),
                'max' => round($tempMax, 1),
                'avg' => round($temp, 1),
            ],
            'humidity' => round($item['humidity'] ?? 50),
            'windSpeed' => round($item['wind_speed'] ?? 0, 1),
            'condition' => ucfirst($weatherDesc),
            'icon' => $this->getWeatherIcon($weatherMain),
            'precipitation' => round($precipitation, 1),
        ];
    }

    /**
     * Get weather icon emoji based on condition
     */
    protected function getWeatherIcon(string $condition): string
    {
        return match (strtolower($condition)) {
            'clear' => '☀️',
            'clouds', 'cloudy' => '☁️',
            'rain', 'drizzle' => '🌧️',
            'thunderstorm' => '⛈️',
            'snow' => '❄️',
            'mist', 'fog', 'haze' => '🌫️',
            default => '⛅',
        };
    }

    protected function transformMachine(array $machine): array
    {
        return [
            '@id' => $machine['@id'] ?? null,
            '@type' => $machine['@type'] ?? 'AgriculturalMachine',
            'id' => ParcelHelper::extractIdFromUrn($machine['@id'] ?? ''),
            'name' => $machine['name'] ?? 'Unknown Machine',
            'description' => $machine['description'] ?? '',
            'machineType' => $machine['machineType'] ?? null,
            'manufacturer' => $machine['manufacturer'] ?? null,
            'model' => $machine['model'] ?? null,
            'serialNumber' => $machine['serialNumber'] ?? null,
            'acquisitionDate' => $machine['acquisitionDate'] ?? null,
            'isActive' => $machine['isActive'] ?? true,
            'parcelId' => isset($machine['hasAgriParcel']['@id']) ? ParcelHelper::extractIdFromUrn($machine['hasAgriParcel']['@id']) : null,
        ];
    }

    /**
     * Handle HTTP request exceptions and return appropriate JSON responses
     *
     * @param \Illuminate\Http\Client\RequestException $e
     * @param string $operation Operation name for logging
     * @param array $context Additional context for logging
     * @return \Illuminate\Http\JsonResponse|null Returns JsonResponse for handled errors, null to continue
     */
    protected function handleRequestException(
        \Illuminate\Http\Client\RequestException $e,
        string $operation,
        array $context = []
    ): ?\Illuminate\Http\JsonResponse {
        if (!$e->response) {
            return null;
        }

        $status = $e->response->status();

        // Handle 401 Unauthorized (no/missing authentication)
        if ($status === 401) {
            return response()->json([
                'error' => 'Unauthenticated',
                'message' => 'Authentication required'
            ], 401);
        }

        // Handle 403 Forbidden (invalid/expired token)
        if ($status === 403) {
            return response()->json([
                'error' => 'Forbidden',
                'message' => 'Invalid or expired token'
            ], 403);
        }

        // Handle 404 Not Found
        if ($status === 404) {
            return response()->json([
                'error' => ucfirst(strtolower($operation)) . ' not found'
            ], 404);
        }

        // Handle 422 Validation Error
        if ($status === 422) {
            $errors = $e->response->json('errors') ?? [];
            return response()->json([
                'error' => 'Validation error',
                'errors' => $errors
            ], 422);
        }

        // For other errors, return null to let caller handle it
        return null;
    }
}
