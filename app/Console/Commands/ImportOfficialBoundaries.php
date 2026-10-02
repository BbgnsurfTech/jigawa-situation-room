<?php

namespace App\Console\Commands;

use App\Models\Lga;
use App\Models\State;
use App\Models\Ward;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class ImportOfficialBoundaries extends Command
{
    protected $signature = 'jesr:import-official-boundaries
        {--lga-file= : Path to LGA GeoJSON file}
        {--ward-file= : Path to Ward GeoJSON file}
        {--state= : Filter by state name (e.g., Jigawa)}
        {--dry-run : Preview matches without saving}
        {--force : Skip confirmation prompts}';

    protected $description = 'Import official GRID3 Nigeria LGA and Ward boundary GeoJSON data';

    private array $stats = [
        'lga_matched' => 0,
        'lga_skipped' => 0,
        'ward_matched' => 0,
        'ward_skipped' => 0,
    ];

    public function handle(): int
    {
        $lgaFile = $this->option('lga-file');
        $wardFile = $this->option('ward-file');
        $stateName = $this->option('state');
        $dryRun = $this->option('dry-run');

        if (! $lgaFile && ! $wardFile) {
            $this->error('Please provide at least one of --lga-file or --ward-file');
            return self::FAILURE;
        }

        $this->info($dryRun ? '🔍 DRY RUN MODE - No changes will be saved' : '📥 IMPORT MODE');
        $this->newLine();

        // Validate state filter if provided
        $state = null;
        if ($stateName) {
            $state = State::where('name', 'LIKE', "%{$stateName}%")->first();
            if (! $state) {
                $this->error("State not found: {$stateName}");
                return self::FAILURE;
            }
            $this->info("Filtering for state: {$state->name}");
        }

        // Import LGA boundaries
        if ($lgaFile) {
            $this->importLgaBoundaries($lgaFile, $state, $dryRun);
        }

        // Import Ward boundaries
        if ($wardFile) {
            $this->importWardBoundaries($wardFile, $state, $dryRun);
        }

        // Summary
        $this->newLine();
        $this->table(
            ['Entity', 'Matched', 'Skipped'],
            [
                ['LGAs', $this->stats['lga_matched'], $this->stats['lga_skipped']],
                ['Wards', $this->stats['ward_matched'], $this->stats['ward_skipped']],
            ]
        );

        if ($dryRun) {
            $this->comment('Run again without --dry-run to save changes.');
        }

        return self::SUCCESS;
    }

    private function importLgaBoundaries(string $file, ?State $state, bool $dryRun): void
    {
        $this->info("📍 Importing LGA boundaries from: {$file}");

        $json = $this->loadGeoJson($file);
        if (! $json) {
            return;
        }

        $features = collect($json['features']);

        // Filter by state if specified
        if ($state) {
            $stateNameLower = strtolower($state->name);
            $features = $features->filter(function ($feature) use ($stateNameLower) {
                $featureState = strtolower($feature['properties']['statename'] ?? '');
                return Str::contains($featureState, $stateNameLower) ||
                       Str::contains($stateNameLower, $featureState);
            });
            $this->line("  Found {$features->count()} LGAs for {$state->name}");
        }

        // Get all LGAs from database for matching
        $query = Lga::query();
        if ($state) {
            $query->where('state_id', $state->id);
        }
        $dbLgas = $query->get()->keyBy(fn ($lga) => $this->normalizeForMatching($lga->name));

        $this->withProgressBar($features, function ($feature) use ($dbLgas, $dryRun) {
            $props = $feature['properties'] ?? [];
            $geometry = $feature['geometry'] ?? null;
            $lgaName = $props['lganame'] ?? null;

            if (! $lgaName || ! $geometry) {
                $this->stats['lga_skipped']++;
                return;
            }

            // Try to match LGA
            $normalizedName = $this->normalizeForMatching($lgaName);
            $lga = $dbLgas->get($normalizedName);

            // Fuzzy match if exact match fails
            if (! $lga) {
                $lga = $this->fuzzyMatch($lgaName, $dbLgas);
            }

            if (! $lga) {
                $this->stats['lga_skipped']++;
                return;
            }

            if (! $dryRun) {
                $lga->update(['boundary_geojson' => $geometry]);
            }

            $this->stats['lga_matched']++;
        });

        $this->newLine();
    }

    private function importWardBoundaries(string $file, ?State $state, bool $dryRun): void
    {
        $this->info("📍 Importing Ward boundaries from: {$file}");

        $json = $this->loadGeoJson($file);
        if (! $json) {
            return;
        }

        $features = collect($json['features']);

        // Filter by state if specified
        if ($state) {
            $stateNameLower = strtolower($state->name);
            $features = $features->filter(function ($feature) use ($stateNameLower) {
                $featureState = strtolower($feature['properties']['statename'] ?? '');
                return Str::contains($featureState, $stateNameLower) ||
                       Str::contains($stateNameLower, $featureState);
            });
            $this->line("  Found {$features->count()} Wards for {$state->name}");
        }

        // Get all wards from database with LGA relationships
        $query = Ward::with('lga');
        if ($state) {
            $query->whereHas('lga', fn ($q) => $q->where('state_id', $state->id));
        }
        $dbWards = $query->get();

        // Build composite key for matching: lga_name + ward_name
        $wardsByCompositeKey = $dbWards->keyBy(function ($ward) {
            return $this->normalizeForMatching($ward->lga->name) . '|' . $this->normalizeForMatching($ward->name);
        });

        // Also index by ward name only for fallback
        $wardsByName = $dbWards->groupBy(fn ($ward) => $this->normalizeForMatching($ward->name));

        $this->withProgressBar($features, function ($feature) use ($wardsByCompositeKey, $wardsByName, $dryRun) {
            $props = $feature['properties'] ?? [];
            $geometry = $feature['geometry'] ?? null;
            $wardName = $props['wardname'] ?? null;
            $lgaName = $props['lganame'] ?? null;

            if (! $wardName || ! $geometry) {
                $this->stats['ward_skipped']++;
                return;
            }

            $ward = null;

            // Try composite key match first (ward + LGA)
            if ($lgaName) {
                $compositeKey = $this->normalizeForMatching($lgaName) . '|' . $this->normalizeForMatching($wardName);
                $ward = $wardsByCompositeKey->get($compositeKey);

                // Fuzzy composite match
                if (! $ward) {
                    $ward = $this->fuzzyCompositeMatch($lgaName, $wardName, $wardsByCompositeKey);
                }
            }

            // Fallback to ward name only (risky if duplicates)
            if (! $ward) {
                $normalizedWardName = $this->normalizeForMatching($wardName);
                $candidates = $wardsByName->get($normalizedWardName);
                if ($candidates && $candidates->count() === 1) {
                    $ward = $candidates->first();
                }
            }

            if (! $ward) {
                $this->stats['ward_skipped']++;
                return;
            }

            if (! $dryRun) {
                $ward->update(['boundary_geojson' => $geometry]);
            }

            $this->stats['ward_matched']++;
        });

        $this->newLine();
    }

    private function loadGeoJson(string $file): ?array
    {
        if (! file_exists($file)) {
            $this->error("File not found: {$file}");
            return null;
        }

        $json = json_decode(file_get_contents($file), true);

        if (! $json || ($json['type'] ?? '') !== 'FeatureCollection' || empty($json['features'])) {
            $this->error('File must be a valid GeoJSON FeatureCollection');
            return null;
        }

        return $json;
    }

    private function normalizeForMatching(string $name): string
    {
        // Normalize: lowercase, remove special chars, collapse spaces
        $name = strtolower($name);
        $name = preg_replace('/[^a-z0-9\s]/', '', $name);
        $name = preg_replace('/\s+/', ' ', trim($name));
        return $name;
    }

    private function fuzzyMatch(string $searchName, $collection)
    {
        $normalizedSearch = $this->normalizeForMatching($searchName);
        $bestMatch = null;
        $bestScore = 0;

        foreach ($collection as $key => $item) {
            // Calculate similarity
            similar_text($normalizedSearch, $key, $score);

            // Also check if one contains the other
            $containsBonus = 0;
            if (Str::contains($key, $normalizedSearch) || Str::contains($normalizedSearch, $key)) {
                $containsBonus = 20;
            }

            $totalScore = $score + $containsBonus;

            if ($totalScore > $bestScore && $totalScore >= 70) {
                $bestScore = $totalScore;
                $bestMatch = $item;
            }
        }

        return $bestMatch;
    }

    private function fuzzyCompositeMatch(string $lgaName, string $wardName, $collection)
    {
        $normalizedLga = $this->normalizeForMatching($lgaName);
        $normalizedWard = $this->normalizeForMatching($wardName);
        $bestMatch = null;
        $bestScore = 0;

        foreach ($collection as $compositeKey => $item) {
            [$dbLga, $dbWard] = explode('|', $compositeKey);

            // Score LGA similarity
            similar_text($normalizedLga, $dbLga, $lgaScore);

            // Score Ward similarity
            similar_text($normalizedWard, $dbWard, $wardScore);

            // Combined score with ward weighted more heavily
            $totalScore = ($lgaScore * 0.3) + ($wardScore * 0.7);

            if ($totalScore > $bestScore && $totalScore >= 60) {
                $bestScore = $totalScore;
                $bestMatch = $item;
            }
        }

        return $bestMatch;
    }
}
