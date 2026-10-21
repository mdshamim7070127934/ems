<?php

use App\Enums\Master\CountryStatus;
use App\Enums\Master\Status;
use App\Models\Master\Branch;
use App\Models\Master\Country;
use App\Models\Master\Region;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

test('guests are redirected to the login page', function () {
    $this->get(route('master.regions.index'))
        ->assertRedirect(route('login'));
});

test('authenticated users can view the region list', function () {
    $user = User::factory()->create();
    $owner = User::factory()->create(['name' => 'Anita Sharma']);
    $country = Country::factory()->active()->create(['name' => 'India']);

    $this->travelTo('2026-09-20 10:00:00');

    Region::factory()->create([
        'region_name' => 'West',
        'country_id' => $country->id,
        'owner_id' => $owner->id,
        'status' => Status::Active,
    ]);

    $this->actingAs($user)
        ->get(route('master.regions.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('master/regions/index')
            ->has('regions.data', 1)
            ->where('regions.data.0.region_name', 'West')
            ->where('regions.data.0.country_name', 'India')
            ->where('regions.data.0.owner_name', 'Anita Sharma')
            ->where('regions.data.0.status', 'active')
            ->where('regions.data.0.created_at', '20-09-2026')
            ->where('filters.sortBy', 'created_at')
            ->has('countryOptions', 1)
            ->has('userOptions', 2)
        );
});

test('inactive countries are excluded from country options', function () {
    $user = User::factory()->create();

    Country::factory()->create([
        'name' => 'Inactive Land',
        'status' => CountryStatus::Inactive,
    ]);
    Country::factory()->active()->create(['name' => 'India']);

    $this->actingAs($user)
        ->get(route('master.regions.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->has('countryOptions', 1)
            ->where('countryOptions.0.name', 'India')
        );
});

test('authenticated users can create a region', function () {
    $user = User::factory()->create();
    $owner = User::factory()->create();
    $country = Country::factory()->active()->create();

    $this->actingAs($user)
        ->from(route('master.regions.index'))
        ->post(route('master.regions.store'), [
            'region_name' => 'West',
            'country_id' => $country->id,
            'owner_id' => $owner->id,
            'status' => 'active',
        ])
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('master.regions.index'));

    $this->assertDatabaseHas('mst_region', [
        'region_name' => 'West',
        'country_id' => $country->id,
        'owner_id' => $owner->id,
    ]);
});

test('a region must belong to an existing country', function () {
    $user = User::factory()->create();
    $owner = User::factory()->create();

    $this->actingAs($user)
        ->from(route('master.regions.index'))
        ->post(route('master.regions.store'), [
            'region_name' => 'West',
            'country_id' => 999,
            'owner_id' => $owner->id,
            'status' => 'active',
        ])
        ->assertSessionHasErrors('country_id')
        ->assertRedirect(route('master.regions.index'));
});

test('duplicate region names in the same country are rejected', function () {
    $user = User::factory()->create();
    $owner = User::factory()->create();
    $country = Country::factory()->active()->create();

    Region::factory()->create([
        'country_id' => $country->id,
        'region_name' => 'West',
    ]);

    $this->actingAs($user)
        ->from(route('master.regions.index'))
        ->post(route('master.regions.store'), [
            'region_name' => 'West',
            'country_id' => $country->id,
            'owner_id' => $owner->id,
            'status' => 'active',
        ])
        ->assertSessionHasErrors(['region_name' => 'The region name has already been taken.'])
        ->assertRedirect(route('master.regions.index'));
});

test('the same region name can exist in a different country', function () {
    $user = User::factory()->create();
    $owner = User::factory()->create();
    $firstCountry = Country::factory()->active()->create();
    $secondCountry = Country::factory()->active()->create();

    Region::factory()->create([
        'country_id' => $firstCountry->id,
        'region_name' => 'West',
    ]);

    $this->actingAs($user)
        ->from(route('master.regions.index'))
        ->post(route('master.regions.store'), [
            'region_name' => 'West',
            'country_id' => $secondCountry->id,
            'owner_id' => $owner->id,
            'status' => 'active',
        ])
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('master.regions.index'));

    expect(Region::query()->where('region_name', 'West')->count())->toBe(2);
});

test('authenticated users can update a region', function () {
    $user = User::factory()->create();
    $owner = User::factory()->create();
    $country = Country::factory()->active()->create();
    $region = Region::factory()->create([
        'country_id' => $country->id,
        'region_name' => 'West',
        'status' => Status::Active,
    ]);

    $this->actingAs($user)
        ->from(route('master.regions.index'))
        ->put(route('master.regions.update', $region), [
            'region_name' => 'East',
            'country_id' => $country->id,
            'owner_id' => $owner->id,
            'status' => 'inactive',
        ])
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('master.regions.index'));

    $region->refresh();

    expect($region->region_name)->toBe('East')
        ->and($region->status)->toBe(Status::Inactive);
});

test('authenticated users can delete a region', function () {
    $user = User::factory()->create();
    $region = Region::factory()->create();

    $this->actingAs($user)
        ->from(route('master.regions.index'))
        ->delete(route('master.regions.destroy', $region))
        ->assertRedirect(route('master.regions.index'));

    $this->assertModelMissing($region);
});

test('a region assigned to a branch cannot be deleted', function () {
    $user = User::factory()->create();
    $region = Region::factory()->create();

    Branch::factory()->create([
        'region_id' => $region->id,
        'country_id' => $region->country_id,
    ]);

    $this->actingAs($user)
        ->from(route('master.regions.index'))
        ->delete(route('master.regions.destroy', $region))
        ->assertRedirect(route('master.regions.index'));

    $this->assertModelExists($region);
});

test('regions can be searched by name', function () {
    $user = User::factory()->create();
    $owner = User::factory()->create(['name' => 'Anita Sharma']);
    $country = Country::factory()->active()->create(['name' => 'India']);

    Region::factory()->create([
        'country_id' => $country->id,
        'owner_id' => $owner->id,
        'region_name' => 'West',
    ]);
    Region::factory()->create([
        'country_id' => $country->id,
        'owner_id' => $owner->id,
        'region_name' => 'East',
    ]);

    $this->actingAs($user)
        ->get(route('master.regions.index', ['search' => 'Wes']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->has('regions.data', 1)
            ->where('regions.data.0.region_name', 'West')
            ->where('filters.search', 'Wes')
        );
});

test('the region list paginates with the selected page size', function () {
    $user = User::factory()->create();
    $country = Country::factory()->active()->create();

    Region::factory()->count(11)->create([
        'country_id' => $country->id,
    ]);

    $this->actingAs($user)
        ->get(route('master.regions.index', ['pageSize' => 10]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->has('regions.data', 10)
            ->where('regions.meta.total', 11)
            ->where('regions.meta.per_page', 10)
        );
});
