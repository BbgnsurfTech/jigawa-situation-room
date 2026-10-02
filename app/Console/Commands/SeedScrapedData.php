<?php

namespace App\Console\Commands;

use Database\Seeders\ScrapedDataSeeder;
use Illuminate\Console\Command;

class SeedScrapedData extends Command
{
    protected $signature = 'seed:scraped
                            {--state= : Seed only a specific state (e.g., jigawa)}
                            {--list : List available states with scraped data}';

    protected $description = 'Seed ward and polling station data from scraped JSON files';

    public function handle(): int
    {
        if ($this->option('list')) {
            return $this->listAvailableStates();
        }

        $state = $this->option('state');

        if ($state) {
            $this->info("Seeding data for state: " . strtoupper($state));
            putenv("SEEDER_STATE={$state}");
        } else {
            $this->info("Seeding data for all available states...");
        }

        $seeder = new ScrapedDataSeeder();
        $seeder->setCommand($this);
        $seeder->run();

        return Command::SUCCESS;
    }

    private function listAvailableStates(): int
    {
        $scrapedDir = storage_path('app/scraped');

        if (!is_dir($scrapedDir)) {
            $this->error("No scraped data directory found.");
            $this->info("Run 'php artisan scrape:all-states' first.");
            return Command::FAILURE;
        }

        $files = glob("{$scrapedDir}/units_*.json");
        $states = [];

        foreach ($files as $file) {
            $filename = basename($file);
            $stateName = str_replace(['units_', '.json'], '', $filename);
            $content = file_get_contents($file);
            $data = json_decode($content, true);
            $count = is_array($data) ? count($data) : 0;

            if ($count > 0) {
                $states[strtoupper(str_replace('_', ' ', $stateName))] = $count;
            }
        }

        if (empty($states)) {
            $this->error("No states with polling unit data found.");
            return Command::FAILURE;
        }

        arsort($states);

        $this->info("Available states with scraped data:");
        $this->newLine();

        $this->table(
            ['State', 'Polling Units'],
            collect($states)->map(fn($count, $name) => [$name, number_format($count)])
                           ->values()
                           ->toArray()
        );

        $this->newLine();
        $this->info("Total: " . count($states) . " states, " . number_format(array_sum($states)) . " polling units");
        $this->newLine();
        $this->info("To seed a specific state: php artisan seed:scraped --state=jigawa");
        $this->info("To seed all states: php artisan seed:scraped");

        return Command::SUCCESS;
    }
}
