<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\PestPrediction;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PestPredictionService
{
    private string $mlApiUrl;

    public function __construct()
    {
        $this->mlApiUrl = config('services.ml_api.url', 'http://localhost:8000');
    }

    /**
     * Fetch prediction from ML API and store in database
     */
    public function fetchAndStorePrediction(string $parcelId): ?PestPrediction
    {
        try {
            $response = Http::timeout(30)
                ->post("{$this->mlApiUrl}/predict/async_bugs", [
                    'id' => $parcelId,
                ]);

            if ($response->successful()) {
                $data = $response->json();

                return PestPrediction::create([
                    'parcel_id' => $parcelId,
                    'response_data' => $data,
                    'fetched_at' => now(),
                ]);
            }

            Log::error('ML API request failed', [
                'parcel_id' => $parcelId,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return null;
        } catch (\Exception $e) {
            Log::error('Error fetching ML prediction', [
                'parcel_id' => $parcelId,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Get the latest prediction from database
     */
    public function getLatestPrediction(?string $parcelId = null): ?PestPrediction
    {
        $query = PestPrediction::orderBy('fetched_at', 'desc');

        if ($parcelId) {
            $query->where('parcel_id', $parcelId);
        }

        return $query->first();
    }

    /**
     * Get all predictions ordered by date
     */
    public function getAllPredictions(int $limit = 10, ?string $parcelId = null): array
    {
        $query = PestPrediction::orderBy('fetched_at', 'desc')->limit($limit);

        if ($parcelId) {
            $query->where('parcel_id', $parcelId);
        }

        return $query->get()->toArray();
    }

    /**
     * Get predictions for multiple parcels
     */
    public function getPredictionsForParcels(array $parcelIds, int $limit = 1): array
    {
        return PestPrediction::whereIn('parcel_id', $parcelIds)
            ->orderBy('parcel_id')
            ->orderBy('fetched_at', 'desc')
            ->get()
            ->groupBy('parcel_id')
            ->map(fn($predictions) => $predictions->take($limit))
            ->flatten()
            ->toArray();
    }
}
