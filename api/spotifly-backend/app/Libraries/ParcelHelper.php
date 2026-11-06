<?php

declare(strict_types=1);

namespace App\Libraries;

class ParcelHelper
{

    /**
     * Extract numeric ID from URN format
     * @param string $urn
     * @return string|null
     */
    public static function extractIdFromUrn(string $urn): ?string
    {
        if (str_contains($urn, ':')) {
            $parts = explode(':', $urn);
            return end($parts);
        }
        return $urn ?: null;
    }

    /**
     * Calculate centroid of polygon
     *
     * @param array<int, array<int, float>> $polygon
     * @return array{lat: float, lng: float}
     */
    public static function calculateCentroid(array $polygon): array
    {
        if (empty($polygon)) {
            return ['lat' => 0.0, 'lng' => 0.0];
        }

        $latSum = 0.0;
        $lngSum = 0.0;
        $count = count($polygon);

        foreach ($polygon as $coord) {
            $latSum += $coord[0];
            $lngSum += $coord[1];
        }

        return [
            'lat' => $latSum / $count,
            'lng' => $lngSum / $count,
        ];
    }

    /**
     * Parse WKT POLYGON string to coordinate array
     * WKT format: POLYGON((lng lat, lng lat, ...))
     *
     * @param string $wkt
     * @return array<int, array<int, float>>
     */
    public static function parseWKTPolygon(string $wkt): array
    {
        // Remove "POLYGON((" and "))"
        $wkt = trim($wkt);
        if (!preg_match('/POLYGON\(\((.*)\)\)/', $wkt, $matches)) {
            return [];
        }

        $coordString = $matches[1];
        $coordPairs = explode(',', $coordString);

        $polygon = [];
        foreach ($coordPairs as $pair) {
            $coords = preg_split('/\s+/', trim($pair));
            if (count($coords) >= 2) {
                // Convert from lng,lat to [lat, lng] for our format
                $polygon[] = [(float)$coords[1], (float)$coords[0]];
            }
        }

        return $polygon;
    }
}
