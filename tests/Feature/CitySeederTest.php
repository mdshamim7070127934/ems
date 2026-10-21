<?php

use App\Enums\Master\Status;
use App\Models\Master\City;
use App\Models\Master\Country;
use App\Models\Master\State;
use Database\Seeders\CitySeeder;

test('the city seeder inserts cities mapped to countries and states', function () {
    $this->seed(CitySeeder::class);

    expect(City::query()->count())->toBeGreaterThan(100000);

    expect(
        City::query()
            ->select('state_id', 'name')
            ->groupBy('state_id', 'name')
            ->havingRaw('COUNT(*) > 1')
            ->exists()
    )->toBeFalse();

    $india = Country::query()->where('sortname', 'IN')->first();
    $usa = Country::query()->where('sortname', 'US')->first();
    $maharashtra = State::query()
        ->where('country_id', $india?->id)
        ->where('name', 'Maharashtra')
        ->first();
    $california = State::query()
        ->where('country_id', $usa?->id)
        ->where('name', 'California')
        ->first();

    expect($india)->not->toBeNull()
        ->and($usa)->not->toBeNull()
        ->and($maharashtra)->not->toBeNull()
        ->and($california)->not->toBeNull();

    $mumbai = City::query()
        ->where('country_id', $india?->id)
        ->where('state_id', $maharashtra?->id)
        ->where('name', 'Mumbai')
        ->first();
    $losAngeles = City::query()
        ->where('country_id', $usa?->id)
        ->where('state_id', $california?->id)
        ->where('name', 'Los Angeles')
        ->first();

    expect($mumbai)->not->toBeNull()
        ->and($mumbai?->status)->toBe(Status::Active)
        ->and($losAngeles)->not->toBeNull()
        ->and($losAngeles?->status)->toBe(Status::Active);
});

test('the city seeder can be run twice without creating duplicates', function () {
    $this->seed(CitySeeder::class);
    $count = City::query()->count();

    $this->seed(CitySeeder::class);

    expect(City::query()->count())->toBe($count);
});
