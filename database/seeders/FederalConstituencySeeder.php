<?php

namespace Database\Seeders;

use App\Models\FederalConstituency;
use App\Models\Lga;
use App\Models\State;
use Illuminate\Database\Seeder;

class FederalConstituencySeeder extends Seeder
{
    /**
     * Seed all 360 Federal Constituencies across Nigeria.
     * Also establishes the LGA-to-Constituency relationships.
     */
    public function run(): void
    {
        $states = State::all()->keyBy('code');

        if ($states->isEmpty()) {
            $this->command->error('StateSeeder must run first. Skipping FederalConstituencySeeder.');
            return;
        }

        // Federal Constituencies organized by state code
        // Each constituency maps to one or more LGAs
        $constituencies = $this->getConstituencyData();

        $count = 0;
        foreach ($constituencies as $stateCode => $stateConstituencies) {
            $state = $states[$stateCode] ?? null;
            if (!$state) {
                $this->command->warn("State {$stateCode} not found. Skipping its constituencies.");
                continue;
            }

            foreach ($stateConstituencies as $index => $constituency) {
                $name = $constituency['name'];
                $lgaCodes = $constituency['lgas'] ?? [];

                // Generate code: e.g., JIG-FC01
                $code = $stateCode . '-FC' . str_pad($index + 1, 2, '0', STR_PAD_LEFT);

                $fc = FederalConstituency::updateOrCreate(
                    ['code' => $code],
                    [
                        'name' => $name,
                        'state_id' => $state->id,
                    ]
                );

                // Sync LGA relationships if LGAs exist
                if (!empty($lgaCodes)) {
                    $lgaIds = Lga::whereIn('code', $lgaCodes)->pluck('id')->toArray();
                    if (!empty($lgaIds)) {
                        $fc->lgas()->syncWithoutDetaching($lgaIds);
                    }
                }

                $count++;
            }
        }

        $this->command->info("FederalConstituencySeeder: {$count} federal constituencies seeded.");
    }

    /**
     * Get all federal constituency data organized by state.
     * Data source: INEC Official List
     */
    private function getConstituencyData(): array
    {
        return [
            // =====================================================================
            // NORTH-CENTRAL ZONE
            // =====================================================================
            'BEN' => [
                // Benue - 11 Federal Constituencies
                ['name' => 'Ado/Okpokwu/Ogbadibo', 'lgas' => []],
                ['name' => 'Agatu/Apa', 'lgas' => []],
                ['name' => 'Buruku', 'lgas' => []],
                ['name' => 'Gboko/Tarka', 'lgas' => []],
                ['name' => 'Guma/Makurdi', 'lgas' => []],
                ['name' => 'Gwer East/Gwer West', 'lgas' => []],
                ['name' => 'Katsina-Ala/Ukum/Logo', 'lgas' => []],
                ['name' => 'Konshisha/Vandeikya', 'lgas' => []],
                ['name' => 'Kwande/Ushongo', 'lgas' => []],
                ['name' => 'Obi/Oju', 'lgas' => []],
                ['name' => 'Ohimini/Oturkpo', 'lgas' => []],
            ],
            'KOG' => [
                // Kogi - 9 Federal Constituencies
                ['name' => 'Adavi/Okehi', 'lgas' => []],
                ['name' => 'Ajaokuta', 'lgas' => []],
                ['name' => 'Ankpa/Olamaboro/Omala', 'lgas' => []],
                ['name' => 'Bassa/Dekina', 'lgas' => []],
                ['name' => 'Idah/Ibaji/Igalamela-Odolu', 'lgas' => []],
                ['name' => 'Ijumu/Kabba-Bunu/Mopa-Muro', 'lgas' => []],
                ['name' => 'Koton-Karfe/Kogi/Lokoja', 'lgas' => []],
                ['name' => 'Ogori-Magongo/Okene', 'lgas' => []],
                ['name' => 'Yagba East/Yagba West', 'lgas' => []],
            ],
            'KWA' => [
                // Kwara - 6 Federal Constituencies
                ['name' => 'Asa/Ilorin West', 'lgas' => []],
                ['name' => 'Baruten/Kaiama', 'lgas' => []],
                ['name' => 'Edu/Moro/Patigi', 'lgas' => []],
                ['name' => 'Ekiti/Irepodun/Isin/Oke-Ero', 'lgas' => []],
                ['name' => 'Ilorin East/Ilorin South', 'lgas' => []],
                ['name' => 'Offa/Oyun/Ifelodun', 'lgas' => []],
            ],
            'NAS' => [
                // Nasarawa - 5 Federal Constituencies
                ['name' => 'Akwanga/Nasarawa Eggon/Wamba', 'lgas' => []],
                ['name' => 'Awe/Doma/Keana', 'lgas' => []],
                ['name' => 'Karu/Keffi/Kokona', 'lgas' => []],
                ['name' => 'Lafia/Obi', 'lgas' => []],
                ['name' => 'Nasarawa/Toto', 'lgas' => []],
            ],
            'NIG' => [
                // Niger - 10 Federal Constituencies
                ['name' => 'Agaie/Lapai', 'lgas' => []],
                ['name' => 'Agwara/Borgu', 'lgas' => []],
                ['name' => 'Bida/Gbako/Katcha', 'lgas' => []],
                ['name' => 'Bosso/Paikoro', 'lgas' => []],
                ['name' => 'Chanchaga', 'lgas' => []],
                ['name' => 'Gurara/Suleja', 'lgas' => []],
                ['name' => 'Kontagora/Mariga/Mashegu', 'lgas' => []],
                ['name' => 'Lavun/Mokwa/Edati', 'lgas' => []],
                ['name' => 'Magama/Rijau', 'lgas' => []],
                ['name' => 'Munya/Shiroro/Rafi', 'lgas' => []],
            ],
            'PLA' => [
                // Plateau - 8 Federal Constituencies
                ['name' => 'Barkin Ladi/Riyom', 'lgas' => []],
                ['name' => 'Bokkos/Mangu', 'lgas' => []],
                ['name' => 'Jos East/Jos South', 'lgas' => []],
                ['name' => 'Jos North', 'lgas' => []],
                ['name' => 'Kanam/Wase', 'lgas' => []],
                ['name' => 'Langtang North/Langtang South', 'lgas' => []],
                ['name' => 'Mikang/Qua\'an Pan', 'lgas' => []],
                ['name' => 'Pankshin/Kanke/Kanam', 'lgas' => []],
            ],
            'FCT' => [
                // FCT - 2 Federal Constituencies
                ['name' => 'Abaji/Gwagwalada/Kuje/Kwali', 'lgas' => []],
                ['name' => 'AMAC/Bwari', 'lgas' => []],
            ],

            // =====================================================================
            // NORTH-EAST ZONE
            // =====================================================================
            'ADA' => [
                // Adamawa - 8 Federal Constituencies
                ['name' => 'Demsa/Numan/Lamurde', 'lgas' => []],
                ['name' => 'Fufore/Song', 'lgas' => []],
                ['name' => 'Ganye/Jada/Toungo/Mayo-Belwa', 'lgas' => []],
                ['name' => 'Girei/Yola North/Yola South', 'lgas' => []],
                ['name' => 'Gombi/Hong', 'lgas' => []],
                ['name' => 'Madagali/Michika', 'lgas' => []],
                ['name' => 'Maiha/Mubi North/Mubi South', 'lgas' => []],
                ['name' => 'Numan/Shelleng/Guyuk', 'lgas' => []],
            ],
            'BAU' => [
                // Bauchi - 12 Federal Constituencies
                ['name' => 'Alkaleri/Kirfi', 'lgas' => []],
                ['name' => 'Bauchi', 'lgas' => []],
                ['name' => 'Bogoro/Dass/Tafawa Balewa', 'lgas' => []],
                ['name' => 'Damban/Darazo/Ganjuwa', 'lgas' => []],
                ['name' => 'Gamawa', 'lgas' => []],
                ['name' => 'Itas/Gadau/Zaki', 'lgas' => []],
                ['name' => 'Jamaare/Katagum', 'lgas' => []],
                ['name' => 'Misau/Dambam', 'lgas' => []],
                ['name' => 'Ningi/Warji', 'lgas' => []],
                ['name' => 'Shira/Giade', 'lgas' => []],
                ['name' => 'Toro', 'lgas' => []],
                ['name' => 'Jama\'are/Katagum', 'lgas' => []],
            ],
            'BOR' => [
                // Borno - 10 Federal Constituencies
                ['name' => 'Askira/Uba/Hawul', 'lgas' => []],
                ['name' => 'Bama/Ngala/Kala-Balge', 'lgas' => []],
                ['name' => 'Biu/Bayo/Shani/Kwaya Kusar', 'lgas' => []],
                ['name' => 'Chibok/Damboa/Gwoza', 'lgas' => []],
                ['name' => 'Dikwa/Mafa/Konduga', 'lgas' => []],
                ['name' => 'Gubio/Kaga/Magumeri/Guzamala', 'lgas' => []],
                ['name' => 'Jere', 'lgas' => []],
                ['name' => 'Maiduguri', 'lgas' => []],
                ['name' => 'Marte/Monguno/Mobbar/Nganzai', 'lgas' => []],
                ['name' => 'Abadam/Kukawa', 'lgas' => []],
            ],
            'GOM' => [
                // Gombe - 6 Federal Constituencies
                ['name' => 'Akko', 'lgas' => []],
                ['name' => 'Balanga/Billiri', 'lgas' => []],
                ['name' => 'Dukku/Nafada', 'lgas' => []],
                ['name' => 'Funakaye/Kwami/Gombe', 'lgas' => []],
                ['name' => 'Kaltungo/Shongom', 'lgas' => []],
                ['name' => 'Yamaltu/Deba', 'lgas' => []],
            ],
            'TAR' => [
                // Taraba - 6 Federal Constituencies
                ['name' => 'Ardo-Kola/Jalingo/Yorro/Zing', 'lgas' => []],
                ['name' => 'Bali/Gassol', 'lgas' => []],
                ['name' => 'Donga/Takum/Ussa', 'lgas' => []],
                ['name' => 'Ibi/Wukari', 'lgas' => []],
                ['name' => 'Kurmi/Sardauna', 'lgas' => []],
                ['name' => 'Karim Lamido/Lau', 'lgas' => []],
            ],
            'YOB' => [
                // Yobe - 6 Federal Constituencies
                ['name' => 'Bade/Jakusko', 'lgas' => []],
                ['name' => 'Bursari/Geidam/Yunusari', 'lgas' => []],
                ['name' => 'Damaturu/Gujba/Tarmuwa/Gulani', 'lgas' => []],
                ['name' => 'Fika/Fune', 'lgas' => []],
                ['name' => 'Machina/Nguru/Karasuwa/Yusufari', 'lgas' => []],
                ['name' => 'Nangere/Potiskum', 'lgas' => []],
            ],

            // =====================================================================
            // NORTH-WEST ZONE
            // =====================================================================
            'JIG' => [
                // Jigawa - 11 Federal Constituencies
                ['name' => 'Auyo/Guri/Kirikasamma', 'lgas' => ['AUY', 'GUR', 'KIR']],
                ['name' => 'Babura/Garki', 'lgas' => ['BAB', 'GAR']],
                ['name' => 'Birniwa/Guri', 'lgas' => ['BIR', 'GUR']],
                ['name' => 'Birnin Kudu/Buji', 'lgas' => ['BKU', 'BUJ']],
                ['name' => 'Dutse/Kiyawa', 'lgas' => ['DUT', 'KIY']],
                ['name' => 'Gumel/Gagarawa/Maigatari', 'lgas' => ['GUM', 'GAG', 'MAI']],
                ['name' => 'Gwaram/Basirka', 'lgas' => ['GWR']],
                ['name' => 'Hadejia/Kafin Hausa/Auyo', 'lgas' => ['HAD', 'KAF', 'AUY']],
                ['name' => 'Jahun/Miga', 'lgas' => ['JAH', 'MIG']],
                ['name' => 'Kazaure/Roni/Gwiwa/Yankwashi', 'lgas' => ['KAZ', 'RON', 'GWI', 'YAN']],
                ['name' => 'Ringim/Taura/Sule Tankarkar', 'lgas' => ['RIN', 'TAU', 'SUL']],
            ],
            'KAD' => [
                // Kaduna - 16 Federal Constituencies
                ['name' => 'Birnin Gwari/Giwa', 'lgas' => []],
                ['name' => 'Chikun/Kajuru', 'lgas' => []],
                ['name' => 'Igabi', 'lgas' => []],
                ['name' => 'Jaba/Zango Kataf', 'lgas' => []],
                ['name' => 'Jema\'a/Sanga', 'lgas' => []],
                ['name' => 'Kaduna North', 'lgas' => []],
                ['name' => 'Kaduna South', 'lgas' => []],
                ['name' => 'Kagarko/Kaura', 'lgas' => []],
                ['name' => 'Kauru/Lere', 'lgas' => []],
                ['name' => 'Kubau/Ikara', 'lgas' => []],
                ['name' => 'Kudan/Makarfi', 'lgas' => []],
                ['name' => 'Sabon Gari', 'lgas' => []],
                ['name' => 'Soba/Zaria', 'lgas' => []],
                ['name' => 'Zaria', 'lgas' => []],
                ['name' => 'Lere', 'lgas' => []],
                ['name' => 'Kachia/Kagarko', 'lgas' => []],
            ],
            'KAN' => [
                // Kano - 24 Federal Constituencies
                ['name' => 'Albasu/Gaya/Ajingi', 'lgas' => []],
                ['name' => 'Bagwai/Shanono', 'lgas' => []],
                ['name' => 'Bebeji/Kiru', 'lgas' => []],
                ['name' => 'Bichi/Kunchi', 'lgas' => []],
                ['name' => 'Dala', 'lgas' => []],
                ['name' => 'Dawakin Kudu/Warawa', 'lgas' => []],
                ['name' => 'Dawakin Tofa/Tofa/Rimin Gado', 'lgas' => []],
                ['name' => 'Doguwa/Tudun Wada', 'lgas' => []],
                ['name' => 'Fagge', 'lgas' => []],
                ['name' => 'Gabasawa/Gezawa', 'lgas' => []],
                ['name' => 'Garko/Sumaila', 'lgas' => []],
                ['name' => 'Gwarzo/Kabo', 'lgas' => []],
                ['name' => 'Gwale', 'lgas' => []],
                ['name' => 'Kano Municipal', 'lgas' => []],
                ['name' => 'Karaye/Rogo', 'lgas' => []],
                ['name' => 'Kibiya/Rano', 'lgas' => []],
                ['name' => 'Kumbotso', 'lgas' => []],
                ['name' => 'Madobi/Makoda', 'lgas' => []],
                ['name' => 'Minjibir/Ungogo', 'lgas' => []],
                ['name' => 'Nasarawa', 'lgas' => []],
                ['name' => 'Takai/Sumaila', 'lgas' => []],
                ['name' => 'Tarauni', 'lgas' => []],
                ['name' => 'Tsanyawa/Kunchi', 'lgas' => []],
                ['name' => 'Wudil/Gaya', 'lgas' => []],
            ],
            'KAT' => [
                // Katsina - 15 Federal Constituencies
                ['name' => 'Bakori/Danja', 'lgas' => []],
                ['name' => 'Batagarawa/Rimi/Charanchi', 'lgas' => []],
                ['name' => 'Bindawa/Mani', 'lgas' => []],
                ['name' => 'Daura/Sandamu/Mai\'adua', 'lgas' => []],
                ['name' => 'Dutsin-Ma/Kurfi', 'lgas' => []],
                ['name' => 'Faskari/Sabuwa', 'lgas' => []],
                ['name' => 'Funtua/Dan-Musa', 'lgas' => []],
                ['name' => 'Ingawa/Kankiya/Kusada', 'lgas' => []],
                ['name' => 'Jibia/Kaita', 'lgas' => []],
                ['name' => 'Kafur/Malumfashi', 'lgas' => []],
                ['name' => 'Kankara/Faskari', 'lgas' => []],
                ['name' => 'Katsina', 'lgas' => []],
                ['name' => 'Mashi/Dutsi', 'lgas' => []],
                ['name' => 'Musawa/Matazu', 'lgas' => []],
                ['name' => 'Zango/Baure', 'lgas' => []],
            ],
            'KEB' => [
                // Kebbi - 8 Federal Constituencies
                ['name' => 'Aleiro/Gwandu/Jega', 'lgas' => []],
                ['name' => 'Arewa/Dandi', 'lgas' => []],
                ['name' => 'Argungu/Augie', 'lgas' => []],
                ['name' => 'Bagudo/Suru', 'lgas' => []],
                ['name' => 'Birnin Kebbi/Kalgo/Bunza', 'lgas' => []],
                ['name' => 'Koko/Besse/Maiyama', 'lgas' => []],
                ['name' => 'Ngaski/Shanga/Yauri', 'lgas' => []],
                ['name' => 'Zuru/Fakai/Sakaba/Danko-Wasagu', 'lgas' => []],
            ],
            'SOK' => [
                // Sokoto - 11 Federal Constituencies
                ['name' => 'Binji/Silame/Gudu', 'lgas' => []],
                ['name' => 'Bodinga/Dange-Shuni/Tureta', 'lgas' => []],
                ['name' => 'Goronyo/Gada', 'lgas' => []],
                ['name' => 'Gwadabawa/Illela', 'lgas' => []],
                ['name' => 'Isa/Sabon Birni', 'lgas' => []],
                ['name' => 'Kebbe/Tambuwal', 'lgas' => []],
                ['name' => 'Kware/Wamakko', 'lgas' => []],
                ['name' => 'Rabah/Wurno', 'lgas' => []],
                ['name' => 'Sokoto North/Sokoto South', 'lgas' => []],
                ['name' => 'Tangaza/Gudu', 'lgas' => []],
                ['name' => 'Yabo/Shagari', 'lgas' => []],
            ],
            'ZAM' => [
                // Zamfara - 7 Federal Constituencies
                ['name' => 'Anka/Talata Mafara', 'lgas' => []],
                ['name' => 'Bakura/Maradun', 'lgas' => []],
                ['name' => 'Birnin Magaji/Kaura Namoda', 'lgas' => []],
                ['name' => 'Bukkuyum/Zurmi/Shinkafi', 'lgas' => []],
                ['name' => 'Bungudu/Maru', 'lgas' => []],
                ['name' => 'Gummi/Bukkuyum', 'lgas' => []],
                ['name' => 'Gusau/Tsafe', 'lgas' => []],
            ],

            // =====================================================================
            // SOUTH-EAST ZONE
            // =====================================================================
            'ABI' => [
                // Abia - 8 Federal Constituencies
                ['name' => 'Aba North/South', 'lgas' => []],
                ['name' => 'Arochukwu/Ohafia', 'lgas' => []],
                ['name' => 'Bende', 'lgas' => []],
                ['name' => 'Ikwuano/Umuahia North/South', 'lgas' => []],
                ['name' => 'Isiala Ngwa North/South', 'lgas' => []],
                ['name' => 'Isuikwuato/Umunneochi', 'lgas' => []],
                ['name' => 'Obingwa/Osisioma/Ugwunagbo/Ukwa East/West', 'lgas' => []],
                ['name' => 'Ukwa East/West', 'lgas' => []],
            ],
            'ANA' => [
                // Anambra - 11 Federal Constituencies
                ['name' => 'Aguata', 'lgas' => []],
                ['name' => 'Anambra East/West', 'lgas' => []],
                ['name' => 'Anaocha/Njikoka/Dunukofia', 'lgas' => []],
                ['name' => 'Awka North/South', 'lgas' => []],
                ['name' => 'Ayamelum/Oyi', 'lgas' => []],
                ['name' => 'Ekwusigo/Nnewi North/South', 'lgas' => []],
                ['name' => 'Idemili North/South', 'lgas' => []],
                ['name' => 'Ihiala', 'lgas' => []],
                ['name' => 'Ogbaru', 'lgas' => []],
                ['name' => 'Onitsha North/South', 'lgas' => []],
                ['name' => 'Orumba North/South', 'lgas' => []],
            ],
            'EBO' => [
                // Ebonyi - 6 Federal Constituencies
                ['name' => 'Abakaliki/Izzi', 'lgas' => []],
                ['name' => 'Afikpo North/South', 'lgas' => []],
                ['name' => 'Ebonyi/Ohaukwu', 'lgas' => []],
                ['name' => 'Ezza North/South/Ikwo', 'lgas' => []],
                ['name' => 'Ishielu/Ivo', 'lgas' => []],
                ['name' => 'Ohaozara/Onicha/Ivo', 'lgas' => []],
            ],
            'ENU' => [
                // Enugu - 8 Federal Constituencies
                ['name' => 'Aninri/Awgu/Oji River', 'lgas' => []],
                ['name' => 'Awgu/Aninri/Oji-River', 'lgas' => []],
                ['name' => 'Enugu East/Isi-Uzo', 'lgas' => []],
                ['name' => 'Enugu North/South', 'lgas' => []],
                ['name' => 'Ezeagu/Udi', 'lgas' => []],
                ['name' => 'Igbo Etiti/Uzo-Uwani', 'lgas' => []],
                ['name' => 'Igbo Eze North/Udenu', 'lgas' => []],
                ['name' => 'Igbo Eze South/Nsukka', 'lgas' => []],
            ],
            'IMO' => [
                // Imo - 10 Federal Constituencies
                ['name' => 'Ahiazu Mbaise/Ezinihitte', 'lgas' => []],
                ['name' => 'Ehime Mbano/Ihitte/Uboma/Obowo', 'lgas' => []],
                ['name' => 'Ideato North/South', 'lgas' => []],
                ['name' => 'Isiala Mbano/Onuimo/Okigwe', 'lgas' => []],
                ['name' => 'Mbaitoli/Ikeduru', 'lgas' => []],
                ['name' => 'Ngor Okpala/Aboh Mbaise', 'lgas' => []],
                ['name' => 'Njaba/Nkwerre/Nwangele/Isu', 'lgas' => []],
                ['name' => 'Ohaji/Egbema/Oguta/Oru West', 'lgas' => []],
                ['name' => 'Okigwe/Onuimo/Isiala Mbano', 'lgas' => []],
                ['name' => 'Owerri', 'lgas' => []],
            ],

            // =====================================================================
            // SOUTH-SOUTH ZONE
            // =====================================================================
            'AKW' => [
                // Akwa Ibom - 10 Federal Constituencies
                ['name' => 'Abak/Etim Ekpo/Ika', 'lgas' => []],
                ['name' => 'Etinan/Nsit Ibom/Nsit Ubium', 'lgas' => []],
                ['name' => 'Eket/Onna/Esit-Eket/Ibeno', 'lgas' => []],
                ['name' => 'Ikono/Ini', 'lgas' => []],
                ['name' => 'Ikot Abasi/Mkpat Enin/Eastern Obolo', 'lgas' => []],
                ['name' => 'Ikot Ekpene/Essien Udim/Obot Akara', 'lgas' => []],
                ['name' => 'Itu/Ibiono Ibom', 'lgas' => []],
                ['name' => 'Okobo/Urue Offong/Oruko/Oron/Udung Uko/Mbo', 'lgas' => []],
                ['name' => 'Oron/Mbo/Okobo/Udung Uko/Urue Offong/Oruko', 'lgas' => []],
                ['name' => 'Uyo/Uruan/Nsit Atai/Ibesikpo Asutan', 'lgas' => []],
            ],
            'BAY' => [
                // Bayelsa - 5 Federal Constituencies
                ['name' => 'Brass/Nembe', 'lgas' => []],
                ['name' => 'Ekeremor/Sagbama', 'lgas' => []],
                ['name' => 'Kolokuma/Opokuma/Yenagoa', 'lgas' => []],
                ['name' => 'Ogbia', 'lgas' => []],
                ['name' => 'Southern Ijaw', 'lgas' => []],
            ],
            'CRO' => [
                // Cross River - 8 Federal Constituencies
                ['name' => 'Abi/Yakurr', 'lgas' => []],
                ['name' => 'Akamkpa/Biase', 'lgas' => []],
                ['name' => 'Bekwarra/Obudu/Obanliku', 'lgas' => []],
                ['name' => 'Boki/Ikom', 'lgas' => []],
                ['name' => 'Calabar Municipal/Odukpani', 'lgas' => []],
                ['name' => 'Calabar South/Akpabuyo/Bakassi', 'lgas' => []],
                ['name' => 'Obubra/Etung', 'lgas' => []],
                ['name' => 'Ogoja/Yala', 'lgas' => []],
            ],
            'DEL' => [
                // Delta - 10 Federal Constituencies
                ['name' => 'Aniocha/Oshimili', 'lgas' => []],
                ['name' => 'Bomadi/Patani', 'lgas' => []],
                ['name' => 'Burutu', 'lgas' => []],
                ['name' => 'Ethiope', 'lgas' => []],
                ['name' => 'Ika', 'lgas' => []],
                ['name' => 'Isoko', 'lgas' => []],
                ['name' => 'Ndokwa/Ukwuani', 'lgas' => []],
                ['name' => 'Okpe/Sapele/Uvwie', 'lgas' => []],
                ['name' => 'Ughelli', 'lgas' => []],
                ['name' => 'Warri', 'lgas' => []],
            ],
            'EDO' => [
                // Edo - 9 Federal Constituencies
                ['name' => 'Akoko-Edo', 'lgas' => []],
                ['name' => 'Egor/Ikpoba-Okha', 'lgas' => []],
                ['name' => 'Esan Central/Esan West/Igueben', 'lgas' => []],
                ['name' => 'Esan North-East/Esan South-East', 'lgas' => []],
                ['name' => 'Etsako', 'lgas' => []],
                ['name' => 'Ovia', 'lgas' => []],
                ['name' => 'Oredo', 'lgas' => []],
                ['name' => 'Orhionmwon/Uhunmwonde', 'lgas' => []],
                ['name' => 'Owan', 'lgas' => []],
            ],
            'RIV' => [
                // Rivers - 13 Federal Constituencies
                ['name' => 'Abua/Odual/Ahoada East', 'lgas' => []],
                ['name' => 'Ahoada West/Ogba/Egbema/Ndoni', 'lgas' => []],
                ['name' => 'Andoni/Opobo-Nkoro', 'lgas' => []],
                ['name' => 'Asari-Toru/Akuku-Toru', 'lgas' => []],
                ['name' => 'Bonny/Degema', 'lgas' => []],
                ['name' => 'Eleme/Oyigbo/Tai', 'lgas' => []],
                ['name' => 'Emohua/Ikwerre', 'lgas' => []],
                ['name' => 'Etche/Omuma', 'lgas' => []],
                ['name' => 'Gokana/Khana', 'lgas' => []],
                ['name' => 'Obio/Akpor', 'lgas' => []],
                ['name' => 'Okrika/Ogu-Bolo', 'lgas' => []],
                ['name' => 'Port Harcourt I', 'lgas' => []],
                ['name' => 'Port Harcourt II', 'lgas' => []],
            ],

            // =====================================================================
            // SOUTH-WEST ZONE
            // =====================================================================
            'EKI' => [
                // Ekiti - 6 Federal Constituencies
                ['name' => 'Ado-Ekiti/Irepodun-Ifelodun', 'lgas' => []],
                ['name' => 'Ekiti South West/Ikere/Ise-Orun', 'lgas' => []],
                ['name' => 'Emure/Gbonyin/Ekiti East', 'lgas' => []],
                ['name' => 'Ido-Osi/Moba/Ilejemeje', 'lgas' => []],
                ['name' => 'Ijero/Ekiti West/Efon', 'lgas' => []],
                ['name' => 'Oye/Ikole', 'lgas' => []],
            ],
            'LAG' => [
                // Lagos - 24 Federal Constituencies
                ['name' => 'Agege', 'lgas' => []],
                ['name' => 'Ajeromi-Ifelodun I', 'lgas' => []],
                ['name' => 'Ajeromi-Ifelodun II', 'lgas' => []],
                ['name' => 'Alimosho I', 'lgas' => []],
                ['name' => 'Alimosho II', 'lgas' => []],
                ['name' => 'Amuwo-Odofin/Ojo', 'lgas' => []],
                ['name' => 'Badagry', 'lgas' => []],
                ['name' => 'Epe', 'lgas' => []],
                ['name' => 'Eti-Osa I', 'lgas' => []],
                ['name' => 'Eti-Osa II', 'lgas' => []],
                ['name' => 'Ibeju-Lekki', 'lgas' => []],
                ['name' => 'Ifako-Ijaiye', 'lgas' => []],
                ['name' => 'Ikeja I', 'lgas' => []],
                ['name' => 'Ikeja II', 'lgas' => []],
                ['name' => 'Ikorodu I', 'lgas' => []],
                ['name' => 'Ikorodu II', 'lgas' => []],
                ['name' => 'Kosofe I', 'lgas' => []],
                ['name' => 'Kosofe II', 'lgas' => []],
                ['name' => 'Lagos Island I', 'lgas' => []],
                ['name' => 'Lagos Island II', 'lgas' => []],
                ['name' => 'Lagos Mainland', 'lgas' => []],
                ['name' => 'Mushin I', 'lgas' => []],
                ['name' => 'Mushin II', 'lgas' => []],
                ['name' => 'Oshodi-Isolo/Surulere', 'lgas' => []],
            ],
            'OGU' => [
                // Ogun - 9 Federal Constituencies
                ['name' => 'Ado-Odo/Ota', 'lgas' => []],
                ['name' => 'Abeokuta North/Odeda/Obafemi Owode', 'lgas' => []],
                ['name' => 'Abeokuta South', 'lgas' => []],
                ['name' => 'Egbado North/Imeko-Afon', 'lgas' => []],
                ['name' => 'Egbado South/Ipokia', 'lgas' => []],
                ['name' => 'Ifo/Ewekoro', 'lgas' => []],
                ['name' => 'Ijebu North/Ijebu East/Ogun Waterside', 'lgas' => []],
                ['name' => 'Ijebu-Ode/Odogbolu/Ikenne', 'lgas' => []],
                ['name' => 'Remo/Sagamu', 'lgas' => []],
            ],
            'OND' => [
                // Ondo - 9 Federal Constituencies
                ['name' => 'Akoko North East/Akoko North West', 'lgas' => []],
                ['name' => 'Akoko South East/Akoko South West', 'lgas' => []],
                ['name' => 'Akure North/Akure South', 'lgas' => []],
                ['name' => 'Idanre/Ifedore', 'lgas' => []],
                ['name' => 'Ese-Odo/Ilaje', 'lgas' => []],
                ['name' => 'Irele/Okitipupa', 'lgas' => []],
                ['name' => 'Odigbo/Ile-Oluji-Okeigbo', 'lgas' => []],
                ['name' => 'Ondo East/Ondo West', 'lgas' => []],
                ['name' => 'Owo/Ose', 'lgas' => []],
            ],
            'OSU' => [
                // Osun - 9 Federal Constituencies
                ['name' => 'Atakumosa East/West/Ilesa East/West', 'lgas' => []],
                ['name' => 'Ayedaade/Irewole/Isokan', 'lgas' => []],
                ['name' => 'Boluwaduro/Ifedayo/Ila', 'lgas' => []],
                ['name' => 'Ede North/Ede South/Egbedore/Ejigbo', 'lgas' => []],
                ['name' => 'Ife Central/Ife East/Ife North/Ife South', 'lgas' => []],
                ['name' => 'Ifelodun/Boripe/Odo-Otin', 'lgas' => []],
                ['name' => 'Irepodun/Orolu/Olorunda/Osogbo', 'lgas' => []],
                ['name' => 'Iwo/Ayedire/Ola-Oluwa', 'lgas' => []],
                ['name' => 'Obokun/Oriade', 'lgas' => []],
            ],
            'OYO' => [
                // Oyo - 14 Federal Constituencies
                ['name' => 'Afijio/Atiba/Oyo East/Oyo West', 'lgas' => []],
                ['name' => 'Akinyele/Lagelu', 'lgas' => []],
                ['name' => 'Egbeda/Ona-Ara', 'lgas' => []],
                ['name' => 'Ibadan North', 'lgas' => []],
                ['name' => 'Ibadan North East/Ibadan South East', 'lgas' => []],
                ['name' => 'Ibadan North West/Ibadan South West', 'lgas' => []],
                ['name' => 'Ibarapa Central/Ibarapa North', 'lgas' => []],
                ['name' => 'Ibarapa East/Ido', 'lgas' => []],
                ['name' => 'Irepo/Olorunsogo/Orelope', 'lgas' => []],
                ['name' => 'Iseyin/Itesiwaju/Iwajowa/Kajola', 'lgas' => []],
                ['name' => 'Ogbomosho North/Ogbomosho South/Oriire', 'lgas' => []],
                ['name' => 'Oluyole', 'lgas' => []],
                ['name' => 'Oyo/Surulere', 'lgas' => []],
                ['name' => 'Saki East/Saki West/Atisbo', 'lgas' => []],
            ],
        ];
    }
}
