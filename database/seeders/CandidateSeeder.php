<?php

namespace Database\Seeders;

use App\Models\Candidate;
use App\Models\Lga;
use App\Models\Party;
use Illuminate\Database\Seeder;

class CandidateSeeder extends Seeder
{
    /**
     * Seed representative gubernatorial + senatorial candidates for Jigawa State.
     * Gubernatorial candidates: one per major party (state-wide, no ward/lga).
     * Senatorial candidates: one per party per senatorial zone (3 zones).
     * House of Reps: one per party per LGA (27 LGAs × major parties).
     */
    public function run(): void
    {
        $parties = Party::all()->keyBy('acronym');

        if ($parties->isEmpty()) {
            $this->command->warn('PartySeeder must run first. Skipping CandidateSeeder.');
            return;
        }

        $lgas = Lga::all()->keyBy('code');

        // -----------------------------------------------------------------------
        // 1. Gubernatorial candidates (state-wide — no lga_id / ward_id)
        // -----------------------------------------------------------------------
        $gubernatorialCandidates = [
            ['name' => 'Umar Namadi',          'party' => 'APC'],
            ['name' => 'Auwal Liman Sankara',  'party' => 'PDP'],
            ['name' => 'Ibrahim Danladi Anka', 'party' => 'LP'],
            ['name' => 'Suleiman Umar Kwari',  'party' => 'NNPP'],
            ['name' => 'Abba Hussaini Gital',  'party' => 'APGA'],
        ];

        foreach ($gubernatorialCandidates as $c) {
            if (!isset($parties[$c['party']])) continue;
            Candidate::firstOrCreate(
                ['name' => $c['name'], 'post_type' => 'governorship', 'party_id' => $parties[$c['party']]->id],
                ['lga_id' => null, 'ward_id' => null]
            );
        }

        // -----------------------------------------------------------------------
        // 2. Senatorial candidates (3 senatorial districts)
        //    Jigawa North-East, Jigawa North-West, Jigawa South
        // -----------------------------------------------------------------------
        $senatorialZones = [
            'Jigawa North-East' => [
                ['name' => 'Malam Ibrahim Gumel',  'party' => 'APC'],
                ['name' => 'Sadiq Sule Tafoki',    'party' => 'PDP'],
                ['name' => 'Nasir Umar Gabari',    'party' => 'LP'],
            ],
            'Jigawa North-West' => [
                ['name' => 'Garba Muhammad Gwazo', 'party' => 'APC'],
                ['name' => 'Ibrahim Ahmad Kafin',  'party' => 'PDP'],
                ['name' => 'Musa Salisu Dutse',    'party' => 'LP'],
            ],
            'Jigawa South' => [
                ['name' => 'Ahmad Aliyu Ringim',   'party' => 'APC'],
                ['name' => 'Bashir Sani Kazaure',  'party' => 'PDP'],
                ['name' => 'Umar Hussain Birniwa', 'party' => 'LP'],
            ],
        ];

        foreach ($senatorialZones as $zone => $candidates) {
            foreach ($candidates as $c) {
                if (!isset($parties[$c['party']])) continue;
                Candidate::firstOrCreate(
                    ['name' => $c['name'], 'post_type' => 'senate', 'party_id' => $parties[$c['party']]->id],
                    ['lga_id' => null, 'ward_id' => null]
                );
            }
        }

        // -----------------------------------------------------------------------
        // 3. House of Representatives candidates — APC + PDP per LGA
        // -----------------------------------------------------------------------
        $houseByLga = [
            'DUT' => ['APC' => 'Ibrahim Sani Dutse',     'PDP' => 'Abdullahi Haruna Dutse'],
            'GML' => ['APC' => 'Salisu Musa Gumel',      'PDP' => 'Hamisu Aliyu Gumel'],
            'HAD' => ['APC' => 'Umar Tukur Hadejia',     'PDP' => 'Suleiman Barde Hadejia'],
            'KAZ' => ['APC' => 'Ahmad Musa Kazaure',     'PDP' => 'Bashir Sani Kazaure'],
            'KRN' => ['APC' => 'Auwal Lawal Kiri Kasama','PDP' => 'Isma\'il Adamu Kirika'],
            'MIG' => ['APC' => 'Yusuf Ibrahim Miga',     'PDP' => 'Garba Rabi\'u Miga'],
            'RNG' => ['APC' => 'Rabiu Ahmad Ringim',     'PDP' => 'Musa Sani Ringim'],
            'SHK' => ['APC' => 'Hamisu Umar Sule-Kaura', 'PDP' => 'Ahmad Lawal Sule-Kaura'],
            'TAU' => ['APC' => 'Kabiru Lawal Taura',     'PDP' => 'Umar Isa Taura'],
            'BAB' => ['APC' => 'Ibrahim Baba Babura',    'PDP' => 'Aliyu Hamisu Babura'],
            'BKU' => ['APC' => 'Sani Adamu Biriniwa',    'PDP' => 'Nasir Bello Birniwa'],
            'GRN' => ['APC' => 'Musa Lawal Gagarawa',    'PDP' => 'Rabi\'u Bello Gagarawa'],
            'GRK' => ['APC' => 'Ahmad Danladi Garki',    'PDP' => 'Sulieman Musa Garki'],
            'GRN2'=> ['APC' => 'Ibrahim Tukur Guri',     'PDP' => 'Hamisu Sani Guri'],
            'IFD' => ['APC' => 'Musa Ibrahim Itas-Gadau','PDP' => 'Umar Yusuf Itas'],
            'JAB' => ['APC' => 'Kabiru Sule Jahun',      'PDP' => 'Nasiru Lawal Jahun'],
            'KAF' => ['APC' => 'Ahmad Umar Kafin-Hausa', 'PDP' => 'Sani Hassan Kafin'],
            'KIY' => ['APC' => 'Lawal Sule Kiyawa',      'PDP' => 'Suleiman Barde Kiyawa'],
            'MAI' => ['APC' => 'Ahmad Bello Maigatari',  'PDP' => 'Umar Garga Maigatari'],
            'MAL' => ['APC' => 'Musa Umar Malammaduri',  'PDP' => 'Ibrahim Yusuf Malammaduri'],
            'SUL' => ['APC' => 'Rabi\'u Sule Malam Maduri','PDP' => 'Nasiru Ahmed Sule'],
            'TAF' => ['APC' => 'Kabiru Lawal Sule-Tankarkar','PDP' => 'Ahmad Rabi\'u Tankarkar'],
            'BRN' => ['APC' => 'Musa Aliyu Birniwa',     'PDP' => 'Umar Bello Birniwa'],
            'GDM' => ['APC' => 'Ibrahim Haruna Buji',    'PDP' => 'Lawal Hussain Buji'],
            'SMS' => ['APC' => 'Ahmad Sani Gwaram',      'PDP' => 'Kabiru Lawal Gwaram'],
            'YAN' => ['APC' => 'Sani Yusuf Yankwashi',   'PDP' => 'Umar Rabi\'u Yankwashi'],
            'GWR' => ['APC' => 'Lawal Ibrahim Gwiwa',    'PDP' => 'Ahmad Sani Gwiwa'],
        ];

        foreach ($houseByLga as $lgaCode => $partyCandidates) {
            $lga = $lgas->first(fn($l) => str_starts_with($l->code ?? '', $lgaCode));
            foreach ($partyCandidates as $partyAcronym => $candidateName) {
                if (!isset($parties[$partyAcronym])) continue;
                Candidate::firstOrCreate(
                    [
                        'name'      => $candidateName,
                        'post_type' => 'house_of_reps',
                        'party_id'  => $parties[$partyAcronym]->id,
                        'lga_id'    => $lga?->id,
                    ],
                    ['ward_id' => null]
                );
            }
        }

        $total = Candidate::count();
        $this->command->info("CandidateSeeder: {$total} candidates seeded.");
    }
}
