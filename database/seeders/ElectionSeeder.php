<?php

namespace Database\Seeders;

use App\Models\Election;
use Illuminate\Database\Seeder;

class ElectionSeeder extends Seeder
{
    public function run(): void
    {
        $elections = [
            [
                'name' => '2024 Presidential Election',
                'type' => Election::TYPE_PRESIDENTIAL,
                'election_date' => '2024-02-25',
                'is_active' => false,
            ],
            [
                'name' => '2024 Jigawa State Governorship Election',
                'type' => Election::TYPE_GOVERNORSHIP,
                'election_date' => '2024-03-09',
                'is_active' => true,
            ],
            [
                'name' => '2024 Jigawa State Senate Election',
                'type' => Election::TYPE_SENATE,
                'election_date' => '2024-02-25',
                'is_active' => true,
            ],
            [
                'name' => '2024 Jigawa State House of Representatives Election',
                'type' => Election::TYPE_HOUSE_OF_REPS,
                'election_date' => '2024-02-25',
                'is_active' => true,
            ],
        ];

        foreach ($elections as $election) {
            Election::firstOrCreate(
                ['type' => $election['type']],
                $election
            );
        }

        $activeCount = Election::active()->count();
        $this->command->info("ElectionSeeder: {$activeCount} active elections seeded.");
    }
}
