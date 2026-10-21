<?php

namespace Database\Seeders;

use JsonException;
use RuntimeException;

class WorldLocationDataset
{
    /**
     * Compact world states and cities keyed by ISO 3166-1 alpha-2.
     *
     * Source: https://github.com/dr5hn/countries-states-cities-database (ODbL).
     *
     * @return array<string, array<string, list<string>>>
     */
    public static function all(): array
    {
        /** @var array<string, array<string, list<string>>>|null $locations */
        static $locations = null;

        if ($locations !== null) {
            return $locations;
        }

        $path = database_path('data/world-locations.json.gz');
        $gzip = file_get_contents($path);

        if ($gzip === false) {
            throw new RuntimeException("Unable to read world locations from [{$path}].");
        }

        $json = gzdecode($gzip);

        if (! is_string($json) || $json === '') {
            throw new RuntimeException('Unable to decode the world locations dataset.');
        }

        try {
            /** @var mixed $decoded */
            $decoded = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new RuntimeException('The world locations dataset is not valid JSON.', 0, $exception);
        }

        if (! is_array($decoded)) {
            throw new RuntimeException('The world locations dataset must be a JSON object.');
        }

        $locations = [];

        foreach ($decoded as $iso2 => $states) {
            if (! is_string($iso2) || ! is_array($states)) {
                continue;
            }

            foreach ($states as $stateName => $cities) {
                if (! is_string($stateName) || ! is_array($cities)) {
                    continue;
                }

                $cityNames = [];

                foreach ($cities as $cityName) {
                    if (is_string($cityName) && $cityName !== '') {
                        $cityNames[] = $cityName;
                    }
                }

                $locations[$iso2][$stateName] = $cityNames;
            }
        }

        return $locations;
    }
}
