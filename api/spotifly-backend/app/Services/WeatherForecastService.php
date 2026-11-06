<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\ParcelWeatherForecastRepository;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;

class WeatherForecastService
{
    public function __construct(
        private ParcelWeatherForecastRepository $forecastRepository
    ) {}

    /**
     * Get hourly forecast from database for a parcel
     */
    public function getHourlyForecast(string $parcelId): array
    {
        $startDate = Carbon::now();
        $endDate = Carbon::now()->addDays(5);

        $forecasts = $this->forecastRepository->getByParcelAndDateRange(
            $parcelId,
            $startDate,
            $endDate
        );

        if ($forecasts->isEmpty()) {
            throw new \RuntimeException('No forecast data available for this parcel');
        }

        return $this->formatHourlyDatabaseForecasts($forecasts, $parcelId);
    }

    /**
     * Get daily forecast from database for a parcel
     */
    public function getDailyForecast(string $parcelId): array
    {
        $startDate = Carbon::now()->startOfDay();
        $endDate = Carbon::now()->addDays(5)->endOfDay();

        $forecasts = $this->forecastRepository->getByParcelAndDateRange(
            $parcelId,
            $startDate,
            $endDate
        );

        if ($forecasts->isEmpty()) {
            throw new \RuntimeException('No forecast data available for this parcel');
        }

        return $this->formatDailyDatabaseForecasts($forecasts, $parcelId);
    }

    /**
     * Format database forecasts to hourly API response format
     */
    private function formatHourlyDatabaseForecasts(Collection $forecasts, string $parcelId): array
    {
        $grouped = [
            'ambient_temperature' => ['data' => []],
            'ambient_humidity' => ['data' => []],
            'wind_speed' => ['data' => []],
            'wind_direction' => ['data' => []],
            'precipitation' => ['data' => []],
        ];

        foreach ($forecasts as $forecast) {
            $timestamp = $forecast->forecast_time->toIso8601String();

            $grouped['ambient_temperature']['data'][$timestamp] = $forecast->ambient_temperature;
            $grouped['ambient_humidity']['data'][$timestamp] = $forecast->ambient_humidity;
            $grouped['wind_speed']['data'][$timestamp] = $forecast->wind_speed;
            $grouped['wind_direction']['data'][$timestamp] = $forecast->wind_direction;
            $grouped['precipitation']['data'][$timestamp] = $forecast->precipitation;
        }

        return [
            'data' => $grouped,
            'source' => 'database',
            'parcel_id' => $parcelId,
            'records_count' => $forecasts->count(),
        ];
    }

    /**
     * Format database forecasts to daily aggregated API response format
     */
    private function formatDailyDatabaseForecasts(Collection $forecasts, string $parcelId): array
    {
        $dailyData = [];

        foreach ($forecasts as $forecast) {
            $date = $forecast->forecast_time->toDateString();

            if (!isset($dailyData[$date])) {
                $dailyData[$date] = [
                    'temperatures' => [],
                    'humidity' => [],
                    'wind_speeds' => [],
                    'precipitation' => [],
                ];
            }

            $dailyData[$date]['temperatures'][] = $forecast->ambient_temperature;
            $dailyData[$date]['humidity'][] = $forecast->ambient_humidity;
            $dailyData[$date]['wind_speeds'][] = $forecast->wind_speed;
            $dailyData[$date]['precipitation'][] = $forecast->precipitation;
        }

        $result = [];
        foreach ($dailyData as $date => $data) {
            $temps = $data['temperatures'];
            $humidity = $data['humidity'];
            $windSpeeds = $data['wind_speeds'];
            $precipitation = array_filter($data['precipitation'], fn($val) => $val !== null);

            $dayData = [
                'date' => $date,
                'temperature' => [
                    'min' => round(min($temps), 1),
                    'max' => round(max($temps), 1),
                    'mean' => round(array_sum($temps) / count($temps), 1),
                ],
                'humidity' => [
                    'min' => round(min($humidity)),
                    'max' => round(max($humidity)),
                    'mean' => round(array_sum($humidity) / count($humidity)),
                ],
                'wind_speed' => [
                    'min' => round(min($windSpeeds), 1),
                    'max' => round(max($windSpeeds), 1),
                    'mean' => round(array_sum($windSpeeds) / count($windSpeeds), 1),
                ],
                'precipitation' => [
                    'total' => !empty($precipitation) ? round(array_sum($precipitation), 1) : 0,
                    'max' => !empty($precipitation) ? round(max($precipitation), 1) : 0,
                ],
            ];

            $condition = $this->determineWeatherConditionFromAggregates(
                $dayData['temperature'],
                $dayData['humidity'],
                $dayData['precipitation']
            );

            $dayData['icon'] = $condition['icon'];
            $dayData['condition'] = $condition['description'];

            $result[] = $dayData;
        }

        return [
            'data' => $result,
            'source' => 'database',
            'parcel_id' => $parcelId,
        ];
    }

    /**
     * Determine weather condition from aggregated daily data
     */
    private function determineWeatherConditionFromAggregates(
        array $temperature,
        array $humidity,
        array $precipitation
    ): array {
        $totalPrecip = $precipitation['total'];
        $maxPrecip = $precipitation['max'];
        $avgTemp = $temperature['mean'];
        $avgHumidity = $humidity['mean'];

        if ($totalPrecip > 5 || $maxPrecip > 2) {
            if ($avgTemp < 2) {
                return ['icon' => 'snow', 'description' => 'Snow'];
            }
            return ['icon' => 'rain', 'description' => 'Rainy'];
        }

        if ($totalPrecip > 0.5 || $maxPrecip > 0.2) {
            return ['icon' => 'cloud-rain', 'description' => 'Light Rain'];
        }

        if ($avgHumidity > 85) {
            return ['icon' => 'cloud', 'description' => 'Cloudy'];
        }

        if ($avgHumidity > 70) {
            return ['icon' => 'cloud-sun', 'description' => 'Partly Cloudy'];
        }

        return ['icon' => 'sun', 'description' => 'Clear'];
    }
}
