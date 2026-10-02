<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Scraper for fetching ward and polling station data from integrity.ng.
 *
 * Data source: http://integrity.ng/index.php/wards/browse
 *              http://integrity.ng/index.php/units/browse
 */
class IntegrityNgScraper
{
    private const BASE_URL = 'http://integrity.ng/index.php';
    private const WARDS_URL = self::BASE_URL . '/wards/browse';
    private const UNITS_URL = self::BASE_URL . '/units/browse';

    private int $requestDelay = 500; // milliseconds between requests

    /**
     * Scrape all wards from integrity.ng.
     *
     * @param callable|null $progressCallback Called with (currentPage, totalPages, wardsOnPage)
     * @return array Array of ward data: [state, lga, name, code]
     */
    public function scrapeWards(?callable $progressCallback = null): array
    {
        $wards = [];
        $page = 1;
        $totalPages = $this->getWardsTotalPages();

        while ($page <= $totalPages) {
            $url = $page === 1 ? self::WARDS_URL : self::WARDS_URL . "/{$page}";
            $pageWards = $this->scrapeWardsPage($url);

            $wards = array_merge($wards, $pageWards);

            if ($progressCallback) {
                $progressCallback($page, $totalPages, count($pageWards));
            }

            $page++;
            usleep($this->requestDelay * 1000);
        }

        return $wards;
    }

    /**
     * Scrape all polling units from integrity.ng.
     *
     * @param string|null $stateFilter Filter by state name (optional)
     * @param callable|null $progressCallback Called with (currentPage, totalPages, unitsOnPage)
     * @return array Array of polling unit data: [state, lga, ward, name, code]
     */
    public function scrapePollingUnits(?string $stateFilter = null, ?callable $progressCallback = null): array
    {
        $units = [];
        $page = 1;
        $totalPages = $this->getUnitsTotalPages();

        while ($page <= $totalPages) {
            $url = $page === 1 ? self::UNITS_URL : self::UNITS_URL . "/{$page}";
            $pageUnits = $this->scrapeUnitsPage($url);

            // Filter by state if specified
            if ($stateFilter) {
                $pageUnits = array_filter($pageUnits, fn($unit) =>
                    strcasecmp($unit['state'], $stateFilter) === 0
                );
            }

            $units = array_merge($units, array_values($pageUnits));

            if ($progressCallback) {
                $progressCallback($page, $totalPages, count($pageUnits));
            }

            $page++;
            usleep($this->requestDelay * 1000);
        }

        return $units;
    }

    /**
     * Scrape wards for a specific state only (faster than full scrape).
     */
    public function scrapeWardsForState(string $stateName, ?callable $progressCallback = null): array
    {
        $wards = [];
        $page = 1;
        $totalPages = $this->getWardsTotalPages();
        $foundState = false;
        $passedState = false;

        while ($page <= $totalPages && !$passedState) {
            $url = $page === 1 ? self::WARDS_URL : self::WARDS_URL . "/{$page}";
            $pageWards = $this->scrapeWardsPage($url);

            foreach ($pageWards as $ward) {
                if (strcasecmp($ward['state'], $stateName) === 0) {
                    $foundState = true;
                    $wards[] = $ward;
                } elseif ($foundState) {
                    // We've passed the state (data is sorted by state)
                    $passedState = true;
                    break;
                }
            }

            if ($progressCallback) {
                $progressCallback($page, $totalPages, count($wards));
            }

            $page++;
            usleep($this->requestDelay * 1000);
        }

        return $wards;
    }

    /**
     * Scrape polling units for a specific state only.
     */
    public function scrapePollingUnitsForState(string $stateName, ?callable $progressCallback = null): array
    {
        $units = [];
        $page = 1;
        $totalPages = $this->getUnitsTotalPages();
        $foundState = false;
        $passedState = false;

        while ($page <= $totalPages && !$passedState) {
            $url = $page === 1 ? self::UNITS_URL : self::UNITS_URL . "/{$page}";
            $pageUnits = $this->scrapeUnitsPage($url);

            foreach ($pageUnits as $unit) {
                if (strcasecmp($unit['state'], $stateName) === 0) {
                    $foundState = true;
                    $units[] = $unit;
                } elseif ($foundState) {
                    $passedState = true;
                    break;
                }
            }

            if ($progressCallback) {
                $progressCallback($page, $totalPages, count($units));
            }

            $page++;
            usleep($this->requestDelay * 1000);
        }

        return $units;
    }

    /**
     * Get total number of ward pages.
     */
    public function getWardsTotalPages(): int
    {
        return $this->getTotalPages(self::WARDS_URL);
    }

    /**
     * Get total number of polling unit pages.
     */
    public function getUnitsTotalPages(): int
    {
        return $this->getTotalPages(self::UNITS_URL);
    }

    /**
     * Parse pagination to get total pages.
     */
    private function getTotalPages(string $url): int
    {
        $response = Http::timeout(30)->get($url);

        if (!$response->successful()) {
            Log::error("IntegrityNgScraper: Failed to fetch {$url}");
            return 0;
        }

        $html = $response->body();

        // Match pattern: href="...browse/89">Last
        if (preg_match('/browse\/(\d+)"[^>]*>Last/', $html, $matches)) {
            return (int) $matches[1];
        }

        return 1;
    }

    /**
     * Scrape a single page of wards.
     */
    private function scrapeWardsPage(string $url): array
    {
        $response = Http::timeout(30)->get($url);

        if (!$response->successful()) {
            Log::error("IntegrityNgScraper: Failed to fetch {$url}");
            return [];
        }

        $html = $response->body();
        $wards = [];

        // Pattern: <td>STATE</td>\n<td>LGA</td>\n<td>WARD NAME</td>\n<td>CODE</td>
        // Match rows with 4+ cells after the index and view link
        preg_match_all(
            '/<tr>\s*<td>\d+<\/td>\s*<td>.*?<\/td>\s*<td>([^<]+)<\/td>\s*<td>([^<]+)<\/td>\s*<td>([^<]+)<\/td>\s*<td>([^<]+)<\/td>/s',
            $html,
            $matches,
            PREG_SET_ORDER
        );

        foreach ($matches as $match) {
            $wards[] = [
                'state' => trim($match[1]),
                'lga' => trim($match[2]),
                'name' => trim($match[3]),
                'code' => trim($match[4]),
            ];
        }

        return $wards;
    }

    /**
     * Scrape a single page of polling units.
     */
    private function scrapeUnitsPage(string $url): array
    {
        $response = Http::timeout(30)->get($url);

        if (!$response->successful()) {
            Log::error("IntegrityNgScraper: Failed to fetch {$url}");
            return [];
        }

        $html = $response->body();
        $units = [];

        // Pattern for units: <td>INDEX</td><td>STATE</td><td>LGA</td><td>WARD</td><td>UNIT NAME</td><td>CODE</td>
        preg_match_all(
            '/<tr>\s*<td>\d+<\/td>\s*<td>([^<]+)<\/td>\s*<td>([^<]+)<\/td>\s*<td>([^<]+)<\/td>\s*<td>([^<]+)<\/td>\s*<td>([^<]+)<\/td>/s',
            $html,
            $matches,
            PREG_SET_ORDER
        );

        foreach ($matches as $match) {
            $units[] = [
                'state' => trim($match[1]),
                'lga' => trim($match[2]),
                'ward' => trim($match[3]),
                'name' => trim($match[4]),
                'code' => trim($match[5]),
            ];
        }

        return $units;
    }

    /**
     * Set delay between requests (in milliseconds).
     */
    public function setRequestDelay(int $milliseconds): self
    {
        $this->requestDelay = $milliseconds;
        return $this;
    }

    /**
     * Export scraped data to JSON file.
     */
    public function exportToJson(array $data, string $filename): bool
    {
        $path = storage_path("app/{$filename}");
        $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

        return file_put_contents($path, $json) !== false;
    }

    // ========================================================================
    // HIERARCHICAL SCRAPING (FASTER) - State → LGA → Ward → Units
    // ========================================================================

    /**
     * Get all states with their IDs.
     */
    public function scrapeStates(): array
    {
        $url = self::BASE_URL . '/states/browse';
        $response = $this->safeGet($url);

        if (!$response) {
            return [];
        }

        $states = [];
        preg_match_all(
            '/<tr>\s*<td>(\d+)<\/td>\s*<td><a href="[^"]*states\/view\/(\d+)"[^>]*>.*?<\/a><\/td>\s*<td>([^<]+)<\/td>\s*<td>([^<]+)<\/td>/s',
            $response->body(),
            $matches,
            PREG_SET_ORDER
        );

        foreach ($matches as $match) {
            $states[] = [
                'id' => (int) $match[2],
                'name' => trim($match[3]),
                'code' => trim($match[4]),
            ];
        }

        return $states;
    }

    /**
     * Get LGAs for a state by state ID (from state view page).
     */
    public function scrapeLgasForState(int $stateId): array
    {
        $url = self::BASE_URL . "/states/view/{$stateId}";
        $response = $this->safeGet($url);

        if (!$response) {
            return [];
        }

        $lgas = [];
        preg_match_all(
            '/<td>(\d+)<\/td>\s*<td><a href="[^"]*lgas\/view\/(\d+)"[^>]*>.*?<\/a><\/td>\s*<td>([^<]+)<\/td>/s',
            $response->body(),
            $matches,
            PREG_SET_ORDER
        );

        foreach ($matches as $match) {
            $lgas[] = [
                'id' => (int) $match[2],
                'name' => trim($match[3]),
            ];
        }

        return $lgas;
    }

    /**
     * Get wards for an LGA by LGA ID (from LGA view page).
     */
    public function scrapeWardsForLga(int $lgaId): array
    {
        $url = self::BASE_URL . "/lgas/view/{$lgaId}";
        $response = $this->safeGet($url);

        if (!$response) {
            return [];
        }

        $wards = [];
        preg_match_all(
            '/<td>(\d+)<\/td>\s*<td><a href="[^"]*wards\/view\/(\d+)"[^>]*>.*?<\/a><\/td>\s*<td>([^<]+)<\/td>/s',
            $response->body(),
            $matches,
            PREG_SET_ORDER
        );

        foreach ($matches as $match) {
            $wards[] = [
                'id' => (int) $match[2],
                'name' => trim($match[3]),
            ];
        }

        return $wards;
    }

    /**
     * Get polling units for a ward by ward ID (from ward view page).
     */
    public function scrapeUnitsForWard(int $wardId): array
    {
        $url = self::BASE_URL . "/wards/view/{$wardId}";
        $response = $this->safeGet($url);

        if (!$response) {
            return [];
        }

        $units = [];
        // Pattern: <td>INDEX</td><td>UNIT NAME</td>
        preg_match_all(
            '/<td>(\d+)<\/td>\s*<td>([^<]+)<\/td>/s',
            $response->body(),
            $matches,
            PREG_SET_ORDER
        );

        foreach ($matches as $match) {
            $name = trim($match[2]);
            // Skip header/empty rows
            if (strlen($name) > 2 && !is_numeric($name)) {
                $units[] = [
                    'index' => (int) $match[1],
                    'name' => $name,
                ];
            }
        }

        return $units;
    }

    /**
     * Scrape all polling units for a state using hierarchical approach.
     * Much faster than pagination approach.
     *
     * @param string $stateName State name (e.g., "JIGAWA")
     * @param callable|null $progressCallback Called with (lgaName, wardName, unitCount)
     * @return array Polling units with state, lga, ward, name
     */
    public function scrapeUnitsHierarchical(string $stateName, ?callable $progressCallback = null): array
    {
        $units = [];

        // Find state ID
        $states = $this->scrapeStates();
        $stateId = null;

        foreach ($states as $state) {
            if (strcasecmp($state['name'], $stateName) === 0) {
                $stateId = $state['id'];
                break;
            }
        }

        if (!$stateId) {
            Log::error("IntegrityNgScraper: State '{$stateName}' not found");
            return [];
        }

        usleep($this->requestDelay * 1000);

        // Get LGAs for state
        $lgas = $this->scrapeLgasForState($stateId);

        foreach ($lgas as $lga) {
            usleep($this->requestDelay * 1000);

            // Get wards for LGA
            $wards = $this->scrapeWardsForLga($lga['id']);

            foreach ($wards as $ward) {
                usleep($this->requestDelay * 1000);

                // Get units for ward
                $wardUnits = $this->scrapeUnitsForWard($ward['id']);

                foreach ($wardUnits as $unit) {
                    $units[] = [
                        'state' => strtoupper($stateName),
                        'lga' => $lga['name'],
                        'ward' => $ward['name'],
                        'name' => $unit['name'],
                        'code' => $unit['index'],
                    ];
                }

                if ($progressCallback) {
                    $progressCallback($lga['name'], $ward['name'], count($wardUnits));
                }
            }
        }

        return $units;
    }

    /**
     * Safe HTTP GET with retry logic.
     */
    private function safeGet(string $url, int $retries = 3): ?\Illuminate\Http\Client\Response
    {
        for ($i = 0; $i < $retries; $i++) {
            try {
                $response = Http::timeout(30)
                    ->withOptions(['verify' => false])
                    ->get($url);

                if ($response->successful()) {
                    return $response;
                }

                Log::warning("IntegrityNgScraper: HTTP {$response->status()} for {$url}");
            } catch (\Exception $e) {
                Log::warning("IntegrityNgScraper: Attempt " . ($i + 1) . " failed for {$url}: " . $e->getMessage());
            }

            if ($i < $retries - 1) {
                sleep(2); // Wait before retry
            }
        }

        Log::error("IntegrityNgScraper: Failed after {$retries} retries for {$url}");
        return null;
    }
}
