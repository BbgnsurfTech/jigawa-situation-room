<?php

namespace Database\Seeders;

use App\Models\Lga;
use App\Models\PollingStation;
use App\Models\Ward;
use Illuminate\Database\Seeder;

class GeolocationSeeder extends Seeder
{
    /**
     * Jigawa State approximate boundaries and LGA center coordinates.
     * Data based on real geographic coordinates.
     */
    private array $lgaCoordinates = [
        // LGA Code => [center_lat, center_lng, lat_range, lng_range]
        'AUY' => [12.3500, 9.5500, 0.15, 0.15],   // Auyo
        'BAB' => [12.7667, 9.0167, 0.20, 0.20],   // Babura
        'BIR' => [12.7833, 10.0667, 0.20, 0.20],  // Birniwa
        'BKU' => [11.4500, 9.4833, 0.25, 0.25],   // Birnin Kudu
        'BUJ' => [11.7500, 9.6000, 0.15, 0.15],   // Buji
        'DUT' => [11.7564, 9.3381, 0.20, 0.20],   // Dutse (State Capital)
        'GAG' => [12.4000, 9.5167, 0.12, 0.12],   // Gagarawa
        'GAR' => [12.1500, 9.4500, 0.18, 0.18],   // Garki
        'GUM' => [12.6333, 9.3833, 0.15, 0.15],   // Gumel
        'GUR' => [12.7667, 10.2667, 0.18, 0.18],  // Guri
        'GWI' => [12.7000, 9.1833, 0.15, 0.15],   // Gwiwa
        'GWR' => [11.2833, 9.8500, 0.22, 0.22],   // Gwaram
        'HAD' => [12.4500, 10.0500, 0.18, 0.18],  // Hadejia
        'JAH' => [12.0500, 9.6333, 0.18, 0.18],   // Jahun
        'KAF' => [12.2333, 9.9333, 0.18, 0.18],   // Kafin Hausa
        'KAU' => [12.8500, 9.8500, 0.20, 0.20],   // Kaugama
        'KAZ' => [12.6500, 8.4333, 0.18, 0.18],   // Kazaure
        'KIR' => [12.8833, 9.5500, 0.18, 0.18],   // Kirika Samma
        'KIY' => [11.5833, 9.6167, 0.15, 0.15],   // Kiyawa
        'MAI' => [12.8167, 9.1500, 0.20, 0.20],   // Maigatari
        'MAD' => [12.5500, 10.4000, 0.20, 0.20],  // Malam Madori
        'MIG' => [12.0833, 9.4667, 0.15, 0.15],   // Miga
        'RIN' => [12.1500, 9.1667, 0.18, 0.18],   // Ringim
        'RON' => [12.5500, 9.0000, 0.15, 0.15],   // Roni
        'SUL' => [12.0500, 10.2500, 0.18, 0.18],  // Sule Tankarkar
        'TAU' => [11.9833, 9.9333, 0.15, 0.15],   // Taura
        'YAN' => [11.9167, 9.2000, 0.15, 0.15],   // Yankwashi
    ];

    public function run(): void
    {
        $this->command->info('Seeding geolocation data...');

        $this->seedLgaBoundaries();
        $this->seedWardBoundaries();
        $this->seedPollingStationCoordinates();

        $this->command->info('Geolocation seeding completed!');
    }

    private function seedLgaBoundaries(): void
    {
        $this->command->info('Adding LGA boundaries...');

        $lgas = Lga::all();
        foreach ($lgas as $lga) {
            $coords = $this->lgaCoordinates[$lga->code] ?? null;

            if (!$coords) {
                continue;
            }

            [$centerLat, $centerLng, $latRange, $lngRange] = $coords;

            // Create a simple polygon boundary for the LGA
            $boundary = $this->createPolygonBoundary($centerLat, $centerLng, $latRange, $lngRange);

            $lga->update(['boundary_geojson' => $boundary]);
        }
    }

    private function seedWardBoundaries(): void
    {
        $this->command->info('Adding ward boundaries...');

        $wards = Ward::with('lga')->get();

        foreach ($wards as $ward) {
            $lgaCoords = $this->lgaCoordinates[$ward->lga->code] ?? null;

            if (!$lgaCoords) {
                continue;
            }

            [$lgaCenterLat, $lgaCenterLng, $lgaLatRange, $lgaLngRange] = $lgaCoords;

            // Create a smaller boundary within the LGA for each ward
            // Offset based on ward index to spread wards across LGA
            $wardIndex = Ward::where('lga_id', $ward->lga_id)
                ->where('id', '<=', $ward->id)
                ->count();

            $totalWards = Ward::where('lga_id', $ward->lga_id)->count();

            // Calculate grid position for the ward
            $gridSize = ceil(sqrt($totalWards));
            $row = floor(($wardIndex - 1) / $gridSize);
            $col = ($wardIndex - 1) % $gridSize;

            $wardLatRange = $lgaLatRange / $gridSize;
            $wardLngRange = $lgaLngRange / $gridSize;

            $wardCenterLat = $lgaCenterLat - ($lgaLatRange / 2) + ($row + 0.5) * $wardLatRange;
            $wardCenterLng = $lgaCenterLng - ($lgaLngRange / 2) + ($col + 0.5) * $wardLngRange;

            $boundary = $this->createPolygonBoundary(
                $wardCenterLat,
                $wardCenterLng,
                $wardLatRange * 0.9,
                $wardLngRange * 0.9
            );

            $ward->update(['boundary_geojson' => $boundary]);
        }
    }

    private function seedPollingStationCoordinates(): void
    {
        $this->command->info('Adding polling station GPS coordinates...');

        $pollingStations = PollingStation::with(['lga', 'ward'])->get();
        $count = 0;

        foreach ($pollingStations as $station) {
            $lgaCoords = $this->lgaCoordinates[$station->lga->code] ?? null;

            if (!$lgaCoords) {
                continue;
            }

            [$lgaCenterLat, $lgaCenterLng, $lgaLatRange, $lgaLngRange] = $lgaCoords;

            // Get ward position within LGA
            $wardIndex = Ward::where('lga_id', $station->lga_id)
                ->where('id', '<=', $station->ward_id)
                ->count();

            $totalWards = Ward::where('lga_id', $station->lga_id)->count();

            $gridSize = ceil(sqrt($totalWards));
            $row = floor(($wardIndex - 1) / $gridSize);
            $col = ($wardIndex - 1) % $gridSize;

            $wardLatRange = $lgaLatRange / $gridSize;
            $wardLngRange = $lgaLngRange / $gridSize;

            $wardCenterLat = $lgaCenterLat - ($lgaLatRange / 2) + ($row + 0.5) * $wardLatRange;
            $wardCenterLng = $lgaCenterLng - ($lgaLngRange / 2) + ($col + 0.5) * $wardLngRange;

            // Add random offset within ward for each polling station
            $lat = $wardCenterLat + (mt_rand(-1000, 1000) / 10000) * $wardLatRange;
            $lng = $wardCenterLng + (mt_rand(-1000, 1000) / 10000) * $wardLngRange;

            $station->update([
                'gps_latitude' => round($lat, 6),
                'gps_longitude' => round($lng, 6),
            ]);

            $count++;
        }

        $this->command->info("Updated coordinates for $count polling stations.");
    }

    private function createPolygonBoundary(float $centerLat, float $centerLng, float $latRange, float $lngRange): array
    {
        // Create a simple rectangular polygon with slight irregularity
        $halfLat = $latRange / 2;
        $halfLng = $lngRange / 2;

        // Add slight variation to make polygons more natural-looking
        $variation = 0.02;

        $coordinates = [
            // Northwest
            [$centerLng - $halfLng + $this->randomVariation($variation), $centerLat + $halfLat - $this->randomVariation($variation)],
            // Northeast
            [$centerLng + $halfLng - $this->randomVariation($variation), $centerLat + $halfLat + $this->randomVariation($variation)],
            // East
            [$centerLng + $halfLng + $this->randomVariation($variation), $centerLat + $this->randomVariation($variation)],
            // Southeast
            [$centerLng + $halfLng - $this->randomVariation($variation), $centerLat - $halfLat + $this->randomVariation($variation)],
            // Southwest
            [$centerLng - $halfLng + $this->randomVariation($variation), $centerLat - $halfLat - $this->randomVariation($variation)],
            // West
            [$centerLng - $halfLng - $this->randomVariation($variation), $centerLat - $this->randomVariation($variation)],
            // Close the polygon (back to first point)
            [$centerLng - $halfLng + $this->randomVariation($variation), $centerLat + $halfLat - $this->randomVariation($variation)],
        ];

        return [
            'type' => 'Polygon',
            'coordinates' => [$coordinates],
        ];
    }

    private function randomVariation(float $max): float
    {
        return (mt_rand(-100, 100) / 100) * $max;
    }
}
