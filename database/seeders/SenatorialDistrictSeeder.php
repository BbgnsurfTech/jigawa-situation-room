<?php

namespace Database\Seeders;

use App\Models\SenatorialDistrict;
use App\Models\State;
use Illuminate\Database\Seeder;

class SenatorialDistrictSeeder extends Seeder
{
    /**
     * Seed all 109 Senatorial Districts across Nigeria.
     * Each state has 3 senatorial districts, FCT has 1.
     */
    public function run(): void
    {
        $states = State::all()->keyBy('code');

        if ($states->isEmpty()) {
            $this->command->error('StateSeeder must run first. Skipping SenatorialDistrictSeeder.');
            return;
        }

        // Senatorial districts organized by state code
        // Format: 'STATE_CODE' => ['District 1', 'District 2', 'District 3']
        $districts = [
            // NORTH-CENTRAL
            'BEN' => ['Benue North-East', 'Benue North-West', 'Benue South'],
            'KOG' => ['Kogi Central', 'Kogi East', 'Kogi West'],
            'KWA' => ['Kwara Central', 'Kwara North', 'Kwara South'],
            'NAS' => ['Nasarawa North', 'Nasarawa South', 'Nasarawa West'],
            'NIG' => ['Niger East', 'Niger North', 'Niger South'],
            'PLA' => ['Plateau Central', 'Plateau North', 'Plateau South'],
            'FCT' => ['FCT'],

            // NORTH-EAST
            'ADA' => ['Adamawa Central', 'Adamawa North', 'Adamawa South'],
            'BAU' => ['Bauchi Central', 'Bauchi North', 'Bauchi South'],
            'BOR' => ['Borno Central', 'Borno North', 'Borno South'],
            'GOM' => ['Gombe Central', 'Gombe North', 'Gombe South'],
            'TAR' => ['Taraba Central', 'Taraba North', 'Taraba South'],
            'YOB' => ['Yobe East', 'Yobe North', 'Yobe South'],

            // NORTH-WEST
            'JIG' => ['Jigawa North-East', 'Jigawa North-West', 'Jigawa South'],
            'KAD' => ['Kaduna Central', 'Kaduna North', 'Kaduna South'],
            'KAN' => ['Kano Central', 'Kano North', 'Kano South'],
            'KAT' => ['Katsina Central', 'Katsina North', 'Katsina South'],
            'KEB' => ['Kebbi Central', 'Kebbi North', 'Kebbi South'],
            'SOK' => ['Sokoto East', 'Sokoto North', 'Sokoto South'],
            'ZAM' => ['Zamfara Central', 'Zamfara North', 'Zamfara West'],

            // SOUTH-EAST
            'ABI' => ['Abia Central', 'Abia North', 'Abia South'],
            'ANA' => ['Anambra Central', 'Anambra North', 'Anambra South'],
            'EBO' => ['Ebonyi Central', 'Ebonyi North', 'Ebonyi South'],
            'ENU' => ['Enugu East', 'Enugu North', 'Enugu West'],
            'IMO' => ['Imo East', 'Imo North', 'Imo West'],

            // SOUTH-SOUTH
            'AKW' => ['Akwa Ibom North-East', 'Akwa Ibom North-West', 'Akwa Ibom South'],
            'BAY' => ['Bayelsa Central', 'Bayelsa East', 'Bayelsa West'],
            'CRO' => ['Cross River Central', 'Cross River North', 'Cross River South'],
            'DEL' => ['Delta Central', 'Delta North', 'Delta South'],
            'EDO' => ['Edo Central', 'Edo North', 'Edo South'],
            'RIV' => ['Rivers East', 'Rivers South-East', 'Rivers West'],

            // SOUTH-WEST
            'EKI' => ['Ekiti Central', 'Ekiti North', 'Ekiti South'],
            'LAG' => ['Lagos Central', 'Lagos East', 'Lagos West'],
            'OGU' => ['Ogun Central', 'Ogun East', 'Ogun West'],
            'OND' => ['Ondo Central', 'Ondo North', 'Ondo South'],
            'OSU' => ['Osun Central', 'Osun East', 'Osun West'],
            'OYO' => ['Oyo Central', 'Oyo North', 'Oyo South'],
        ];

        $count = 0;
        foreach ($districts as $stateCode => $districtNames) {
            $state = $states[$stateCode] ?? null;
            if (!$state) {
                $this->command->warn("State {$stateCode} not found. Skipping its districts.");
                continue;
            }

            foreach ($districtNames as $index => $name) {
                // Generate code: e.g., JIG-NE for Jigawa North-East
                $code = $this->generateDistrictCode($stateCode, $name, $index);

                SenatorialDistrict::updateOrCreate(
                    ['code' => $code],
                    [
                        'name' => $name,
                        'state_id' => $state->id,
                    ]
                );
                $count++;
            }
        }

        $this->command->info("SenatorialDistrictSeeder: {$count} senatorial districts seeded.");
    }

    /**
     * Generate a unique code for a senatorial district.
     */
    private function generateDistrictCode(string $stateCode, string $name, int $index): string
    {
        // Extract direction from name (North, South, East, West, Central)
        $directionMap = [
            'North-East' => 'NE',
            'North-West' => 'NW',
            'South-East' => 'SE',
            'South-West' => 'SW',
            'North' => 'N',
            'South' => 'S',
            'East' => 'E',
            'West' => 'W',
            'Central' => 'C',
        ];

        foreach ($directionMap as $direction => $code) {
            if (str_contains($name, $direction)) {
                return "{$stateCode}-{$code}";
            }
        }

        // Fallback: use index
        return "{$stateCode}-" . ($index + 1);
    }
}
