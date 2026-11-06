<?php

namespace App\Jobs;

use App\Repositories\ParcelWeatherForecastRepository;
use App\Services\WeatherService;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class FetchParcelWeatherForecast implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        private string $parcelId,
        private float $lat,
        private float $lon
    ) {}

    public function handle(
        WeatherService $weatherService,
        ParcelWeatherForecastRepository $repository
    ): void {
        try {
            Log::info("Fetching weather for parcel {$this->parcelId}");

            // Fetch hourly forecast for 5 days
            $forecastData = $weatherService->getForecastHourly5Days($this->lat, $this->lon);
            $groupedData = $this->transformWeatherData($forecastData);
            $timeSeriesData = $this->transformToTimeSeries($groupedData);

            // Store each timestamp's weather data
            foreach ($timeSeriesData as $timestamp => $weatherData) {
                $forecastTime = Carbon::parse($timestamp);

                // Use repository to create or update forecast
                $repository->upsertForecast(
                    $this->parcelId,
                    $forecastTime,
                    $weatherData
                );
            }

            Log::info("✓ Weather data stored for parcel {$this->parcelId}", [
                'records_stored' => count($timeSeriesData)
            ]);

        } catch (\Exception $e) {
            Log::error("Failed to fetch weather for parcel {$this->parcelId}", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            throw $e; // Re-throw to mark job as failed
        }
    }

    public function failed(\Throwable $exception): void
    {
        Log::error("Weather fetch job failed for parcel {$this->parcelId}", [
            'error' => $exception->getMessage()
        ]);
    }

    private function transformWeatherData(array $forecastData): array
    {
        $grouped = [];

        foreach ($forecastData as $item) {
            $measurementType = $item['measurement_type'] ?? null;
            $timestamp = $item['timestamp'] ?? null;
            $value = $item['value'] ?? null;

            // Skip items without required fields
            if (!$measurementType || !$timestamp || $value === null) {
                continue;
            }

            // Initialize the measurement type group if not exists
            if (!isset($grouped[$measurementType])) {
                $grouped[$measurementType] = ['data' => []];
            }

            // Add timestamp => value mapping
            $grouped[$measurementType]['data'][$timestamp] = $value;
        }

        return $grouped;
    }

    private function transformToTimeSeries(array $forecastData): array
    {
        $timeSeries = [];

        foreach ($forecastData as $measurementType => $measurementData) {
            // Skip if no data array
            if (!isset($measurementData['data']) || !is_array($measurementData['data'])) {
                continue;
            }

            // Iterate through each timestamp in this measurement type
            foreach ($measurementData['data'] as $timestamp => $value) {
                // Initialize timestamp entry if not exists
                if (!isset($timeSeries[$timestamp])) {
                    $timeSeries[$timestamp] = [];
                }

                // Add this measurement to the timestamp
                $timeSeries[$timestamp][$measurementType] = $value;
            }
        }

        return $timeSeries;
    }
}

