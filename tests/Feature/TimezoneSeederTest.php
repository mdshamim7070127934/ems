<?php

use App\Models\Master\Timezone;
use Database\Seeders\TimezoneSeeder;
use DateTimeZone;

test('the timezone seeder inserts every IANA identifier without duplicates', function () {
    $this->seed(TimezoneSeeder::class);

    $identifiers = DateTimeZone::listIdentifiers();

    expect(Timezone::query()->count())->toBe(count($identifiers))
        ->and(Timezone::query()->distinct('name')->count('name'))->toBe(count($identifiers));

    $this->assertDatabaseHas('mst_timezone', [
        'name' => 'Asia/Kolkata',
        'status' => 'active',
    ]);
});

test('the timezone seeder can be run twice without creating duplicates', function () {
    $this->seed(TimezoneSeeder::class);
    $this->seed(TimezoneSeeder::class);

    expect(Timezone::query()->count())->toBe(count(DateTimeZone::listIdentifiers()));
});
