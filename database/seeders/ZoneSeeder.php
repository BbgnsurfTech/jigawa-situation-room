<?php

namespace Database\Seeders;

use App\Models\Zone;
use Illuminate\Database\Seeder;

class ZoneSeeder extends Seeder
{
    /**
     * Seed Nigeria's 6 geopolitical zones.
     */
    public function run(): void
    {
        $zones = [
            ['name' => 'North-Central', 'code' => 'NC'],
            ['name' => 'North-East', 'code' => 'NE'],
            ['name' => 'North-West', 'code' => 'NW'],
            ['name' => 'South-East', 'code' => 'SE'],
            ['name' => 'South-South', 'code' => 'SS'],
            ['name' => 'South-West', 'code' => 'SW'],
        ];

        foreach ($zones as $zone) {
            Zone::firstOrCreate(
                ['code' => $zone['code']],
                $zone
            );
        }

        $this->command->info('ZoneSeeder: 6 geopolitical zones seeded.');
    }
}
