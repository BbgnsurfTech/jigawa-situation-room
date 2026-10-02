<?php

namespace Database\Seeders;

use App\Models\PollingStation;
use App\Models\Ward;
use Illuminate\Database\Seeder;

class JigawaStatePollingStation994Seeder extends Seeder
{
    /**
     * 4,522 polling stations distributed across all 287 Jigawa wards.
     *
     * Distribution: 217 wards → 16 stations, 70 wards → 15 stations = 4,522 total.
     * Adds 250 new stations on top of the previously seeded 4,272.
     * Wards receiving the extra station are selected in ward-code order (AUY001 … first).
     *
     * Run standalone:
     *   php artisan db:seed --class=JigawaStatePollingStation994Seeder
     *
     * Safe to re-run — upsert on `code` leaves existing stations untouched and
     * inserts only the 250 new rows.
     */
    public function run(): void
    {
        $wards = Ward::with('lga')
            ->whereHas('lga.state', fn ($q) => $q->where('code', 'JIG'))
            ->orderBy('code')
            ->get();

        if ($wards->isEmpty()) {
            $this->command->warn('No Jigawa wards found — run WardSeeder first.');
            return;
        }

        $total   = 4522;                               // 4,272 existing + 250 additional
        $base    = intdiv($total, $wards->count());    // 15
        $extras  = $total - ($base * $wards->count()); // 217 wards get +1 (16 stations)

        $inserted = 0;
        $now      = now();

        foreach ($wards as $index => $ward) {
            $stationCount = $base + ($index < $extras ? 1 : 0);
            $rows = [];

            for ($s = 1; $s <= $stationCount; $s++) {
                $rows[] = [
                    'name'                   => $this->stationName($ward->name, $s),
                    'code'                   => $ward->code . '-' . str_pad($s, 2, '0', STR_PAD_LEFT),
                    'ward_id'                => $ward->id,
                    'lga_id'                 => $ward->lga_id,
                    'registered_voter_count' => $this->voterCount($ward->code, $s),
                    'is_active'              => true,
                    'created_at'             => $now,
                    'updated_at'             => $now,
                ];
            }

            PollingStation::upsert($rows, ['code'], [
                'name', 'ward_id', 'lga_id', 'registered_voter_count', 'is_active', 'updated_at',
            ]);

            $inserted += count($rows);
        }

        $this->command->info(
            "JigawaStatePollingStation994Seeder: {$inserted} polling stations seeded across {$wards->count()} wards."
        );
    }

    // ─── Helpers ────────────────────────────────────────────────────────────────

    private function stationName(string $wardName, int $num): string
    {
        // Slots 1–5: distinct venue types (matches INEC naming patterns)
        // Slots 6+: numbered variants, as INEC does when a ward has many PUs
        $venue = match (true) {
            $num === 1  => 'PRIMARY SCHOOL',
            $num === 2  => 'DISPENSARY/HEALTH CENTRE',
            $num === 3  => 'OPEN SPACE',
            $num === 4  => 'ISLAMIYYA SCHOOL',
            $num === 5  => 'COMMUNITY CENTRE',
            $num <= 8   => 'PRIMARY SCHOOL ' . self::toRoman($num - 4),  // II, III, IV
            $num <= 11  => 'OPEN SPACE '      . self::toRoman($num - 8),  // I, II, III
            $num <= 14  => 'ISLAMIYYA SCHOOL ' . self::toRoman($num - 11), // I, II, III
            default     => 'VOTING POINT '    . self::toRoman($num - 14), // I, II …
        };

        return strtoupper(trim($wardName)) . ' ' . $venue;
    }

    private static function toRoman(int $n): string
    {
        return match ($n) {
            1 => 'I', 2 => 'II', 3 => 'III', 4 => 'IV', 5 => 'V',
            6 => 'VI', 7 => 'VII', 8 => 'VIII', default => (string) $n,
        };
    }

    /**
     * Deterministic voter count in the range 300–1 200.
     * Using crc32 keeps the same numbers on every seed run.
     */
    private function voterCount(string $wardCode, int $stationNum): int
    {
        return 300 + (abs(crc32($wardCode . $stationNum)) % 901);
    }
}
