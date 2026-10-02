<?php

namespace App\Console\Commands;

use App\Services\IntegrityNgScraper;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class ScrapeAllStates extends Command
{
    protected $signature = 'scrape:all-states
                            {--delay=100 : Delay between requests in milliseconds}
                            {--start-from= : Start from a specific state (e.g., KADUNA)}
                            {--skip-existing : Skip states that already have JSON files}';

    protected $description = 'Scrape ward and polling unit data for all 37 Nigerian states from integrity.ng';

    private int $totalWards = 0;
    private int $totalUnits = 0;

    public function handle(IntegrityNgScraper $scraper): int
    {
        $delay = (int) $this->option('delay');
        $startFrom = $this->option('start-from');
        $skipExisting = $this->option('skip-existing');

        $scraper->setRequestDelay($delay);

        $this->info('Fetching list of states from integrity.ng...');
        $states = $scraper->scrapeStates();

        if (empty($states)) {
            $this->error('Failed to fetch states list.');
            return Command::FAILURE;
        }

        $this->info("Found " . count($states) . " states");
        $this->newLine();

        // Find starting index
        $startIndex = 0;
        if ($startFrom) {
            foreach ($states as $index => $state) {
                if (strcasecmp($state['name'], $startFrom) === 0) {
                    $startIndex = $index;
                    break;
                }
            }
            $this->info("Starting from state: {$startFrom} (index {$startIndex})");
        }

        $startTime = now();

        for ($i = $startIndex; $i < count($states); $i++) {
            $state = $states[$i];
            $stateName = $state['name'];
            $stateCode = $state['code'];

            $this->newLine();
            $this->info("═══════════════════════════════════════════════════════════════");
            $this->info("[" . ($i + 1) . "/" . count($states) . "] Processing: {$stateName} ({$stateCode})");
            $this->info("═══════════════════════════════════════════════════════════════");

            // Check if already scraped
            $wardsFile = storage_path("app/scraped/wards_" . strtolower(str_replace(' ', '_', $stateName)) . ".json");
            $unitsFile = storage_path("app/scraped/units_" . strtolower(str_replace(' ', '_', $stateName)) . ".json");

            if ($skipExisting && File::exists($wardsFile) && File::exists($unitsFile)) {
                $this->warn("  Skipping - files already exist");
                continue;
            }

            // Ensure directory exists
            File::ensureDirectoryExists(storage_path('app/scraped'));

            // Scrape wards
            $this->info("  Scraping wards...");
            $wards = $scraper->scrapeWardsForState($stateName, function ($page, $total, $count) {
                // Silent progress
            });
            $this->info("    Found " . count($wards) . " wards");

            // Save wards
            $this->saveJson($wards, $wardsFile);
            $this->totalWards += count($wards);

            // Scrape polling units using hierarchical approach
            $this->info("  Scraping polling units (hierarchical)...");
            $units = $scraper->scrapeUnitsHierarchical($stateName, function ($lgaName, $wardName, $unitCount) {
                $this->line("    {$lgaName} → {$wardName}: {$unitCount} units");
            });
            $this->info("    Found " . count($units) . " polling units");

            // Save units
            $this->saveJson($units, $unitsFile);
            $this->totalUnits += count($units);

            // Progress summary
            $elapsed = now()->diffInMinutes($startTime);
            $this->info("  Progress: {$this->totalWards} wards, {$this->totalUnits} units (elapsed: {$elapsed} min)");
        }

        $this->newLine(2);
        $this->info("════════════════════════════════════════════════════════════════");
        $this->info("                    SCRAPING COMPLETE!");
        $this->info("════════════════════════════════════════════════════════════════");
        $this->info("Total States: " . count($states));
        $this->info("Total Wards: {$this->totalWards}");
        $this->info("Total Polling Units: {$this->totalUnits}");
        $this->info("Total Time: " . now()->diffInMinutes($startTime) . " minutes");
        $this->info("Files saved to: storage/app/scraped/");
        $this->info("════════════════════════════════════════════════════════════════");

        return Command::SUCCESS;
    }

    private function saveJson(array $data, string $path): void
    {
        $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        File::put($path, $json);
    }
}
