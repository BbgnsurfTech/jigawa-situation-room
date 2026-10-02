<?php

namespace App\Console\Commands;

use App\Services\IntegrityNgScraper;
use Illuminate\Console\Command;

class ScrapeIntegrityNg extends Command
{
    protected $signature = 'scrape:integrity-ng
                            {type : Type of data to scrape (wards, units, all)}
                            {--state= : Scrape only for a specific state (e.g., JIGAWA)}
                            {--output= : Output filename (stored in storage/app/)}
                            {--delay=500 : Delay between requests in milliseconds}';

    protected $description = 'Scrape ward and polling unit data from integrity.ng';

    public function handle(IntegrityNgScraper $scraper): int
    {
        $type = $this->argument('type');
        $state = $this->option('state');
        $delay = (int) $this->option('delay');

        $scraper->setRequestDelay($delay);

        $this->info('Connecting to integrity.ng...');
        $this->newLine();

        if ($type === 'wards' || $type === 'all') {
            $this->scrapeWards($scraper, $state);
        }

        if ($type === 'units' || $type === 'all') {
            $this->scrapeUnits($scraper, $state);
        }

        $this->newLine();
        $this->info('Scraping complete!');

        return Command::SUCCESS;
    }

    private function scrapeWards(IntegrityNgScraper $scraper, ?string $state): void
    {
        $this->info('Scraping wards...');

        $totalPages = $scraper->getWardsTotalPages();
        $this->info("Total ward pages: {$totalPages}");

        $progressBar = $this->output->createProgressBar($totalPages);
        $progressBar->start();

        $callback = function ($page, $total, $count) use ($progressBar) {
            $progressBar->advance();
        };

        if ($state) {
            $this->info("Filtering for state: {$state}");
            $wards = $scraper->scrapeWardsForState($state, $callback);
        } else {
            $wards = $scraper->scrapeWards($callback);
        }

        $progressBar->finish();
        $this->newLine(2);

        $this->info("Scraped " . count($wards) . " wards");

        // Output to file
        $filename = $this->option('output') ?? ($state ? "wards_{$state}.json" : 'wards_all.json');
        $filename = strtolower(str_replace(' ', '_', $filename));

        if ($scraper->exportToJson($wards, $filename)) {
            $this->info("Exported to storage/app/{$filename}");
        }

        // Show sample
        $this->table(
            ['State', 'LGA', 'Ward Name', 'Code'],
            array_slice($wards, 0, 10)
        );

        if (count($wards) > 10) {
            $this->info('... and ' . (count($wards) - 10) . ' more');
        }
    }

    private function scrapeUnits(IntegrityNgScraper $scraper, ?string $state): void
    {
        $this->info('Scraping polling units...');

        if ($state) {
            // Use faster hierarchical approach for single state
            $this->info("Using hierarchical scraping for state: {$state}");
            $this->newLine();

            $callback = function ($lgaName, $wardName, $unitCount) {
                $this->line("  {$lgaName} → {$wardName}: {$unitCount} units");
            };

            $units = $scraper->scrapeUnitsHierarchical($state, $callback);
        } else {
            // Full pagination approach for all states
            $totalPages = $scraper->getUnitsTotalPages();
            $this->info("Total polling unit pages: {$totalPages}");
            $this->warn("This may take a while (~{$totalPages} requests)...");

            $progressBar = $this->output->createProgressBar($totalPages);
            $progressBar->start();

            $callback = function ($page, $total, $count) use ($progressBar) {
                $progressBar->advance();
            };

            $units = $scraper->scrapePollingUnits(null, $callback);

            $progressBar->finish();
        }

        $this->newLine(2);
        $this->info("Scraped " . count($units) . " polling units");

        // Output to file
        $filename = $this->option('output') ?? ($state ? "units_{$state}.json" : 'units_all.json');
        $filename = strtolower(str_replace(' ', '_', $filename));

        if ($scraper->exportToJson($units, $filename)) {
            $this->info("Exported to storage/app/{$filename}");
        }

        // Show sample
        $this->table(
            ['State', 'LGA', 'Ward', 'Unit Name', 'Code'],
            array_map(fn($u) => [
                $u['state'],
                $u['lga'],
                $u['ward'],
                substr($u['name'], 0, 40) . (strlen($u['name']) > 40 ? '...' : ''),
                $u['code'],
            ], array_slice($units, 0, 10))
        );

        if (count($units) > 10) {
            $this->info('... and ' . (count($units) - 10) . ' more');
        }
    }
}
