<?php

namespace Database\Seeders;

use App\Models\State;
use App\Models\Zone;
use Illuminate\Database\Seeder;

class StateSeeder extends Seeder
{
    /**
     * Seed Nigeria's 36 states + FCT organized by geopolitical zones.
     */
    public function run(): void
    {
        $zones = Zone::all()->keyBy('code');

        if ($zones->isEmpty()) {
            $this->command->error('ZoneSeeder must run first. Skipping StateSeeder.');
            return;
        }

        $states = [
            // North-Central (NC) - 6 states + FCT
            ['name' => 'Benue', 'code' => 'BEN', 'zone' => 'NC', 'capital' => 'Makurdi'],
            ['name' => 'Kogi', 'code' => 'KOG', 'zone' => 'NC', 'capital' => 'Lokoja'],
            ['name' => 'Kwara', 'code' => 'KWA', 'zone' => 'NC', 'capital' => 'Ilorin'],
            ['name' => 'Nasarawa', 'code' => 'NAS', 'zone' => 'NC', 'capital' => 'Lafia'],
            ['name' => 'Niger', 'code' => 'NIG', 'zone' => 'NC', 'capital' => 'Minna'],
            ['name' => 'Plateau', 'code' => 'PLA', 'zone' => 'NC', 'capital' => 'Jos'],
            ['name' => 'Federal Capital Territory', 'code' => 'FCT', 'zone' => 'NC', 'capital' => 'Abuja'],

            // North-East (NE) - 6 states
            ['name' => 'Adamawa', 'code' => 'ADA', 'zone' => 'NE', 'capital' => 'Yola'],
            ['name' => 'Bauchi', 'code' => 'BAU', 'zone' => 'NE', 'capital' => 'Bauchi'],
            ['name' => 'Borno', 'code' => 'BOR', 'zone' => 'NE', 'capital' => 'Maiduguri'],
            ['name' => 'Gombe', 'code' => 'GOM', 'zone' => 'NE', 'capital' => 'Gombe'],
            ['name' => 'Taraba', 'code' => 'TAR', 'zone' => 'NE', 'capital' => 'Jalingo'],
            ['name' => 'Yobe', 'code' => 'YOB', 'zone' => 'NE', 'capital' => 'Damaturu'],

            // North-West (NW) - 7 states
            ['name' => 'Jigawa', 'code' => 'JIG', 'zone' => 'NW', 'capital' => 'Dutse'],
            ['name' => 'Kaduna', 'code' => 'KAD', 'zone' => 'NW', 'capital' => 'Kaduna'],
            ['name' => 'Kano', 'code' => 'KAN', 'zone' => 'NW', 'capital' => 'Kano'],
            ['name' => 'Katsina', 'code' => 'KAT', 'zone' => 'NW', 'capital' => 'Katsina'],
            ['name' => 'Kebbi', 'code' => 'KEB', 'zone' => 'NW', 'capital' => 'Birnin Kebbi'],
            ['name' => 'Sokoto', 'code' => 'SOK', 'zone' => 'NW', 'capital' => 'Sokoto'],
            ['name' => 'Zamfara', 'code' => 'ZAM', 'zone' => 'NW', 'capital' => 'Gusau'],

            // South-East (SE) - 5 states
            ['name' => 'Abia', 'code' => 'ABI', 'zone' => 'SE', 'capital' => 'Umuahia'],
            ['name' => 'Anambra', 'code' => 'ANA', 'zone' => 'SE', 'capital' => 'Awka'],
            ['name' => 'Ebonyi', 'code' => 'EBO', 'zone' => 'SE', 'capital' => 'Abakaliki'],
            ['name' => 'Enugu', 'code' => 'ENU', 'zone' => 'SE', 'capital' => 'Enugu'],
            ['name' => 'Imo', 'code' => 'IMO', 'zone' => 'SE', 'capital' => 'Owerri'],

            // South-South (SS) - 6 states
            ['name' => 'Akwa Ibom', 'code' => 'AKW', 'zone' => 'SS', 'capital' => 'Uyo'],
            ['name' => 'Bayelsa', 'code' => 'BAY', 'zone' => 'SS', 'capital' => 'Yenagoa'],
            ['name' => 'Cross River', 'code' => 'CRO', 'zone' => 'SS', 'capital' => 'Calabar'],
            ['name' => 'Delta', 'code' => 'DEL', 'zone' => 'SS', 'capital' => 'Asaba'],
            ['name' => 'Edo', 'code' => 'EDO', 'zone' => 'SS', 'capital' => 'Benin City'],
            ['name' => 'Rivers', 'code' => 'RIV', 'zone' => 'SS', 'capital' => 'Port Harcourt'],

            // South-West (SW) - 6 states
            ['name' => 'Ekiti', 'code' => 'EKI', 'zone' => 'SW', 'capital' => 'Ado-Ekiti'],
            ['name' => 'Lagos', 'code' => 'LAG', 'zone' => 'SW', 'capital' => 'Ikeja'],
            ['name' => 'Ogun', 'code' => 'OGU', 'zone' => 'SW', 'capital' => 'Abeokuta'],
            ['name' => 'Ondo', 'code' => 'OND', 'zone' => 'SW', 'capital' => 'Akure'],
            ['name' => 'Osun', 'code' => 'OSU', 'zone' => 'SW', 'capital' => 'Osogbo'],
            ['name' => 'Oyo', 'code' => 'OYO', 'zone' => 'SW', 'capital' => 'Ibadan'],
        ];

        foreach ($states as $state) {
            $zone = $zones[$state['zone']] ?? null;
            if (!$zone) {
                $this->command->warn("Zone {$state['zone']} not found for state {$state['name']}");
                continue;
            }

            State::firstOrCreate(
                ['code' => $state['code']],
                [
                    'name' => $state['name'],
                    'code' => $state['code'],
                    'zone_id' => $zone->id,
                    'capital' => $state['capital'],
                ]
            );
        }

        $total = State::count();
        $this->command->info("StateSeeder: {$total} states seeded (36 states + FCT).");
    }
}
