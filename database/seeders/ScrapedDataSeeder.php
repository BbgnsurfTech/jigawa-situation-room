<?php

namespace Database\Seeders;

use App\Models\Lga;
use App\Models\PollingStation;
use App\Models\State;
use App\Models\Ward;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;

/**
 * Seeder for importing scraped ward and polling station data from integrity.ng.
 *
 * Data files are expected in storage/app/scraped/:
 * - wards_{state_name}.json - Ward data for each state
 * - units_{state_name}.json - Polling unit data for each state
 *
 * Run with: php artisan db:seed --class=ScrapedDataSeeder
 * Or for a single state: php artisan db:seed --class=ScrapedDataSeeder -- --state=jigawa
 */
class ScrapedDataSeeder extends Seeder
{
    private int $wardsCreated = 0;
    private int $wardsUpdated = 0;
    private int $unitsCreated = 0;
    private int $lgasCreated = 0;

    public function run(): void
    {
        $specificState = $this->command->option('state') ?? env('SEEDER_STATE');

        $scrapedDir = storage_path('app/scraped');

        if (!File::isDirectory($scrapedDir)) {
            $this->command->error("Scraped data directory not found: {$scrapedDir}");
            $this->command->info("Run 'php artisan scrape:all-states' first to collect data.");
            return;
        }

        if ($specificState) {
            // Seed a single state
            $this->seedState(strtoupper($specificState));
        } else {
            // Seed all available states
            $this->seedAllStates($scrapedDir);
        }

        $this->printSummary();
    }

    private function seedAllStates(string $scrapedDir): void
    {
        // Get all ward files and extract state names
        $wardFiles = File::glob("{$scrapedDir}/wards_*.json");

        $states = [];
        foreach ($wardFiles as $file) {
            $filename = basename($file);
            $stateName = str_replace(['wards_', '.json'], '', $filename);
            $stateName = strtoupper(str_replace('_', ' ', $stateName));
            $states[$stateName] = $file;
        }

        ksort($states);

        $this->command->info("Found " . count($states) . " states with scraped data");
        $this->command->newLine();

        foreach ($states as $stateName => $wardFile) {
            $this->seedState($stateName);
        }
    }

    private function seedState(string $stateName): void
    {
        $stateSlug = strtolower(str_replace(' ', '_', $stateName));
        $wardsFile = storage_path("app/scraped/wards_{$stateSlug}.json");
        $unitsFile = storage_path("app/scraped/units_{$stateSlug}.json");

        if (!File::exists($wardsFile)) {
            $this->command->warn("No wards file found for {$stateName}");
            return;
        }

        if (!File::exists($unitsFile)) {
            $this->command->warn("No units file found for {$stateName}");
            return;
        }

        // Check file sizes (skip empty files)
        $wardsData = json_decode(File::get($wardsFile), true);
        $unitsData = json_decode(File::get($unitsFile), true);

        if (empty($wardsData) || empty($unitsData)) {
            $this->command->warn("Empty data files for {$stateName}, skipping...");
            return;
        }

        $this->command->info("Seeding {$stateName}: " . count($wardsData) . " wards, " . count($unitsData) . " polling units");

        // Find or create state
        $state = $this->findOrCreateState($stateName);
        if (!$state) {
            $this->command->error("Could not find or create state: {$stateName}");
            return;
        }

        // Create LGA and Ward lookup tables for this state
        $lgaLookup = $this->buildLgaLookup($state, $wardsData);
        $wardLookup = $this->seedWards($wardsData, $lgaLookup);

        // Seed polling stations
        $this->seedPollingStations($unitsData, $wardLookup, $lgaLookup);

        $this->command->info("  Completed {$stateName}");
    }

    private function findOrCreateState(string $stateName): ?State
    {
        // Try to find existing state by name
        $state = State::where('name', 'LIKE', $stateName)->first();

        if ($state) {
            return $state;
        }

        // Try alternate name formats
        $alternates = [
            strtoupper($stateName),
            ucwords(strtolower($stateName)),
            $stateName,
        ];

        foreach ($alternates as $name) {
            $state = State::where('name', 'LIKE', "%{$name}%")->first();
            if ($state) {
                return $state;
            }
        }

        // Special handling for FCT
        if (str_contains($stateName, 'FCT') || str_contains($stateName, 'ABUJA')) {
            $state = State::where('name', 'LIKE', '%FCT%')
                         ->orWhere('name', 'LIKE', '%Abuja%')
                         ->first();
            if ($state) {
                return $state;
            }
        }

        $this->command->warn("State not found in database: {$stateName}. Creating...");

        // Create the state with a generated code
        $code = $this->generateStateCode($stateName);
        return State::create([
            'name' => $stateName,
            'code' => $code,
        ]);
    }

    private function generateStateCode(string $stateName): string
    {
        // Generate 2-3 character code from state name
        $words = explode(' ', $stateName);
        if (count($words) > 1) {
            return strtoupper(substr($words[0], 0, 1) . substr($words[1], 0, 1));
        }
        return strtoupper(substr($stateName, 0, 3));
    }

    private function buildLgaLookup(State $state, array $wardsData): array
    {
        $lgaLookup = [];
        $uniqueLgas = collect($wardsData)->pluck('lga')->unique();

        foreach ($uniqueLgas as $lgaName) {
            $lgaName = trim($lgaName);

            // Try to find existing LGA for this state
            $lga = Lga::where('state_id', $state->id)
                      ->where(function ($q) use ($lgaName) {
                          $q->where('name', $lgaName)
                            ->orWhere('name', 'LIKE', "%{$lgaName}%");
                      })
                      ->first();

            if (!$lga) {
                // Create new LGA
                $lga = Lga::create([
                    'name' => $lgaName,
                    'code' => $this->generateLgaCode($state->code ?? '', $lgaName),
                    'state_id' => $state->id,
                ]);
                $this->lgasCreated++;
            }

            $lgaLookup[strtoupper($lgaName)] = $lga;
        }

        return $lgaLookup;
    }

    private function generateLgaCode(string $stateCode, string $lgaName): string
    {
        // Generate code like JIG-DUT for Jigawa, Dutse
        $words = explode(' ', $lgaName);
        $lgaPart = strtoupper(substr($words[0], 0, 3));
        return $stateCode . '-' . $lgaPart;
    }

    private function seedWards(array $wardsData, array $lgaLookup): array
    {
        $wardLookup = [];
        $wardCounters = []; // Track ward numbers per LGA for unique codes

        foreach ($wardsData as $wardData) {
            $lgaName = strtoupper(trim($wardData['lga']));
            $wardName = trim($wardData['name']);
            $scrapedCode = $wardData['code'] ?? '';

            if (!isset($lgaLookup[$lgaName])) {
                $this->command->warn("  LGA not found: {$lgaName}");
                continue;
            }

            $lga = $lgaLookup[$lgaName];

            // Find existing ward by name within LGA (not by code to avoid conflicts)
            $ward = Ward::where('lga_id', $lga->id)
                       ->where('name', $wardName)
                       ->first();

            if ($ward) {
                // Ward exists, just track it
                $this->wardsUpdated++;
            } else {
                // Generate a unique ward code: L{lga_id}W{seq} (max 10 chars)
                if (!isset($wardCounters[$lga->id])) {
                    // Start from max existing ward number + 1
                    $maxCode = Ward::where('lga_id', $lga->id)->count();
                    $wardCounters[$lga->id] = $maxCode + 1;
                }

                // Format: L{lga_id}W{seq} - e.g., L12W001 (max 10 chars)
                $uniqueCode = 'L' . $lga->id . 'W' . str_pad($wardCounters[$lga->id], 3, '0', STR_PAD_LEFT);
                $wardCounters[$lga->id]++;

                // Ensure code is unique and fits in 10 chars
                while (Ward::where('code', $uniqueCode)->exists() || strlen($uniqueCode) > 10) {
                    $uniqueCode = 'L' . $lga->id . 'W' . str_pad($wardCounters[$lga->id], 3, '0', STR_PAD_LEFT);
                    $wardCounters[$lga->id]++;
                }

                $ward = Ward::create([
                    'name' => $wardName,
                    'code' => $uniqueCode,
                    'lga_id' => $lga->id,
                ]);
                $this->wardsCreated++;
            }

            // Store in lookup with multiple keys for matching
            $lookupKey = strtoupper("{$lgaName}|{$wardName}");
            $wardLookup[$lookupKey] = $ward;

            // Also store with just ward name within LGA context
            if (!isset($wardLookup["{$lgaName}|*"])) {
                $wardLookup["{$lgaName}|*"] = [];
            }
            $wardLookup["{$lgaName}|*"][strtoupper($wardName)] = $ward;
        }

        return $wardLookup;
    }

    private function generateWardCode(string $lgaIdOrCode, string $wardName): string
    {
        // Generate unique code that fits in 10 chars
        // Use random suffix to ensure uniqueness
        $prefix = is_numeric($lgaIdOrCode) ? 'L' . $lgaIdOrCode . 'W' : 'W';
        $code = $prefix . str_pad(mt_rand(1, 9999), 4, '0', STR_PAD_LEFT);

        // Ensure uniqueness and max 10 chars
        while (Ward::where('code', $code)->exists() || strlen($code) > 10) {
            $code = $prefix . str_pad(mt_rand(1, 9999), 4, '0', STR_PAD_LEFT);
        }

        return $code;
    }

    private function seedPollingStations(array $unitsData, array $wardLookup, array $lgaLookup): void
    {
        // Batch insert for performance
        $batchSize = 100;
        $batch = [];
        $now = now();

        foreach ($unitsData as $unitData) {
            $lgaName = strtoupper(trim($unitData['lga']));
            $wardName = strtoupper(trim($unitData['ward']));
            $unitName = trim($unitData['name']);
            $unitCode = $unitData['code'] ?? '';

            // Find LGA
            if (!isset($lgaLookup[$lgaName])) {
                continue; // Skip if LGA not found
            }
            $lga = $lgaLookup[$lgaName];

            // Find ward using lookup
            $lookupKey = "{$lgaName}|{$wardName}";
            $ward = $wardLookup[$lookupKey] ?? null;

            // Try fuzzy match if exact match fails
            if (!$ward && isset($wardLookup["{$lgaName}|*"])) {
                $wardsInLga = $wardLookup["{$lgaName}|*"];
                $ward = $wardsInLga[$wardName] ?? null;

                // Try partial match
                if (!$ward) {
                    foreach ($wardsInLga as $wName => $w) {
                        if (str_contains($wName, $wardName) || str_contains($wardName, $wName)) {
                            $ward = $w;
                            break;
                        }
                    }
                }
            }

            if (!$ward) {
                // Create ward on-the-fly if not found
                $ward = Ward::where('name', $wardName)->where('lga_id', $lga->id)->first();
                if (!$ward) {
                    $ward = Ward::create([
                        'name' => $wardName,
                        'lga_id' => $lga->id,
                        'code' => $this->generateWardCode((string)$lga->id, $wardName),
                    ]);
                    $this->wardsCreated++;
                }

                // Add to lookup
                $wardLookup[$lookupKey] = $ward;
                if (!isset($wardLookup["{$lgaName}|*"])) {
                    $wardLookup["{$lgaName}|*"] = [];
                }
                $wardLookup["{$lgaName}|*"][$wardName] = $ward;
            }

            // Check if polling station already exists
            $exists = PollingStation::where('ward_id', $ward->id)
                                   ->where('name', $unitName)
                                   ->exists();
            if ($exists) {
                continue;
            }

            $batch[] = [
                'name' => $unitName,
                'code' => $ward->code . '-' . str_pad($unitCode, 3, '0', STR_PAD_LEFT),
                'ward_id' => $ward->id,
                'lga_id' => $lga->id,
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ];

            if (count($batch) >= $batchSize) {
                PollingStation::insert($batch);
                $this->unitsCreated += count($batch);
                $batch = [];
            }
        }

        // Insert remaining
        if (!empty($batch)) {
            PollingStation::insert($batch);
            $this->unitsCreated += count($batch);
        }
    }

    private function printSummary(): void
    {
        $this->command->newLine(2);
        $this->command->info("════════════════════════════════════════════════════════════════");
        $this->command->info("                    SEEDING COMPLETE!");
        $this->command->info("════════════════════════════════════════════════════════════════");
        $this->command->info("LGAs Created: {$this->lgasCreated}");
        $this->command->info("Wards Created: {$this->wardsCreated}");
        $this->command->info("Wards Updated: {$this->wardsUpdated}");
        $this->command->info("Polling Stations Created: {$this->unitsCreated}");
        $this->command->info("════════════════════════════════════════════════════════════════");
    }
}
