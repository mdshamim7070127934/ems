<?php

use App\Enums\Master\Status;
use App\Models\Master\Country;
use App\Models\Master\State;
use Database\Seeders\StateSeeder;

test('the state seeder inserts states mapped to countries', function () {
    $this->seed(StateSeeder::class);

    expect(State::query()->count())->toBeGreaterThan(4000);

    expect(
        State::query()
            ->select('country_id', 'name')
            ->groupBy('country_id', 'name')
            ->havingRaw('COUNT(*) > 1')
            ->exists()
    )->toBeFalse();

    $india = Country::query()->where('sortname', 'IN')->first();
    $usa = Country::query()->where('sortname', 'US')->first();

    expect($india)->not->toBeNull()
        ->and($usa)->not->toBeNull();

    $maharashtra = State::query()
        ->where('country_id', $india?->id)
        ->where('name', 'Maharashtra')
        ->first();
    $california = State::query()
        ->where('country_id', $usa?->id)
        ->where('name', 'California')
        ->first();

    expect($maharashtra)->not->toBeNull()
        ->and($maharashtra?->status)->toBe(Status::Active)
        ->and($california)->not->toBeNull()
        ->and($california?->status)->toBe(Status::Active);
});

test('the state seeder can be run twice without creating duplicates', function () {
    $this->seed(StateSeeder::class);
    $count = State::query()->count();

    $this->seed(StateSeeder::class);

    expect(State::query()->count())->toBe($count);
});
