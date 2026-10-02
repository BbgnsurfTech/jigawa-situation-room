<?php

namespace Database\Seeders;

use App\Models\Party;
use Illuminate\Database\Seeder;

class PartySeeder extends Seeder
{
    public function run(): void
    {
        $parties = [
            ['name' => 'All Progressives Congress',        'acronym' => 'APC',  'colour_hex' => '#3B82F6'],
            ['name' => "People's Democratic Party",        'acronym' => 'PDP',  'colour_hex' => '#EF4444'],
            ['name' => 'Labour Party',                     'acronym' => 'LP',   'colour_hex' => '#10B981'],
            ['name' => "New Nigeria People's Party",       'acronym' => 'NNPP', 'colour_hex' => '#F59E0B'],
            ['name' => 'All Progressives Grand Alliance',  'acronym' => 'APGA', 'colour_hex' => '#6366F1'],
            ['name' => 'Social Democratic Party',          'acronym' => 'SDP',  'colour_hex' => '#8B5CF6'],
            ['name' => 'Action Democratic Party',          'acronym' => 'ADP',  'colour_hex' => '#EC4899'],
            ['name' => 'Young Progressive Party',          'acronym' => 'YPP',  'colour_hex' => '#14B8A6'],
            ['name' => 'Zenith Labour Party',              'acronym' => 'ZLP',  'colour_hex' => '#F97316'],
            ['name' => 'African Democratic Congress',      'acronym' => 'ADC',  'colour_hex' => '#84CC16'],
        ];

        foreach ($parties as $p) {
            Party::firstOrCreate(['acronym' => $p['acronym']], $p);
        }
    }
}
