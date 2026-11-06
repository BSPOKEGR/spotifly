<?php

namespace App\Console\Commands;

use App\Libraries\ParcelHelper;
use App\Services\AlertService;
use App\Services\FarmCalendarService;
use App\Services\PestDiseaseService;
use Carbon\Carbon;
use Carbon\Month;
use Illuminate\Console\Command;

class FetchAlertsCommand extends Command
{
    protected $signature = 'fetch:alerts';

    protected $description = 'Command description';

    public function __construct(
        private readonly AlertService $alertService,
        private readonly FarmCalendarService $farmCalendar,
        private readonly PestDiseaseService $pestDiseaseService,
    ) {
        parent::__construct();
    }

    public function handle(): void
    {
        $this->info('Fetching parcels for alerts...');
        $parcels = $this->farmCalendar->getFarmParcels();

        if (empty($parcels)) {
            $this->warn('No parcels found.');
            return;
        }

        foreach ($parcels as $parcel) {
            $parcelId = ParcelHelper::extractIdFromUrn($parcel['@id'] ?? '');
            $this->info("Processing parcel ID: $parcelId - {$parcel['identifier']}");
            $alerts = $this->fetchAlertsForParcel($parcel);

            $this->alertService->saveAlertsForParcel($parcelId, $alerts);
        }
    }

    private function fetchAlertsForParcel(mixed $parcel): array
    {
        // Parse WKT POLYGON to coordinates array
        $polygon = ParcelHelper::parseWKTPolygon($parcel['hasGeometry']['asWKT'] ?? '');

        // Calculate centroid for location
        $location = ParcelHelper::calculateCentroid($polygon);

        return $this->pestDiseaseService->getPestAlerts(
            $location,
            Carbon::today(),
        );
    }
}
