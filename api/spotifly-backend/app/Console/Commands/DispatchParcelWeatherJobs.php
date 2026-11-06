<?php

namespace App\Console\Commands;

//use App\Jobs\FetchParcelWeatherForecast;
use App\Jobs\FetchParcelWeatherForecast;
use App\Libraries\ParcelHelper;
use App\Services\FarmCalendarService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class DispatchParcelWeatherJobs extends Command
{
    protected $signature = 'weather:dispatch-parcel-jobs';
    protected $description = 'Dispatch async weather fetch jobs for all parcels';

    public function __construct(
        private FarmCalendarService $farmCalendar
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $this->info('Dispatching weather fetch jobs for all parcels...');

        try {
            // Fetch all parcels
            $parcels = $this->farmCalendar->getFarmParcels();

            if (empty($parcels)) {
                $this->warn('No parcels found.');
                return self::SUCCESS;
            }

            $dispatched = 0;

            foreach ($parcels as $parcel) {
                $parcelId = ParcelHelper::extractIdFromUrn($parcel['@id']) ?? null;
                $location = $parcel['location'] ?? null;

                if (!$parcelId || !$location) {
                    continue;
                }

                $lat = $location['lat'] ?? null;
                $lon = $location['long'] ?? null;

                if (!$lat || !$lon) {
                    continue;
                }

                // Dispatch async job for this parcel
                FetchParcelWeatherForecast::dispatch($parcelId, $lat, $lon);
                $dispatched++;
                break;
            }

            $this->info("✓ Dispatched {$dispatched} weather fetch jobs");
            Log::info("Dispatched weather jobs for {$dispatched} parcels");

            return self::SUCCESS;

        } catch (\Exception $e) {
            $this->error('Failed to dispatch weather jobs: ' . $e->getMessage());
            Log::error('Weather job dispatch failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return self::FAILURE;
        }
    }
}
