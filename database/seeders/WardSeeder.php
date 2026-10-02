<?php

namespace Database\Seeders;

use App\Models\Lga;
use App\Models\SenatorialDistrict;
use App\Models\State;
use App\Models\Ward;
use Illuminate\Database\Seeder;

class WardSeeder extends Seeder
{
    /**
     * Seed wards for all states.
     * Currently includes complete data for Jigawa (287 wards).
     * Other states have template structures ready for population.
     */
    public function run(): void
    {
        $states = State::all()->keyBy('code');
        $count = 0;

        // Seed Jigawa wards (complete data - 287 wards)
        if ($states->has('JIG')) {
            $count += $this->seedJigawaWards();
        }

        // Seed other states with basic ward structure
        foreach ($states as $stateCode => $state) {
            if ($stateCode === 'JIG') continue; // Already seeded above
            $count += $this->seedStateWards($stateCode);
        }

        $this->command->info("WardSeeder: {$count} wards seeded across all states.");
    }

    /**
     * Seed Jigawa's 287 wards (complete INEC official data).
     */
    private function seedJigawaWards(): int
    {
        $jigawa = State::where('code', 'JIG')->first();
        if (!$jigawa) return 0;

        // Get or create Jigawa's three senatorial districts
        $northEast = SenatorialDistrict::firstOrCreate(
            ['code' => 'JIG-NE'],
            ['name' => 'Jigawa North-East', 'state_id' => $jigawa->id]
        );
        $northWest = SenatorialDistrict::firstOrCreate(
            ['code' => 'JIG-NW'],
            ['name' => 'Jigawa North-West', 'state_id' => $jigawa->id]
        );
        $south = SenatorialDistrict::firstOrCreate(
            ['code' => 'JIG-S'],
            ['name' => 'Jigawa South', 'state_id' => $jigawa->id]
        );

        // LGA code -> senatorial district mapping
        $districtMap = [
            // Jigawa North-East — 13 LGAs
            'AUY' => $northEast, 'BUJ' => $northEast, 'DUT' => $northEast,
            'GAG' => $northEast, 'GUR' => $northEast, 'HAD' => $northEast,
            'JAH' => $northEast, 'KAF' => $northEast, 'KAZ' => $northEast,
            'KIY' => $northEast, 'MAI' => $northEast, 'MIG' => $northEast,
            'RIN' => $northEast,
            // Jigawa North-West — 8 LGAs
            'BAB' => $northWest, 'BIR' => $northWest, 'GAR' => $northWest,
            'GUM' => $northWest, 'GWI' => $northWest, 'KAU' => $northWest,
            'KIR' => $northWest, 'RON' => $northWest,
            // Jigawa South — 6 LGAs
            'BKU' => $south, 'GWR' => $south, 'MAD' => $south,
            'SUL' => $south, 'TAU' => $south, 'YAN' => $south,
        ];

        // Complete ward data for Jigawa (287 wards from INEC official list)
        $wardData = [
            'AUY' => ['Auyo', 'Auyakayi', 'Ayama', 'Ayan', 'Gamafoi', 'Gamsarka', 'Gatafa', 'Kafur', 'Tsidir', 'Unik'],
            'BAB' => ['Babura', 'Batali', 'Dorawa', 'Garu', 'Gasakoli', 'Insharuwa', 'Jigawa', 'Kanya', 'Kuzunzumi', 'Kyambo', 'Takwasa'],
            'BIR' => ['Batu', 'Birniwa', 'Dangwaleri', 'Diginsa', 'Fagi', 'Kachallari', 'Karanka', 'Kazura', 'Machinamari', 'Matamu', 'Nguwa'],
            'BKU' => ['Birnin Kudu', 'Kangire', 'Kantoga', 'Kiyako', 'Kwangwara', 'Lafiya', 'Sundimina', 'Surko', "Unguwar 'Ya", 'Wurno', 'Yalwan Damai'],
            'BUJ' => ['Ahoto', 'Buji', 'Churbun', 'Gantsa', 'Falageri', 'Kawaya', 'Kukuma', 'K/Lelen Kudu', 'Madabe', 'Y/Tukur'],
            'DUT' => ['Abaya', 'Chamo', 'Dundubus', 'Duru', 'Jigawar Tsada', 'Kachi', 'Karnaya', 'Kudai', 'Limawa', 'Madobi', 'Sakwaya'],
            'GAG' => ['Gagarawa Gari', 'Gagarawa Tasha', 'Garin Chiroma', 'Kore Balatu', 'Madaka', 'Maiaduwa', 'Maikilili', 'Medu', 'Yalawa', 'Zarada'],
            'GAR' => ['Buduru', 'Doko', 'Garki', 'Gwarzon Garki', 'Jirma', 'Kanya', 'Kargo', 'Kore', 'Muku', 'Rafin Marke', 'Siyori'],
            'GUM' => ['Baikarya', 'Danama', 'Dantanoma', 'Galagamma', 'Garin Gambo', 'Garin Alhaji Barka', 'Gusau', 'Hammado', 'Kofar Arewa', 'Kofar Yamma', 'Zango'],
            'GUR' => ['Abunabo', 'Adiyani', 'Dawa', 'Garbagal', 'Guri', 'Kadira', 'Lafiya', 'Margadu', 'Matara Baba', 'Musari'],
            'GWI' => ['Buntusu', 'Dabi', 'Darina', 'F/Yamma', 'Guntai', 'Gwiwa', 'Korayel', 'Rorau', 'Shafe', 'Yola', 'Zaumar Sainawa'],
            'GWR' => ['Basirka', 'Dingaya', 'Fagam', 'Farin Dutse', 'Gwaram Tsohuwa', 'Kila', 'Kwandiko', 'Maruta', 'Sara', 'Tsangarwa', 'Zandan Nagogo'],
            'HAD' => ['Atafi', 'Dubantu', 'Gagulmari', 'Kasuwar Kuda', 'Kasuwar Kofa', 'Majema', 'Matsaro', 'Rumfa', 'Sabon Garu', 'Yankoli', 'Yayari'],
            'JAH' => ['Aujara', 'Gangawa', 'Gauza Tazara', 'Gunka', 'Harbo Sabuwa', 'Harbo Tsohuwa', 'Idanuna', 'Jabarna', 'Jahun', 'Kale', 'Kanwa'],
            'KAF' => ['Balangu', 'Dumadumin Toka', 'Gafaya', 'Jabo', 'Kafin Hausa', 'Kazalewa', 'Majawa', 'Mezan', 'Ruba', 'Sarawa', 'Zago'],
            'KAU' => ['Arbus', 'Askandu', 'Dabuwaran', 'Dakaiyawa', 'Hadin', "Ja'e", 'Jarkasa', 'Kaugama', 'Marke', 'Unguwar Jibrin', 'Yalo'],
            'KAZ' => ["Ba'auzini", 'Daba', 'Dabaza', 'Dandi', 'Gada', 'Kanti', 'Maradawa', 'Sabaru', 'Unguwar Arewa', 'Unguwar Gabas', 'Unguwar Yamma'],
            'KIR' => ['Baturiya', 'Bulunchai', 'Doleri', 'Fandum', 'Gayin', 'Kirika Samma', 'Madachi', 'Marma', 'Tsheguwa', 'Tarabu'],
            'KIY' => ['Abalago', 'Andaza', 'Fake', 'Garko', 'Guruduba', 'Katanga', 'Katuka', 'Kiyawa', 'Kwanda', 'Maje', 'Tsurma'],
            'MAI' => ['Balarabe', 'Dankumbo', 'Fulata', 'Galadi', 'Jajeri', 'Kukayasku', 'Madana', 'Maigatari Arewa', 'Maigatari Kudu', 'Matoya', 'Turbus'],
            'MAD' => ['Arki', 'Dunari', 'Fateka Akurya', 'Garin Gabas', 'Maira Kumi', 'Maka Ddari', 'Malam Madori', 'Shaiya', 'Tagwaro', 'Tashena', 'Tonikutara'],
            'MIG' => ['Dangyatin', 'Harbo', 'Hantsu', 'Koya', 'Miga', 'Sabon Gari Takanebu', 'Sansani', 'Tsakuwawa', 'Yanduna', 'Zareku'],
            'RIN' => ['Chai-Chai', 'Dabi', 'Kafin Babushe', 'Karshi', 'Kyarama', 'Ringim', 'Sankara', 'Sintilmawa', 'Tofa', 'Yandutse'],
            'RON' => ['Amaryawa', 'Baragumi', 'Dansure', 'Fara', 'Gora', 'Kwaita', 'Roni', 'Sankau', 'Tunas', 'Yanzaki', 'Zugai'],
            'SUL' => ['Albasu', 'Amanga', 'Dangwanki', 'Danladi', 'Danzomo', 'Jeke', 'Shabaru', 'Sule Tankarkar', 'Takatsaba', 'Yandamo'],
            'TAU' => ['Ajaura', 'Chakwaikwaiwa', 'Chukuto', 'Gujungu', 'Kiri', 'Kwalam', 'Maje', 'Majiya', 'S/Garin Yaya', 'Taura'],
            'YAN' => ['Achilafiya', 'Belas', 'Dawan Gawo', 'Gwarta', 'Gurjiya', 'Karkarna', 'Kuda', 'Ringim', 'Yankwashi', 'Zunbumba'],
        ];

        $count = 0;
        foreach ($wardData as $lgaCode => $wardNames) {
            $lga = Lga::where('code', $lgaCode)->first();
            if (!$lga) continue;

            $district = $districtMap[$lgaCode] ?? null;

            foreach ($wardNames as $index => $wardName) {
                $wardCode = $lgaCode . str_pad($index + 1, 3, '0', STR_PAD_LEFT);

                Ward::updateOrCreate(
                    ['code' => $wardCode],
                    [
                        'name' => $wardName,
                        'lga_id' => $lga->id,
                        'senatorial_district_id' => $district?->id,
                    ]
                );
                $count++;
            }
        }

        return $count;
    }

    /**
     * Seed wards for other states (template structure).
     * Creates basic ward structure based on LGA data.
     */
    private function seedStateWards(string $stateCode): int
    {
        $state = State::where('code', $stateCode)->first();
        if (!$state) return 0;

        // Get LGAs for this state
        $lgas = Lga::where('state_id', $state->id)->get();
        if ($lgas->isEmpty()) return 0;

        // Get senatorial districts for this state
        $districts = SenatorialDistrict::where('state_id', $state->id)->get();

        // Get state-specific ward data
        $wardData = $this->getStateWardData($stateCode);

        // If we have specific ward data for this state, use it
        if (!empty($wardData)) {
            return $this->seedSpecificWards($state, $lgas, $districts, $wardData);
        }

        // Otherwise, create placeholder wards (10 per LGA as template)
        return $this->seedTemplateWards($state, $lgas, $districts);
    }

    /**
     * Seed specific ward data for a state.
     */
    private function seedSpecificWards(State $state, $lgas, $districts, array $wardData): int
    {
        $count = 0;
        $lgaMap = $lgas->keyBy('code');
        $districtMap = $districts->keyBy('code');

        foreach ($wardData as $lgaCode => $wards) {
            $lga = $lgaMap[$lgaCode] ?? null;
            if (!$lga) continue;

            // Find the appropriate senatorial district for this LGA
            $district = $this->findDistrictForLga($lgaCode, $districtMap, $state->code);

            foreach ($wards as $index => $wardName) {
                $wardCode = $lgaCode . str_pad($index + 1, 3, '0', STR_PAD_LEFT);

                Ward::updateOrCreate(
                    ['code' => $wardCode],
                    [
                        'name' => $wardName,
                        'lga_id' => $lga->id,
                        'senatorial_district_id' => $district?->id,
                    ]
                );
                $count++;
            }
        }

        return $count;
    }

    /**
     * Create template wards for states without specific data.
     * Creates 10 placeholder wards per LGA.
     */
    private function seedTemplateWards(State $state, $lgas, $districts): int
    {
        $count = 0;

        foreach ($lgas as $lga) {
            // Assign to first district if available (placeholder logic)
            $district = $districts->first();

            // Create 10 template wards per LGA
            for ($i = 1; $i <= 10; $i++) {
                $wardCode = $lga->code . str_pad($i, 3, '0', STR_PAD_LEFT);
                $wardName = "{$lga->name} Ward {$i}";

                Ward::updateOrCreate(
                    ['code' => $wardCode],
                    [
                        'name' => $wardName,
                        'lga_id' => $lga->id,
                        'senatorial_district_id' => $district?->id,
                    ]
                );
                $count++;
            }
        }

        return $count;
    }

    /**
     * Find the appropriate senatorial district for an LGA.
     */
    private function findDistrictForLga(string $lgaCode, $districtMap, string $stateCode): ?SenatorialDistrict
    {
        // This would contain state-specific LGA->District mappings
        // For now, return null (to be populated with specific data)
        return null;
    }

    /**
     * Get state-specific ward data.
     * Add more states here as data becomes available.
     */
    private function getStateWardData(string $stateCode): array
    {
        // Ward data organized by state code
        $stateWards = [
            // KANO - Sample of 44 LGAs with wards (partial data for demonstration)
            'KAN' => [
                'KAN01' => ['Ajingi', 'Gachi', 'Fagwalawa', 'Kubarti', 'Yalwa', 'Famfo', 'Gamoji', 'Beli', 'Fanda', 'Zakirai'],
                'KAN07' => ['Dala', 'Kantudu', 'Kofar Mazugal', 'Gobirawa', 'Kabuwaya', 'Dogon Nama', 'Gwammaja', 'Kofar Dan Agundi', 'Tudun Nufawa', 'Yalwa'],
                // Add more Kano LGAs as data becomes available
            ],

            // LAGOS - Sample LGAs with wards
            'LAG' => [
                'LAG01' => ['Agege', 'Dopemu', 'Isale Oja', 'Oniwaya', 'Orile Agege', 'Papa Ashafa', 'Oko Oba', 'Cement', 'Oyewole', 'Ijaiye'],
                'LAG11' => ['Adeniyi Jones', 'Alausa', 'Anifowose', 'GRA Ikeja', 'Oba Akran', 'Ojodu', 'Onigbongbo', 'Wasimi', 'Wempco', 'Opebi'],
                // Add more Lagos LGAs as data becomes available
            ],

            // KADUNA - Sample LGAs with wards
            'KAD' => [
                'KAD09' => ['Hayin Banki', 'Kabala Costain', 'Kawo', 'Unguwar Dosa', 'Unguwar Rimi', 'Unguwar Sarki', 'Badarawa', 'Malali', 'Sardauna', 'Unguwar Mu\'azu'],
                'KAD23' => ['Zaria City', 'Dutsen Abba', 'Gyallesu', 'Kaura', 'Kufena', 'Kwarbai', 'Limancin Kona', 'Tudun Wada', 'Unguwan Juma', 'Wuciciri'],
                // Add more Kaduna LGAs as data becomes available
            ],

            // FCT - Complete ward data for 6 Area Councils
            'FCT' => [
                'FCT01' => ['Abaji Central', 'Agyana', 'Gawu', 'Gurdi', 'Nuku', 'Pandagi', 'Rimba', 'Sabo', 'Yaba', 'Alu'],
                'FCT02' => ['City Centre', 'Garki', 'Wuse', 'Kabusa', 'Orozo', 'Karshi', 'Nyanya', 'Karu', 'Jiwa', 'Gui'],
                'FCT03' => ['Bwari Central', 'Kawu', 'Kuduru', 'Kubwa', 'Byazhin', 'Dutse Alhaji', 'Usuma', 'Igu', 'Shere', 'Ushafa'],
                'FCT04' => ['Gwagwa', 'Ibwa', 'Ikwa', 'Kutunku', 'Paiko', 'Tungan Maje', 'Zuba', 'Dobi', 'Gwako', 'Central'],
                'FCT05' => ['Kuje Central', 'Chibiri', 'Gaube', 'Gwargwada', 'Kabi', 'Kuchiyako', 'Rubochi', 'Shadalafi', 'Yenche', 'Zango'],
                'FCT06' => ['Kwali Central', 'Dafa', 'Kilankwa', 'Pai', 'Yangoji', 'Yebu', 'Sheda', 'Gumbo', 'Kundu', 'Wako'],
            ],
        ];

        return $stateWards[$stateCode] ?? [];
    }
}
