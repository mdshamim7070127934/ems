<?php

namespace Database\Seeders;

use App\Enums\Master\CountryStatus;
use App\Models\Master\Country;
use App\Models\Master\Timezone;
use DateTimeZone;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;
use NumberFormatter;
use ResourceBundle;
use RuntimeException;

class CountrySeeder extends Seeder
{
    /**
     * ISO codes that are regions or placeholders, not countries.
     *
     * @var list<string>
     */
    private const SKIP_CODES = ['ZZ', 'EU', 'EZ', 'UN', 'XA', 'XB', 'QO'];

    /**
     * Seed ISO countries from ICU, mapped to a primary IANA timezone.
     */
    public function run(): void
    {
        $this->call(TimezoneSeeder::class);

        $timezoneIds = Timezone::query()->pluck('id', 'name');
        $callingCodes = require database_path('data/country-calling-codes.php');

        foreach ($this->countries() as $code => $name) {
            if (! isset($callingCodes[$code])) {
                continue;
            }

            $attributes = [
                'name' => $name,
                'currency' => $this->currencyFor($code),
                'phonecode' => $callingCodes[$code],
                'timezone_id' => $this->timezoneIdFor($code, $timezoneIds),
                'status' => CountryStatus::Active,
            ];

            $country = Country::query()->firstOrCreate(
                ['sortname' => $code],
                $attributes,
            );

            if ($country->timezone_id === null && $attributes['timezone_id'] !== null) {
                $country->update([
                    'timezone_id' => $attributes['timezone_id'],
                ]);
            }
        }
    }

    /**
     * @return array<string, string>
     */
    private function countries(): array
    {
        $bundle = ResourceBundle::create('en', 'ICUDATA-region');

        if ($bundle === null) {
            throw new RuntimeException('The ICU region bundle is unavailable.');
        }

        $records = $bundle->get('Countries');
        $countries = [];

        foreach ($records as $code => $name) {
            if (
                ! is_string($code)
                || ! is_string($name)
                || ! preg_match('/^[A-Z]{2}$/', $code)
                || in_array($code, self::SKIP_CODES, true)
            ) {
                continue;
            }

            $countries[$code] = $name;
        }

        return $countries;
    }

    /**
     * @param  Collection<string, int>  $timezoneIds
     */
    private function timezoneIdFor(string $code, Collection $timezoneIds): ?int
    {
        $preferred = $this->primaryTimezones()[$code] ?? null;

        if (is_string($preferred) && $timezoneIds->has($preferred)) {
            return (int) $timezoneIds->get($preferred);
        }

        foreach (DateTimeZone::listIdentifiers(DateTimeZone::PER_COUNTRY, $code) as $identifier) {
            if ($timezoneIds->has($identifier)) {
                return (int) $timezoneIds->get($identifier);
            }
        }

        return null;
    }

    /**
     * Capital/primary IANA zone for countries that have more than one.
     *
     * @return array<string, string>
     */
    private function primaryTimezones(): array
    {
        return [
            'AC' => 'Atlantic/St_Helena',
            'AQ' => 'Antarctica/McMurdo',
            'AR' => 'America/Argentina/Buenos_Aires',
            'AU' => 'Australia/Sydney',
            'BR' => 'America/Sao_Paulo',
            'BV' => 'Europe/Oslo',
            'CA' => 'America/Toronto',
            'CD' => 'Africa/Kinshasa',
            'CL' => 'America/Santiago',
            'CN' => 'Asia/Shanghai',
            'CY' => 'Asia/Nicosia',
            'DE' => 'Europe/Berlin',
            'DG' => 'Indian/Chagos',
            'EA' => 'Africa/Ceuta',
            'EC' => 'America/Guayaquil',
            'ES' => 'Europe/Madrid',
            'FM' => 'Pacific/Pohnpei',
            'GL' => 'America/Nuuk',
            'HM' => 'Indian/Kerguelen',
            'IC' => 'Atlantic/Canary',
            'ID' => 'Asia/Jakarta',
            'KI' => 'Pacific/Tarawa',
            'KZ' => 'Asia/Almaty',
            'MH' => 'Pacific/Majuro',
            'MN' => 'Asia/Ulaanbaatar',
            'MX' => 'America/Mexico_City',
            'MY' => 'Asia/Kuala_Lumpur',
            'NZ' => 'Pacific/Auckland',
            'PF' => 'Pacific/Tahiti',
            'PG' => 'Pacific/Port_Moresby',
            'PS' => 'Asia/Gaza',
            'PT' => 'Europe/Lisbon',
            'RU' => 'Europe/Moscow',
            'TA' => 'Atlantic/St_Helena',
            'UA' => 'Europe/Kyiv',
            'UM' => 'Pacific/Honolulu',
            'US' => 'America/New_York',
            'UZ' => 'Asia/Tashkent',
            'XK' => 'Europe/Belgrade',
        ];
    }

    private function currencyFor(string $code): string
    {
        $formatter = new NumberFormatter('en_'.$code, NumberFormatter::CURRENCY);
        $currency = $formatter->getTextAttribute(NumberFormatter::CURRENCY_CODE);

        if ($currency === '' || $currency === 'XXX') {
            return 'USD';
        }

        return $currency;
    }
}
