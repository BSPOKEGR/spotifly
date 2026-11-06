<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\ParcelWeatherForecast;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;

/**
 * @extends BaseRepository<ParcelWeatherForecast>
 */
class ParcelWeatherForecastRepository extends BaseRepository
{
    public function __construct()
    {
        parent::__construct(ParcelWeatherForecast::class);
    }

    /**
     * Get all forecasts for a specific parcel
     *
     * @param string $parcelId
     * @return Collection<int, ParcelWeatherForecast>
     */
    public function getByParcelId(string $parcelId): Collection
    {
        return $this->where(['parcel_id' => $parcelId]);
    }

    /**
     * Get forecast for a specific parcel and date
     *
     * @param string $parcelId
     * @param Carbon $forecastTime
     * @return ParcelWeatherForecast|null
     */
    public function getByParcelAndTime(string $parcelId, Carbon $forecastTime): ?ParcelWeatherForecast
    {
        return $this->model
            ->where('parcel_id', $parcelId)
            ->where('forecast_time', $forecastTime)
            ->first();
    }

    /**
     * Get the latest forecast for a parcel
     *
     * @param string $parcelId
     * @return ParcelWeatherForecast|null
     */
    public function getLatestForParcel(string $parcelId): ?ParcelWeatherForecast
    {
        return $this->model
            ->where('parcel_id', $parcelId)
            ->orderBy('forecast_time', 'desc')
            ->first();
    }

    /**
     * Get forecasts for a parcel within a date range
     *
     * @param string $parcelId
     * @param Carbon $startDate
     * @param Carbon $endDate
     * @return Collection<int, ParcelWeatherForecast>
     */
    public function getByParcelAndDateRange(string $parcelId, Carbon $startDate, Carbon $endDate): Collection
    {
        /** @var Collection<int, ParcelWeatherForecast> $forecasts */
        $forecasts = $this->model
            ->where('parcel_id', $parcelId)
            ->whereBetween('forecast_time', [$startDate, $endDate])
            ->orderBy('forecast_time', 'asc')
            ->get();

        return $forecasts;
    }

    /**
     * Create or update forecast for a parcel at a specific time
     *
     * @param string $parcelId
     * @param Carbon $forecastTime
     * @param array<string, mixed> $data
     * @return ParcelWeatherForecast
     */
    public function upsertForecast(string $parcelId, Carbon $forecastTime, array $data): ParcelWeatherForecast
    {
        $forecast = $this->getByParcelAndTime($parcelId, $forecastTime);

        if ($forecast) {
            $forecast->update($data);
            return $forecast;
        }

        return $this->create(array_merge($data, [
            'parcel_id' => $parcelId,
            'forecast_time' => $forecastTime,
        ]));
    }

    /**
     * Delete old forecasts older than specified date
     *
     * @param Carbon $beforeDate
     * @return int Number of deleted records
     */
    public function deleteOldForecasts(Carbon $beforeDate): int
    {
        return $this->model
            ->where('forecast_time', '<', $beforeDate)
            ->delete();
    }

    /**
     * Get all forecasts for multiple parcels
     *
     * @param array<string> $parcelIds
     * @return Collection<int, ParcelWeatherForecast>
     */
    public function getByParcelIds(array $parcelIds): Collection
    {
        /** @var Collection<int, ParcelWeatherForecast> $forecasts */
        $forecasts = $this->model
            ->whereIn('parcel_id', $parcelIds)
            ->orderBy('parcel_id')
            ->orderBy('forecast_time', 'asc')
            ->get();

        return $forecasts;
    }
}
