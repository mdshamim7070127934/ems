<?php

use App\Enums\Master\CountryStatus;
use App\Models\Master\Country;
use App\Models\Master\Timezone;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

test('guests are redirected to the login page', function () {
    $this->get(route('master.countries.index'))
        ->assertRedirect(route('login'));
});

test('authenticated users can view the country list', function () {
    $user = User::factory()->create();

    Country::factory()->create([
        'sortname' => 'IN',
        'name' => 'India',
        'currency' => 'INR',
        'phonecode' => 91,
        'status' => CountryStatus::Active,
    ]);

    $this->actingAs($user)
        ->get(route('master.countries.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('master/countries/index')
            ->has('countries.data', 1)
            ->where('countries.data.0.name', 'India')
            ->where('countries.data.0.sortname', 'IN')
            ->where('countries.data.0.currency', 'INR')
            ->where('countries.data.0.phonecode', 91)
            ->where('countries.data.0.status', 'active')
            ->where('countries.data.0.status_label', 'Active')
            ->where('countries.data.0.timezone_id', null)
            ->where('filters.sortBy', 'name')
            ->where('filters.sortOrder', 'desc')
            ->where('filters.pageSize', 10)
            ->has('timezoneOptions')
        );
});

test('unauthenticated users cannot create a country', function () {
    $this->post(route('master.countries.store'), [
        'sortname' => 'IN',
        'name' => 'India',
        'currency' => 'INR',
        'phonecode' => 91,
        'status' => 'inactive',
    ])->assertRedirect(route('login'));

    $this->assertDatabaseMissing('mst_country', [
        'name' => 'India',
    ]);
});

test('authenticated users can create a country', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->from(route('master.countries.index'))
        ->post(route('master.countries.store'), [
            'sortname' => 'IN',
            'name' => 'India',
            'currency' => 'INR',
            'phonecode' => 91,
            'timezone_id' => 'none',
            'status' => 'inactive',
        ])
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('master.countries.index'));

    $this->assertDatabaseHas('mst_country', [
        'sortname' => 'IN',
        'name' => 'India',
        'currency' => 'INR',
        'phonecode' => 91,
        'timezone_id' => null,
        'status' => 'inactive',
    ]);
});

test('country name is required when creating a country', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->from(route('master.countries.index'))
        ->post(route('master.countries.store'), [
            'sortname' => 'IN',
            'name' => '',
            'currency' => 'INR',
            'phonecode' => 91,
            'status' => 'inactive',
        ])
        ->assertSessionHasErrors(['name' => 'The name field is required.'])
        ->assertRedirect(route('master.countries.index'));
});

test('duplicate country names are rejected', function () {
    $user = User::factory()->create();

    Country::factory()->create([
        'name' => 'India',
    ]);

    $this->actingAs($user)
        ->from(route('master.countries.index'))
        ->post(route('master.countries.store'), [
            'sortname' => 'IN',
            'name' => 'India',
            'currency' => 'INR',
            'phonecode' => 91,
            'status' => 'inactive',
        ])
        ->assertSessionHasErrors(['name' => 'The name has already been taken.'])
        ->assertRedirect(route('master.countries.index'));
});

test('duplicate country sort names are rejected', function () {
    $user = User::factory()->create();

    Country::factory()->create([
        'sortname' => 'IN',
    ]);

    $this->actingAs($user)
        ->from(route('master.countries.index'))
        ->post(route('master.countries.store'), [
            'sortname' => 'IN',
            'name' => 'India',
            'currency' => 'INR',
            'phonecode' => 91,
            'status' => 'inactive',
        ])
        ->assertSessionHasErrors(['sortname' => 'The sort name has already been taken.'])
        ->assertRedirect(route('master.countries.index'));
});

test('invalid country status is rejected', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->from(route('master.countries.index'))
        ->post(route('master.countries.store'), [
            'sortname' => 'IN',
            'name' => 'India',
            'currency' => 'INR',
            'phonecode' => 91,
            'status' => 'archived',
        ])
        ->assertSessionHasErrors('status')
        ->assertRedirect(route('master.countries.index'));
});

test('authenticated users can update a country', function () {
    $user = User::factory()->create();
    $timezone = Timezone::factory()->create(Timezone::attributesFor('UTC'));
    $country = Country::factory()->create([
        'sortname' => 'IN',
        'name' => 'India',
        'currency' => 'INR',
        'phonecode' => 91,
        'status' => CountryStatus::Inactive,
    ]);

    $this->actingAs($user)
        ->from(route('master.countries.index'))
        ->put(route('master.countries.update', $country), [
            'sortname' => 'US',
            'name' => 'United States',
            'currency' => 'USD',
            'phonecode' => 1,
            'timezone_id' => $timezone->id,
            'status' => 'active',
        ])
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('master.countries.index'));

    $country->refresh();

    expect($country->sortname)->toBe('US')
        ->and($country->name)->toBe('United States')
        ->and($country->currency)->toBe('USD')
        ->and($country->phonecode)->toBe(1)
        ->and($country->timezone_id)->toBe($timezone->id)
        ->and($country->status)->toBe(CountryStatus::Active);
});

test('a country timezone must exist', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->from(route('master.countries.index'))
        ->post(route('master.countries.store'), [
            'sortname' => 'IN',
            'name' => 'India',
            'currency' => 'INR',
            'phonecode' => 91,
            'timezone_id' => 999,
            'status' => 'inactive',
        ])
        ->assertSessionHasErrors('timezone_id')
        ->assertRedirect(route('master.countries.index'));
});

test('authenticated users can delete a country', function () {
    $user = User::factory()->create();
    $country = Country::factory()->create();

    $this->actingAs($user)
        ->from(route('master.countries.index'))
        ->delete(route('master.countries.destroy', $country))
        ->assertRedirect(route('master.countries.index'));

    $this->assertModelMissing($country);
});

test('countries can be searched by name', function () {
    $user = User::factory()->create();

    Country::factory()->create(['name' => 'India']);
    Country::factory()->create(['name' => 'United States']);

    $this->actingAs($user)
        ->get(route('master.countries.index', ['search' => 'Ind']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->has('countries.data', 1)
            ->where('countries.data.0.name', 'India')
            ->where('filters.search', 'Ind')
        );
});

test('countries can be sorted by name', function () {
    $user = User::factory()->create();

    Country::factory()->create(['name' => 'United States']);
    Country::factory()->create(['name' => 'India']);

    $this->actingAs($user)
        ->get(route('master.countries.index', [
            'sortBy' => 'name',
            'sortOrder' => 'asc',
        ]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('countries.data.0.name', 'India')
            ->where('countries.data.1.name', 'United States')
            ->where('filters.sortBy', 'name')
            ->where('filters.sortOrder', 'asc')
        );
});

test('invalid sort columns fall back to the default sort', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('master.countries.index', [
            'sortBy' => 'id;drop table users',
            'sortOrder' => 'desc',
        ]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('filters.sortBy', 'name')
        );
});

test('the country list paginates with the selected page size', function () {
    $user = User::factory()->create();

    Country::factory()->count(11)->create();

    $this->actingAs($user)
        ->get(route('master.countries.index', ['pageSize' => 10]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->has('countries.data', 10)
            ->where('countries.meta.total', 11)
            ->where('countries.meta.per_page', 10)
            ->where('countries.meta.last_page', 2)
        );
});
