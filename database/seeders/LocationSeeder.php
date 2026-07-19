<?php

namespace Database\Seeders;

use App\Models\City;
use App\Models\Country;
use App\Models\CountryRegion;
use App\Models\State;
use Illuminate\Database\Seeder;

class LocationSeeder extends Seeder
{
    /**
     * Populates the full world country list (name + ISO2 code) since
     * that's a fixed, small reference set worth having complete.
     *
     * States and cities are NOT populated for every country — that's
     * hundreds of thousands of rows and not something worth hand-seeding.
     * Instead this seeds Nigeria's states/cities in full detail (36
     * states + FCT, with major cities per state), since that's the
     * deployment's home operating country. Staff add states/cities for
     * any other country they expand into from Setups → Location as
     * needed — the CRUD screens already support it.
     */
    public function run(): void
    {
        $countries = $this->worldCountries();

        foreach ($countries as $data) {
            Country::firstOrCreate(['code' => $data['code']], ['name' => $data['name']]);
        }

        $this->assignContinentsAndRegions();

        $nigeria = Country::where('code', 'NG')->first();

        if (! $nigeria) {
            return;
        }

        foreach ($this->nigeriaStatesAndCities() as $stateName => $data) {
            $state = State::firstOrCreate(
                ['country_id' => $nigeria->id, 'name' => $stateName],
                ['short_code' => $data['code']],
            );

            if (! $state->short_code) {
                $state->update(['short_code' => $data['code']]);
            }

            foreach ($data['cities'] as $cityName => $cityCode) {
                City::firstOrCreate(
                    ['state_id' => $state->id, 'name' => $cityName],
                    ['short_code' => $cityCode],
                );
            }
        }
    }

    private function worldCountries(): array
    {
        return [
            ['name' => 'Afghanistan', 'code' => 'AF'], ['name' => 'Albania', 'code' => 'AL'],
            ['name' => 'Algeria', 'code' => 'DZ'], ['name' => 'Andorra', 'code' => 'AD'],
            ['name' => 'Angola', 'code' => 'AO'], ['name' => 'Argentina', 'code' => 'AR'],
            ['name' => 'Armenia', 'code' => 'AM'], ['name' => 'Australia', 'code' => 'AU'],
            ['name' => 'Austria', 'code' => 'AT'], ['name' => 'Azerbaijan', 'code' => 'AZ'],
            ['name' => 'Bahamas', 'code' => 'BS'], ['name' => 'Bahrain', 'code' => 'BH'],
            ['name' => 'Bangladesh', 'code' => 'BD'], ['name' => 'Barbados', 'code' => 'BB'],
            ['name' => 'Belarus', 'code' => 'BY'], ['name' => 'Belgium', 'code' => 'BE'],
            ['name' => 'Belize', 'code' => 'BZ'], ['name' => 'Benin', 'code' => 'BJ'],
            ['name' => 'Bhutan', 'code' => 'BT'], ['name' => 'Bolivia', 'code' => 'BO'],
            ['name' => 'Bosnia and Herzegovina', 'code' => 'BA'], ['name' => 'Botswana', 'code' => 'BW'],
            ['name' => 'Brazil', 'code' => 'BR'], ['name' => 'Brunei', 'code' => 'BN'],
            ['name' => 'Bulgaria', 'code' => 'BG'], ['name' => 'Burkina Faso', 'code' => 'BF'],
            ['name' => 'Burundi', 'code' => 'BI'], ['name' => 'Cambodia', 'code' => 'KH'],
            ['name' => 'Cameroon', 'code' => 'CM'], ['name' => 'Canada', 'code' => 'CA'],
            ['name' => 'Cape Verde', 'code' => 'CV'], ['name' => 'Central African Republic', 'code' => 'CF'],
            ['name' => 'Chad', 'code' => 'TD'], ['name' => 'Chile', 'code' => 'CL'],
            ['name' => 'China', 'code' => 'CN'], ['name' => 'Colombia', 'code' => 'CO'],
            ['name' => 'Comoros', 'code' => 'KM'], ['name' => 'Congo (DRC)', 'code' => 'CD'],
            ['name' => 'Congo (Republic)', 'code' => 'CG'], ['name' => 'Costa Rica', 'code' => 'CR'],
            ['name' => "Côte d'Ivoire", 'code' => 'CI'], ['name' => 'Croatia', 'code' => 'HR'],
            ['name' => 'Cuba', 'code' => 'CU'], ['name' => 'Cyprus', 'code' => 'CY'],
            ['name' => 'Czechia', 'code' => 'CZ'], ['name' => 'Denmark', 'code' => 'DK'],
            ['name' => 'Djibouti', 'code' => 'DJ'], ['name' => 'Dominica', 'code' => 'DM'],
            ['name' => 'Dominican Republic', 'code' => 'DO'], ['name' => 'Ecuador', 'code' => 'EC'],
            ['name' => 'Egypt', 'code' => 'EG'], ['name' => 'El Salvador', 'code' => 'SV'],
            ['name' => 'Equatorial Guinea', 'code' => 'GQ'], ['name' => 'Eritrea', 'code' => 'ER'],
            ['name' => 'Estonia', 'code' => 'EE'], ['name' => 'Eswatini', 'code' => 'SZ'],
            ['name' => 'Ethiopia', 'code' => 'ET'], ['name' => 'Fiji', 'code' => 'FJ'],
            ['name' => 'Finland', 'code' => 'FI'], ['name' => 'France', 'code' => 'FR'],
            ['name' => 'Gabon', 'code' => 'GA'], ['name' => 'Gambia', 'code' => 'GM'],
            ['name' => 'Georgia', 'code' => 'GE'], ['name' => 'Germany', 'code' => 'DE'],
            ['name' => 'Ghana', 'code' => 'GH'], ['name' => 'Greece', 'code' => 'GR'],
            ['name' => 'Grenada', 'code' => 'GD'], ['name' => 'Guatemala', 'code' => 'GT'],
            ['name' => 'Guinea', 'code' => 'GN'], ['name' => 'Guinea-Bissau', 'code' => 'GW'],
            ['name' => 'Guyana', 'code' => 'GY'], ['name' => 'Haiti', 'code' => 'HT'],
            ['name' => 'Honduras', 'code' => 'HN'], ['name' => 'Hungary', 'code' => 'HU'],
            ['name' => 'Iceland', 'code' => 'IS'], ['name' => 'India', 'code' => 'IN'],
            ['name' => 'Indonesia', 'code' => 'ID'], ['name' => 'Iran', 'code' => 'IR'],
            ['name' => 'Iraq', 'code' => 'IQ'], ['name' => 'Ireland', 'code' => 'IE'],
            ['name' => 'Israel', 'code' => 'IL'], ['name' => 'Italy', 'code' => 'IT'],
            ['name' => 'Jamaica', 'code' => 'JM'], ['name' => 'Japan', 'code' => 'JP'],
            ['name' => 'Jordan', 'code' => 'JO'], ['name' => 'Kazakhstan', 'code' => 'KZ'],
            ['name' => 'Kenya', 'code' => 'KE'], ['name' => 'Kiribati', 'code' => 'KI'],
            ['name' => 'Kuwait', 'code' => 'KW'], ['name' => 'Kyrgyzstan', 'code' => 'KG'],
            ['name' => 'Laos', 'code' => 'LA'], ['name' => 'Latvia', 'code' => 'LV'],
            ['name' => 'Lebanon', 'code' => 'LB'], ['name' => 'Lesotho', 'code' => 'LS'],
            ['name' => 'Liberia', 'code' => 'LR'], ['name' => 'Libya', 'code' => 'LY'],
            ['name' => 'Liechtenstein', 'code' => 'LI'], ['name' => 'Lithuania', 'code' => 'LT'],
            ['name' => 'Luxembourg', 'code' => 'LU'], ['name' => 'Madagascar', 'code' => 'MG'],
            ['name' => 'Malawi', 'code' => 'MW'], ['name' => 'Malaysia', 'code' => 'MY'],
            ['name' => 'Maldives', 'code' => 'MV'], ['name' => 'Mali', 'code' => 'ML'],
            ['name' => 'Malta', 'code' => 'MT'], ['name' => 'Mauritania', 'code' => 'MR'],
            ['name' => 'Mauritius', 'code' => 'MU'], ['name' => 'Mexico', 'code' => 'MX'],
            ['name' => 'Moldova', 'code' => 'MD'], ['name' => 'Monaco', 'code' => 'MC'],
            ['name' => 'Mongolia', 'code' => 'MN'], ['name' => 'Montenegro', 'code' => 'ME'],
            ['name' => 'Morocco', 'code' => 'MA'], ['name' => 'Mozambique', 'code' => 'MZ'],
            ['name' => 'Myanmar', 'code' => 'MM'], ['name' => 'Namibia', 'code' => 'NA'],
            ['name' => 'Nepal', 'code' => 'NP'], ['name' => 'Netherlands', 'code' => 'NL'],
            ['name' => 'New Zealand', 'code' => 'NZ'], ['name' => 'Nicaragua', 'code' => 'NI'],
            ['name' => 'Niger', 'code' => 'NE'], ['name' => 'Nigeria', 'code' => 'NG'],
            ['name' => 'North Korea', 'code' => 'KP'], ['name' => 'North Macedonia', 'code' => 'MK'],
            ['name' => 'Norway', 'code' => 'NO'], ['name' => 'Oman', 'code' => 'OM'],
            ['name' => 'Pakistan', 'code' => 'PK'], ['name' => 'Panama', 'code' => 'PA'],
            ['name' => 'Papua New Guinea', 'code' => 'PG'], ['name' => 'Paraguay', 'code' => 'PY'],
            ['name' => 'Peru', 'code' => 'PE'], ['name' => 'Philippines', 'code' => 'PH'],
            ['name' => 'Poland', 'code' => 'PL'], ['name' => 'Portugal', 'code' => 'PT'],
            ['name' => 'Qatar', 'code' => 'QA'], ['name' => 'Romania', 'code' => 'RO'],
            ['name' => 'Russia', 'code' => 'RU'], ['name' => 'Rwanda', 'code' => 'RW'],
            ['name' => 'Saudi Arabia', 'code' => 'SA'], ['name' => 'Senegal', 'code' => 'SN'],
            ['name' => 'Serbia', 'code' => 'RS'], ['name' => 'Sierra Leone', 'code' => 'SL'],
            ['name' => 'Singapore', 'code' => 'SG'], ['name' => 'Slovakia', 'code' => 'SK'],
            ['name' => 'Slovenia', 'code' => 'SI'], ['name' => 'Somalia', 'code' => 'SO'],
            ['name' => 'South Africa', 'code' => 'ZA'], ['name' => 'South Korea', 'code' => 'KR'],
            ['name' => 'South Sudan', 'code' => 'SS'], ['name' => 'Spain', 'code' => 'ES'],
            ['name' => 'Sri Lanka', 'code' => 'LK'], ['name' => 'Sudan', 'code' => 'SD'],
            ['name' => 'Suriname', 'code' => 'SR'], ['name' => 'Sweden', 'code' => 'SE'],
            ['name' => 'Switzerland', 'code' => 'CH'], ['name' => 'Syria', 'code' => 'SY'],
            ['name' => 'Taiwan', 'code' => 'TW'], ['name' => 'Tajikistan', 'code' => 'TJ'],
            ['name' => 'Tanzania', 'code' => 'TZ'], ['name' => 'Thailand', 'code' => 'TH'],
            ['name' => 'Togo', 'code' => 'TG'], ['name' => 'Trinidad and Tobago', 'code' => 'TT'],
            ['name' => 'Tunisia', 'code' => 'TN'], ['name' => 'Turkey', 'code' => 'TR'],
            ['name' => 'Turkmenistan', 'code' => 'TM'], ['name' => 'Uganda', 'code' => 'UG'],
            ['name' => 'Ukraine', 'code' => 'UA'], ['name' => 'United Arab Emirates', 'code' => 'AE'],
            ['name' => 'United Kingdom', 'code' => 'GB'], ['name' => 'United States', 'code' => 'US'],
            ['name' => 'Uruguay', 'code' => 'UY'], ['name' => 'Uzbekistan', 'code' => 'UZ'],
            ['name' => 'Vanuatu', 'code' => 'VU'], ['name' => 'Venezuela', 'code' => 'VE'],
            ['name' => 'Vietnam', 'code' => 'VN'], ['name' => 'Yemen', 'code' => 'YE'],
            ['name' => 'Zambia', 'code' => 'ZM'], ['name' => 'Zimbabwe', 'code' => 'ZW'],
        ];
    }

    /**
     * All 36 Nigerian states + FCT, each with a unique 2-letter short_code
     * and a practical set of major cities (state capital plus one or two
     * other commercial centers), each with its own short_code unique
     * within that state — not an exhaustive city list. Add more from
     * Setups → Location → Cities as needed.
     */
    private function nigeriaStatesAndCities(): array
    {
        return [
            'Abia' => ['code' => 'AB', 'cities' => ['Umuahia' => 'UMU', 'Aba' => 'ABA', 'Arochukwu' => 'ARO', 'Ohafia' => 'OHA', 'Isiala Ngwa' => 'ISN']],
            'Adamawa' => ['code' => 'AD', 'cities' => ['Yola' => 'YOL', 'Mubi' => 'MUB', 'Numan' => 'NUM', 'Ganye' => 'GAN', 'Jimeta' => 'JIM']],
            'Akwa Ibom' => ['code' => 'AK', 'cities' => ['Uyo' => 'UYO', 'Ikot Ekpene' => 'IKO', 'Eket' => 'EKE', 'Oron' => 'ORO', 'Abak' => 'ABK']],
            'Anambra' => ['code' => 'AN', 'cities' => ['Awka' => 'AWK', 'Onitsha' => 'ONI', 'Nnewi' => 'NNE', 'Ekwulobia' => 'EKW', 'Aguata' => 'AGU']],
            'Bauchi' => ['code' => 'BA', 'cities' => ['Bauchi' => 'BAU', 'Azare' => 'AZA', 'Misau' => 'MIS', "Jama'are" => 'JAM', 'Ningi' => 'NIN']],
            'Bayelsa' => ['code' => 'BY', 'cities' => ['Yenagoa' => 'YEN', 'Brass' => 'BRA', 'Sagbama' => 'SAG', 'Ogbia' => 'OGB', 'Nembe' => 'NEM']],
            'Benue' => ['code' => 'BE', 'cities' => ['Makurdi' => 'MAK', 'Gboko' => 'GBO', 'Otukpo' => 'OTU', 'Katsina-Ala' => 'KTA', 'Vandeikya' => 'VAN']],
            'Borno' => ['code' => 'BO', 'cities' => ['Maiduguri' => 'MAI', 'Bama' => 'BAM', 'Biu' => 'BIU', 'Dikwa' => 'DIK', 'Gwoza' => 'GWO']],
            'Cross River' => ['code' => 'CR', 'cities' => ['Calabar' => 'CAL', 'Ikom' => 'IKM', 'Ogoja' => 'OGJ', 'Obudu' => 'OBU', 'Ugep' => 'UGE']],
            'Delta' => ['code' => 'DE', 'cities' => ['Asaba' => 'ASA', 'Warri' => 'WAR', 'Sapele' => 'SAP', 'Ughelli' => 'UGH', 'Agbor' => 'AGB']],
            'Ebonyi' => ['code' => 'EB', 'cities' => ['Abakaliki' => 'ABK2', 'Afikpo' => 'AFI', 'Onueke' => 'ONU', 'Ezza' => 'EZZ']],
            'Edo' => ['code' => 'ED', 'cities' => ['Benin City' => 'BEN', 'Ekpoma' => 'EKP', 'Auchi' => 'AUC', 'Uromi' => 'URO', 'Igarra' => 'IGA']],
            'Ekiti' => ['code' => 'EK', 'cities' => ['Ado-Ekiti' => 'ADO', 'Ikere-Ekiti' => 'IKR', 'Ilawe-Ekiti' => 'ILW', 'Efon-Alaaye' => 'EFO']],
            'Enugu' => ['code' => 'EN', 'cities' => ['Enugu' => 'ENU', 'Nsukka' => 'NSU', 'Awgu' => 'AWG', 'Oji River' => 'OJI', 'Udi' => 'UDI']],
            'FCT' => ['code' => 'FC', 'cities' => ['Abuja' => 'ABU', 'Gwagwalada' => 'GWA', 'Kuje' => 'KUJ', 'Bwari' => 'BWA', 'Kubwa' => 'KUB']],
            'Gombe' => ['code' => 'GO', 'cities' => ['Gombe' => 'GOM', 'Kaltungo' => 'KAL', 'Dukku' => 'DUK', 'Billiri' => 'BIL']],
            'Imo' => ['code' => 'IM', 'cities' => ['Owerri' => 'OWE', 'Orlu' => 'ORL', 'Okigwe' => 'OKI', 'Mbaise' => 'MBA']],
            'Jigawa' => ['code' => 'JI', 'cities' => ['Dutse' => 'DUT', 'Hadejia' => 'HAD', 'Gumel' => 'GUM', 'Kazaure' => 'KAZ']],
            'Kaduna' => ['code' => 'KD', 'cities' => ['Kaduna' => 'KAD', 'Zaria' => 'ZAR', 'Kafanchan' => 'KAF', 'Kagoro' => 'KAG']],
            'Kano' => ['code' => 'KN', 'cities' => ['Kano' => 'KAN', 'Wudil' => 'WUD', 'Gwarzo' => 'GWZ', 'Rano' => 'RAN']],
            'Katsina' => ['code' => 'KT', 'cities' => ['Katsina' => 'KAT', 'Funtua' => 'FUN', 'Daura' => 'DAU', 'Malumfashi' => 'MAL']],
            'Kebbi' => ['code' => 'KE', 'cities' => ['Birnin Kebbi' => 'BIR', 'Argungu' => 'ARG', 'Yauri' => 'YAU', 'Zuru' => 'ZUR']],
            'Kogi' => ['code' => 'KO', 'cities' => ['Lokoja' => 'LOK', 'Okene' => 'OKE', 'Idah' => 'IDA', 'Kabba' => 'KBB']],
            'Kwara' => ['code' => 'KW', 'cities' => ['Ilorin' => 'ILO', 'Offa' => 'OFF', 'Omu-Aran' => 'OMU', 'Jebba' => 'JEB']],
            'Lagos' => ['code' => 'LA', 'cities' => ['Ikeja' => 'IKE', 'Lagos Island' => 'LAG', 'Surulere' => 'SUR', 'Lekki' => 'LEK', 'Ikorodu' => 'IKD', 'Badagry' => 'BAD', 'Epe' => 'EPE', 'Apapa' => 'APA', 'Yaba' => 'YAB', 'Ajah' => 'AJA']],
            'Nasarawa' => ['code' => 'NA', 'cities' => ['Lafia' => 'LAF', 'Keffi' => 'KEF', 'Akwanga' => 'AKW', 'Nasarawa' => 'NAS']],
            'Niger' => ['code' => 'NI', 'cities' => ['Minna' => 'MIN', 'Bida' => 'BID', 'Kontagora' => 'KON', 'Suleja' => 'SUL']],
            'Ogun' => ['code' => 'OG', 'cities' => ['Abeokuta' => 'ABE', 'Sagamu' => 'SAG2', 'Ijebu-Ode' => 'IJE', 'Ota' => 'OTA', 'Ilaro' => 'ILA']],
            'Ondo' => ['code' => 'ON', 'cities' => ['Akure' => 'AKU', 'Ondo City' => 'OND', 'Owo' => 'OWO', 'Okitipupa' => 'OKT']],
            'Osun' => ['code' => 'OS', 'cities' => ['Osogbo' => 'OSO', 'Ile-Ife' => 'ILE', 'Ilesa' => 'ILS', 'Ede' => 'EDE']],
            'Oyo' => ['code' => 'OY', 'cities' => ['Ibadan' => 'IBA', 'Ogbomoso' => 'OGB', 'Iseyin' => 'ISE', 'Saki' => 'SAK']],
            'Plateau' => ['code' => 'PL', 'cities' => ['Jos' => 'JOS', 'Bukuru' => 'BUK', 'Pankshin' => 'PAN', 'Shendam' => 'SHE']],
            'Rivers' => ['code' => 'RI', 'cities' => ['Port Harcourt' => 'POR', 'Bonny' => 'BON', 'Ahoada' => 'AHO', 'Okrika' => 'OKR', 'Eleme' => 'ELE']],
            'Sokoto' => ['code' => 'SO', 'cities' => ['Sokoto' => 'SOK', 'Wurno' => 'WUR', 'Tambuwal' => 'TAM', 'Illela' => 'ILL']],
            'Taraba' => ['code' => 'TA', 'cities' => ['Jalingo' => 'JAL', 'Wukari' => 'WUK', 'Bali' => 'BAL', 'Gembu' => 'GEM']],
            'Yobe' => ['code' => 'YO', 'cities' => ['Damaturu' => 'DAM', 'Potiskum' => 'POT', 'Gashua' => 'GAS', 'Nguru' => 'NGU']],
            'Zamfara' => ['code' => 'ZA', 'cities' => ['Gusau' => 'GUS', 'Kaura Namoda' => 'KAU', 'Talata Mafara' => 'TAL']],
        ];
    }

    /**
     * A starting point, not a fixed answer — staff can rename or
     * regroup any of this from Setups → Location → Country Regions
     * (rename a region, or reassign a country to a different one from
     * its edit page). Region breakdown roughly follows the UN M49
     * sub-region scheme, merged down to 19 groups; transcontinental
     * countries (Turkey, Armenia, Azerbaijan, Georgia) are grouped under
     * Western Asia here, a common convention for logistics purposes.
     */
    private function assignContinentsAndRegions(): void
    {
        $data = [
            // code => [continent, region]
            'DZ' => ['Africa', 'Northern Africa'], 'EG' => ['Africa', 'Northern Africa'], 'LY' => ['Africa', 'Northern Africa'],
            'MA' => ['Africa', 'Northern Africa'], 'SD' => ['Africa', 'Northern Africa'], 'TN' => ['Africa', 'Northern Africa'],

            'BJ' => ['Africa', 'Western Africa'], 'BF' => ['Africa', 'Western Africa'], 'CV' => ['Africa', 'Western Africa'],
            'CI' => ['Africa', 'Western Africa'], 'GM' => ['Africa', 'Western Africa'], 'GH' => ['Africa', 'Western Africa'],
            'GN' => ['Africa', 'Western Africa'], 'GW' => ['Africa', 'Western Africa'], 'LR' => ['Africa', 'Western Africa'],
            'ML' => ['Africa', 'Western Africa'], 'MR' => ['Africa', 'Western Africa'], 'NE' => ['Africa', 'Western Africa'],
            'NG' => ['Africa', 'Western Africa'], 'SN' => ['Africa', 'Western Africa'], 'SL' => ['Africa', 'Western Africa'],
            'TG' => ['Africa', 'Western Africa'],

            'AO' => ['Africa', 'Middle Africa'], 'CM' => ['Africa', 'Middle Africa'], 'CF' => ['Africa', 'Middle Africa'],
            'TD' => ['Africa', 'Middle Africa'], 'CG' => ['Africa', 'Middle Africa'], 'CD' => ['Africa', 'Middle Africa'],
            'GQ' => ['Africa', 'Middle Africa'], 'GA' => ['Africa', 'Middle Africa'],

            'BI' => ['Africa', 'Eastern Africa'], 'KM' => ['Africa', 'Eastern Africa'], 'DJ' => ['Africa', 'Eastern Africa'],
            'ER' => ['Africa', 'Eastern Africa'], 'ET' => ['Africa', 'Eastern Africa'], 'KE' => ['Africa', 'Eastern Africa'],
            'MG' => ['Africa', 'Eastern Africa'], 'MW' => ['Africa', 'Eastern Africa'], 'MU' => ['Africa', 'Eastern Africa'],
            'MZ' => ['Africa', 'Eastern Africa'], 'RW' => ['Africa', 'Eastern Africa'], 'SO' => ['Africa', 'Eastern Africa'],
            'SS' => ['Africa', 'Eastern Africa'], 'TZ' => ['Africa', 'Eastern Africa'], 'UG' => ['Africa', 'Eastern Africa'],
            'ZM' => ['Africa', 'Eastern Africa'], 'ZW' => ['Africa', 'Eastern Africa'],

            'BW' => ['Africa', 'Southern Africa'], 'SZ' => ['Africa', 'Southern Africa'], 'LS' => ['Africa', 'Southern Africa'],
            'NA' => ['Africa', 'Southern Africa'], 'ZA' => ['Africa', 'Southern Africa'],

            'DK' => ['Europe', 'Northern Europe'], 'EE' => ['Europe', 'Northern Europe'], 'FI' => ['Europe', 'Northern Europe'],
            'IS' => ['Europe', 'Northern Europe'], 'IE' => ['Europe', 'Northern Europe'], 'LV' => ['Europe', 'Northern Europe'],
            'LT' => ['Europe', 'Northern Europe'], 'NO' => ['Europe', 'Northern Europe'], 'SE' => ['Europe', 'Northern Europe'],
            'GB' => ['Europe', 'Northern Europe'],

            'AT' => ['Europe', 'Western Europe'], 'BE' => ['Europe', 'Western Europe'], 'FR' => ['Europe', 'Western Europe'],
            'DE' => ['Europe', 'Western Europe'], 'LI' => ['Europe', 'Western Europe'], 'LU' => ['Europe', 'Western Europe'],
            'MC' => ['Europe', 'Western Europe'], 'NL' => ['Europe', 'Western Europe'], 'CH' => ['Europe', 'Western Europe'],

            'AD' => ['Europe', 'Southern Europe'], 'HR' => ['Europe', 'Southern Europe'], 'CY' => ['Europe', 'Southern Europe'],
            'GR' => ['Europe', 'Southern Europe'], 'IT' => ['Europe', 'Southern Europe'], 'MT' => ['Europe', 'Southern Europe'],
            'PT' => ['Europe', 'Southern Europe'], 'ES' => ['Europe', 'Southern Europe'], 'SI' => ['Europe', 'Southern Europe'],

            'AL' => ['Europe', 'Eastern Europe'], 'BY' => ['Europe', 'Eastern Europe'], 'BA' => ['Europe', 'Eastern Europe'],
            'BG' => ['Europe', 'Eastern Europe'], 'CZ' => ['Europe', 'Eastern Europe'], 'HU' => ['Europe', 'Eastern Europe'],
            'MD' => ['Europe', 'Eastern Europe'], 'ME' => ['Europe', 'Eastern Europe'], 'MK' => ['Europe', 'Eastern Europe'],
            'PL' => ['Europe', 'Eastern Europe'], 'RO' => ['Europe', 'Eastern Europe'], 'RU' => ['Europe', 'Eastern Europe'],
            'RS' => ['Europe', 'Eastern Europe'], 'SK' => ['Europe', 'Eastern Europe'], 'UA' => ['Europe', 'Eastern Europe'],

            'BH' => ['Asia', 'Western Asia'], 'IQ' => ['Asia', 'Western Asia'], 'IL' => ['Asia', 'Western Asia'],
            'JO' => ['Asia', 'Western Asia'], 'KW' => ['Asia', 'Western Asia'], 'LB' => ['Asia', 'Western Asia'],
            'OM' => ['Asia', 'Western Asia'], 'QA' => ['Asia', 'Western Asia'], 'SA' => ['Asia', 'Western Asia'],
            'SY' => ['Asia', 'Western Asia'], 'AE' => ['Asia', 'Western Asia'], 'YE' => ['Asia', 'Western Asia'],
            'TR' => ['Asia', 'Western Asia'], 'AM' => ['Asia', 'Western Asia'], 'AZ' => ['Asia', 'Western Asia'],
            'GE' => ['Asia', 'Western Asia'], 'IR' => ['Asia', 'Western Asia'],

            'KZ' => ['Asia', 'Central Asia'], 'KG' => ['Asia', 'Central Asia'], 'TJ' => ['Asia', 'Central Asia'],
            'TM' => ['Asia', 'Central Asia'], 'UZ' => ['Asia', 'Central Asia'],

            'AF' => ['Asia', 'Southern Asia'], 'BD' => ['Asia', 'Southern Asia'], 'BT' => ['Asia', 'Southern Asia'],
            'IN' => ['Asia', 'Southern Asia'], 'MV' => ['Asia', 'Southern Asia'], 'NP' => ['Asia', 'Southern Asia'],
            'PK' => ['Asia', 'Southern Asia'], 'LK' => ['Asia', 'Southern Asia'],

            'CN' => ['Asia', 'Eastern Asia'], 'JP' => ['Asia', 'Eastern Asia'], 'KP' => ['Asia', 'Eastern Asia'],
            'KR' => ['Asia', 'Eastern Asia'], 'MN' => ['Asia', 'Eastern Asia'], 'TW' => ['Asia', 'Eastern Asia'],

            'BN' => ['Asia', 'South-Eastern Asia'], 'KH' => ['Asia', 'South-Eastern Asia'], 'ID' => ['Asia', 'South-Eastern Asia'],
            'LA' => ['Asia', 'South-Eastern Asia'], 'MY' => ['Asia', 'South-Eastern Asia'], 'MM' => ['Asia', 'South-Eastern Asia'],
            'PH' => ['Asia', 'South-Eastern Asia'], 'SG' => ['Asia', 'South-Eastern Asia'], 'TH' => ['Asia', 'South-Eastern Asia'],
            'VN' => ['Asia', 'South-Eastern Asia'],

            'CA' => ['North America', 'Northern America'], 'US' => ['North America', 'Northern America'],
            'MX' => ['North America', 'Central America'],
            'BZ' => ['North America', 'Central America'], 'CR' => ['North America', 'Central America'],
            'SV' => ['North America', 'Central America'], 'GT' => ['North America', 'Central America'],
            'HN' => ['North America', 'Central America'], 'NI' => ['North America', 'Central America'],
            'PA' => ['North America', 'Central America'],

            'BS' => ['North America', 'Caribbean'], 'BB' => ['North America', 'Caribbean'], 'CU' => ['North America', 'Caribbean'],
            'DM' => ['North America', 'Caribbean'], 'DO' => ['North America', 'Caribbean'], 'GD' => ['North America', 'Caribbean'],
            'HT' => ['North America', 'Caribbean'], 'JM' => ['North America', 'Caribbean'], 'TT' => ['North America', 'Caribbean'],

            'AR' => ['South America', 'South America'], 'BO' => ['South America', 'South America'],
            'BR' => ['South America', 'South America'], 'CL' => ['South America', 'South America'],
            'CO' => ['South America', 'South America'], 'EC' => ['South America', 'South America'],
            'GY' => ['South America', 'South America'], 'PY' => ['South America', 'South America'],
            'PE' => ['South America', 'South America'], 'SR' => ['South America', 'South America'],
            'UY' => ['South America', 'South America'], 'VE' => ['South America', 'South America'],

            'AU' => ['Oceania', 'Oceania'], 'FJ' => ['Oceania', 'Oceania'], 'KI' => ['Oceania', 'Oceania'],
            'NZ' => ['Oceania', 'Oceania'], 'PG' => ['Oceania', 'Oceania'], 'VU' => ['Oceania', 'Oceania'],
        ];

        $regionCache = [];

        foreach ($data as $code => [$continent, $regionName]) {
            if (! isset($regionCache[$regionName])) {
                $regionCache[$regionName] = CountryRegion::firstOrCreate(['name' => $regionName]);
            }

            Country::where('code', $code)->update([
                'continent' => $continent,
                'country_region_id' => $regionCache[$regionName]->id,
            ]);
        }
    }
}
