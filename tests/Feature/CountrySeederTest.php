<?php

use App\Enums\Master\CountryStatus;
use App\Models\Master\Country;
use App\Models\Master\Timezone;
use Database\Seeders\CountrySeeder;

test('the country seeder inserts iso countries with timezone mapping', function () {
    $this->seed(CountrySeeder::class);

    expect(Country::query()->count())->toBeGreaterThan(200)
        ->and(Country::query()->distinct('sortname')->count('sortname'))->toBe(Country::query()->count());

    $india = Country::query()->where('sortname', 'IN')->first();
    $usa = Country::query()->where('sortname', 'US')->first();
    $kolkata = Timezone::query()->where('name', 'Asia/Kolkata')->first();
    $newYork = Timezone::query()->where('name', 'America/New_York')->first();

    expect($india)->not->toBeNull()
        ->and($india?->name)->toBe('India')
        ->and($india?->currency)->toBe('INR')
        ->and($india?->phonecode)->toBe(91)
        ->and($india?->timezone_id)->toBe($kolkata?->id)
        ->and($india?->status)->toBe(CountryStatus::Active)
        ->and($usa)->not->toBeNull()
        ->and($usa?->name)->toBe('United States')
        ->and($usa?->currency)->toBe('USD')
        ->and($usa?->phonecode)->toBe(1)
        ->and($usa?->timezone_id)->toBe($newYork?->id);

    expect(Country::query()->whereNotNull('timezone_id')->count())
        ->toBe(Country::query()->count());
});

test('the country seeder can be run twice without creating duplicates', function () {
    $this->seed(CountrySeeder::class);
    $count = Country::query()->count();

    $this->seed(CountrySeeder::class);

    expect(Country::query()->count())->toBe($count);
});
