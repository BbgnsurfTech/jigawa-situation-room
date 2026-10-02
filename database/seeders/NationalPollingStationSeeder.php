<?php

namespace Database\Seeders;

use App\Models\Lga;
use App\Models\PollingStation;
use App\Models\State;
use App\Models\Ward;
use Illuminate\Database\Seeder;

/**
 * Seeder for generating polling stations for all Nigerian states.
 * Uses generated data for realistic polling station distribution.
 *
 * Call this INSTEAD of PollingStationSeeder for national deployment.
 * For Jigawa-only, the original PollingStationSeeder uses official INEC data.
 */
class NationalPollingStationSeeder extends Seeder
{
    private array $stationPrefixes = [
        'Primary School',
        'Islamiyya School',
        'Community School',
        'Dispensary',
        'Health Centre',
        'Town Hall',
        'Village Square',
        'Market Square',
        'Central Primary School',
        'Model Primary School',
        'Community Centre',
        'Open Space',
    ];

    private array $stationSuffixes = [
        'I', 'II', 'III', 'A', 'B', 'C',
        'Gabas', 'Yamma', 'Arewa', 'Kudu', 'Tsakiya',
        'East', 'West', 'North', 'South', 'Central',
    ];

    private int $totalStations = 0;

    public function run(): void
    {
        $this->command->info('Seeding national polling stations...');

        $states = State::with(['lgas.wards'])->orderBy('name')->get();

        foreach ($states as $stateIndex => $state) {
            $this->command->info("[" . ($stateIndex + 1) . "/{$states->count()}] Processing {$state->name}...");
            $stateStations = 0;

            foreach ($state->lgas as $lga) {
                foreach ($lga->wards as $ward) {
                    $count = $this->seedWardPollingStations($ward, $lga);
                    $stateStations += $count;
                }
            }

            $this->totalStations += $stateStations;
            $this->command->info("  Created {$stateStations} polling stations for {$state->name}");
        }

        $this->command->info("National polling station seeding completed!");
        $this->command->info("Total polling stations created: {$this->totalStations}");
    }

    /**
     * Generate polling stations for a ward.
     * Each ward gets 5-20 polling stations based on typical Nigerian ward sizes.
     */
    private function seedWardPollingStations(Ward $ward, Lga $lga): int
    {
        // Skip if ward already has polling stations
        if (PollingStation::where('ward_id', $ward->id)->exists()) {
            return 0;
        }

        // Determine number of stations (realistic range for Nigerian wards)
        // Urban areas tend to have more stations
        $isUrban = $this->isLikelyUrban($lga->name);
        $numStations = $isUrban
            ? fake()->numberBetween(10, 20)
            : fake()->numberBetween(5, 15);

        $stations = [];
        $now = now();

        for ($i = 1; $i <= $numStations; $i++) {
            $stationName = $this->generateStationName($ward->name, $i, $numStations);

            $stations[] = [
                'name' => $stationName,
                'code' => $ward->code . '-' . str_pad($i, 2, '0', STR_PAD_LEFT),
                'ward_id' => $ward->id,
                'lga_id' => $lga->id,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        // Batch insert
        foreach (array_chunk($stations, 50) as $chunk) {
            PollingStation::insert($chunk);
        }

        return $numStations;
    }

    /**
     * Generate a realistic polling station name.
     */
    private function generateStationName(string $wardName, int $index, int $total): string
    {
        // Clean ward name for use in station name
        $cleanWardName = preg_replace('/[^A-Za-z\s]/', '', $wardName);
        $cleanWardName = trim(preg_replace('/\s+/', ' ', $cleanWardName));

        // Get first word of ward name
        $wardWords = explode(' ', $cleanWardName);
        $shortWardName = $wardWords[0] ?? 'Central';

        // Choose a prefix
        $prefix = fake()->randomElement($this->stationPrefixes);

        // Add suffix if multiple stations
        $suffix = '';
        if ($total > 1) {
            if ($index <= 3) {
                $suffix = ' ' . ['I', 'II', 'III'][$index - 1];
            } elseif ($index <= 8) {
                $suffix = ' ' . fake()->randomElement(['A', 'B', 'C', 'Gabas', 'Yamma', 'Arewa', 'Kudu']);
            } else {
                $suffix = ' ' . chr(64 + min($index, 26)); // A, B, C...
            }
        }

        // Combine parts
        return strtoupper($shortWardName) . ' ' . $prefix . $suffix;
    }

    /**
     * Check if LGA is likely urban (state capitals, major cities).
     */
    private function isLikelyUrban(string $lgaName): bool
    {
        $urbanIndicators = [
            'Municipal', 'Metro', 'City', 'Urban', 'Central',
            'North', 'South', 'East', 'West', // Often LGA divisions of cities
        ];

        foreach ($urbanIndicators as $indicator) {
            if (stripos($lgaName, $indicator) !== false) {
                return true;
            }
        }

        // Known major city LGAs
        $majorCityLgas = [
            'Ikeja', 'Lagos Island', 'Lagos Mainland', 'Surulere', 'Eti-Osa',
            'Kano Municipal', 'Ibadan North', 'Ibadan South', 'Port Harcourt',
            'Abuja Municipal', 'Kaduna North', 'Kaduna South', 'Enugu North',
            'Benin', 'Warri', 'Jos North', 'Jos South', 'Maiduguri',
        ];

        foreach ($majorCityLgas as $city) {
            if (stripos($lgaName, $city) !== false) {
                return true;
            }
        }

        return false;
    }
}
