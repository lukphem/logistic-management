<?php

namespace Database\Seeders;

use App\Models\City;
use App\Models\Country;
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
            'Abia' => ['code' => 'AB', 'cities' => ['Umuahia' => 'UMU', 'Aba' => 'ABA']],
            'Adamawa' => ['code' => 'AD', 'cities' => ['Yola' => 'YOL', 'Mubi' => 'MUB']],
            'Akwa Ibom' => ['code' => 'AK', 'cities' => ['Uyo' => 'UYO', 'Ikot Ekpene' => 'IKO']],
            'Anambra' => ['code' => 'AN', 'cities' => ['Awka' => 'AWK', 'Onitsha' => 'ONI', 'Nnewi' => 'NNE']],
            'Bauchi' => ['code' => 'BA', 'cities' => ['Bauchi' => 'BAU', 'Azare' => 'AZA']],
            'Bayelsa' => ['code' => 'BY', 'cities' => ['Yenagoa' => 'YEN']],
            'Benue' => ['code' => 'BE', 'cities' => ['Makurdi' => 'MAK', 'Gboko' => 'GBO']],
            'Borno' => ['code' => 'BO', 'cities' => ['Maiduguri' => 'MAI']],
            'Cross River' => ['code' => 'CR', 'cities' => ['Calabar' => 'CAL', 'Ikom' => 'IKM']],
            'Delta' => ['code' => 'DE', 'cities' => ['Asaba' => 'ASA', 'Warri' => 'WAR', 'Sapele' => 'SAP']],
            'Ebonyi' => ['code' => 'EB', 'cities' => ['Abakaliki' => 'ABK']],
            'Edo' => ['code' => 'ED', 'cities' => ['Benin City' => 'BEN', 'Ekpoma' => 'EKP']],
            'Ekiti' => ['code' => 'EK', 'cities' => ['Ado-Ekiti' => 'ADO']],
            'Enugu' => ['code' => 'EN', 'cities' => ['Enugu' => 'ENU', 'Nsukka' => 'NSU']],
            'FCT' => ['code' => 'FC', 'cities' => ['Abuja' => 'ABU']],
            'Gombe' => ['code' => 'GO', 'cities' => ['Gombe' => 'GOM']],
            'Imo' => ['code' => 'IM', 'cities' => ['Owerri' => 'OWE', 'Orlu' => 'ORL']],
            'Jigawa' => ['code' => 'JI', 'cities' => ['Dutse' => 'DUT']],
            'Kaduna' => ['code' => 'KD', 'cities' => ['Kaduna' => 'KAD', 'Zaria' => 'ZAR']],
            'Kano' => ['code' => 'KN', 'cities' => ['Kano' => 'KAN']],
            'Katsina' => ['code' => 'KT', 'cities' => ['Katsina' => 'KAT', 'Funtua' => 'FUN']],
            'Kebbi' => ['code' => 'KE', 'cities' => ['Birnin Kebbi' => 'BIR']],
            'Kogi' => ['code' => 'KO', 'cities' => ['Lokoja' => 'LOK']],
            'Kwara' => ['code' => 'KW', 'cities' => ['Ilorin' => 'ILO']],
            'Lagos' => ['code' => 'LA', 'cities' => ['Ikeja' => 'IKE', 'Lagos Island' => 'LAG', 'Surulere' => 'SUR', 'Lekki' => 'LEK', 'Ikorodu' => 'IKD', 'Badagry' => 'BAD']],
            'Nasarawa' => ['code' => 'NA', 'cities' => ['Lafia' => 'LAF']],
            'Niger' => ['code' => 'NI', 'cities' => ['Minna' => 'MIN', 'Bida' => 'BID']],
            'Ogun' => ['code' => 'OG', 'cities' => ['Abeokuta' => 'ABE', 'Sagamu' => 'SAG', 'Ijebu-Ode' => 'IJE']],
            'Ondo' => ['code' => 'ON', 'cities' => ['Akure' => 'AKU', 'Ondo City' => 'OND']],
            'Osun' => ['code' => 'OS', 'cities' => ['Osogbo' => 'OSO', 'Ile-Ife' => 'ILE']],
            'Oyo' => ['code' => 'OY', 'cities' => ['Ibadan' => 'IBA', 'Ogbomoso' => 'OGB']],
            'Plateau' => ['code' => 'PL', 'cities' => ['Jos' => 'JOS']],
            'Rivers' => ['code' => 'RI', 'cities' => ['Port Harcourt' => 'POR', 'Bonny' => 'BON']],
            'Sokoto' => ['code' => 'SO', 'cities' => ['Sokoto' => 'SOK']],
            'Taraba' => ['code' => 'TA', 'cities' => ['Jalingo' => 'JAL']],
            'Yobe' => ['code' => 'YO', 'cities' => ['Damaturu' => 'DAM']],
            'Zamfara' => ['code' => 'ZA', 'cities' => ['Gusau' => 'GUS']],
        ];
    }
}
