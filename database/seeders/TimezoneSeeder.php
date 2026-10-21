<?php

namespace Database\Seeders;

use App\Models\Master\Timezone;
use DateTimeZone;
use Illuminate\Database\Seeder;

class TimezoneSeeder extends Seeder
{
    /**
     * Seed all IANA timezones from the PHP timezone database.
     */
    public function run(): void
    {
        foreach (DateTimeZone::listIdentifiers() as $identifier) {
            Timezone::query()->firstOrCreate(
                ['name' => $identifier],
                Timezone::attributesFor($identifier),
            );
        }
    }
}
