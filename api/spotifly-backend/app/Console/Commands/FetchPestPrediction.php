<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Libraries\ParcelHelper;
use App\Services\FarmCalendarService;
use App\Services\PestPredictionService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class FetchPestPrediction extends Command
{
    protected $signature = 'fetch:pest-prediction';

    protected $description = 'Fetch pest predictions from ML API for all parcels and store in database';

    public function __construct(
        private readonly PestPredictionService $predictionService,
        private readonly FarmCalendarService $farmCalendar
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $this->info('Fetching parcels for pest predictions...');
        $parcels = $this->farmCalendar->getFarmParcels();

        if (empty($parcels)) {
            $this->warn('No parcels found.');
            return Command::FAILURE;
        }

        $this->info('Found ' . count($parcels) . ' parcels.');

        $successCount = 0;
        $failureCount = 0;

        // Loop through each parcel
        foreach ($parcels as $parcel) {
            $parcelId = ParcelHelper::extractIdFromUrn($parcel['@id'] ?? '');
            $parcelName = $parcel['identifier'] ?? 'Unknown';

            $this->info("Processing parcel: $parcelName (ID: $parcelId)");

            try {
                $prediction = $this->predictionService->fetchAndStorePrediction($parcelId);

                if ($prediction) {
                    $this->line("  ✓ Prediction fetched successfully (ID: {$prediction->id})");
                    $successCount++;

                    Log::info('Pest prediction fetched for parcel', [
                        'parcel_id' => $parcelId,
                        'parcel_name' => $parcelName,
                        'prediction_id' => $prediction->id,
                    ]);
                } else {
                    $this->error("  ✗ Failed to fetch prediction");
                    $failureCount++;
                }
            } catch (\Exception $e) {
                $this->error("  ✗ Exception: {$e->getMessage()}");
                $failureCount++;

                Log::error('Exception in pest prediction fetch for parcel', [
                    'parcel_id' => $parcelId,
                    'parcel_name' => $parcelName,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $this->newLine();
        $this->info("Summary: $successCount successful, $failureCount failed");

        return $failureCount === 0 ? Command::SUCCESS : Command::FAILURE;
    }
}
