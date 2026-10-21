<?php

use App\Enums\Master\Status;
use App\Models\Master\City;
use App\Models\Master\Country;
use App\Models\Master\State;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

test('guests are redirected to the login page', function () {
    $this->get(route('master.cities.index'))
        ->assertRedirect(route('login'));
});

test('authenticated users can view the city list', function () {
    $user = User::factory()->create();
    $country = Country::factory()->active()->create([
        'name' => 'India',
    ]);
    $state = State::factory()->create([
        'country_id' => $country->id,
        'name' => 'Maharashtra',
    ]);

    $this->travelTo('2026-09-20 10:00:00');

    City::factory()->create([
        'country_id' => $country->id,
        'state_id' => $state->id,
        'name' => 'Mumbai',
        'status' => Status::Active,
    ]);

    $this->actingAs($user)
        ->get(route('master.cities.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('master/cities/index')
            ->has('cities.data', 1)
            ->where('cities.data.0.name', 'Mumbai')
            ->where('cities.data.0.country_name', 'India')
            ->where('cities.data.0.state_name', 'Maharashtra')
            ->where('cities.data.0.status', 'active')
            ->where('cities.data.0.status_label', 'Active')
            ->where('cities.data.0.created_on', '20-09-2026')
            ->where('filters.sortBy', 'created_on')
            ->where('filters.sortOrder', 'desc')
            ->where('filters.pageSize', 10)
            ->has('countryOptions', 1)
            ->has('stateOptions', 1)
        );
});

test('inactive states are excluded from state options', function () {
    $user = User::factory()->create();
    $country = Country::factory()->active()->create();

    State::factory()->inactive()->create([
        'country_id' => $country->id,
        'name' => 'Hidden',
    ]);
    State::factory()->create([
        'country_id' => $country->id,
        'name' => 'Maharashtra',
    ]);

    $this->actingAs($user)
        ->get(route('master.cities.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->has('stateOptions', 1)
            ->where('stateOptions.0.name', 'Maharashtra')
        );
});

test('unauthenticated users cannot create a city', function () {
    $state = State::factory()->create();

    $this->post(route('master.cities.store'), [
        'country_id' => $state->country_id,
        'state_id' => $state->id,
        'name' => 'Mumbai',
        'status' => 'active',
    ])->assertRedirect(route('login'));

    $this->assertDatabaseMissing('mst_city', [
        'name' => 'Mumbai',
    ]);
});

test('authenticated users can create a city', function () {
    $user = User::factory()->create();
    $state = State::factory()->create();

    $this->actingAs($user)
        ->from(route('master.cities.index'))
        ->post(route('master.cities.store'), [
            'country_id' => $state->country_id,
            'state_id' => $state->id,
            'name' => 'Mumbai',
            'status' => 'active',
        ])
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('master.cities.index'));

    $this->assertDatabaseHas('mst_city', [
        'country_id' => $state->country_id,
        'state_id' => $state->id,
        'name' => 'Mumbai',
        'status' => 'active',
    ]);
});

test('city name is required when creating a city', function () {
    $user = User::factory()->create();
    $state = State::factory()->create();

    $this->actingAs($user)
        ->from(route('master.cities.index'))
        ->post(route('master.cities.store'), [
            'country_id' => $state->country_id,
            'state_id' => $state->id,
            'name' => '',
            'status' => 'active',
        ])
        ->assertSessionHasErrors(['name' => 'The name field is required.'])
        ->assertRedirect(route('master.cities.index'));
});

test('a city state must belong to the selected country', function () {
    $user = User::factory()->create();
    $country = Country::factory()->active()->create();
    $state = State::factory()->create();

    $this->actingAs($user)
        ->from(route('master.cities.index'))
        ->post(route('master.cities.store'), [
            'country_id' => $country->id,
            'state_id' => $state->id,
            'name' => 'Mumbai',
            'status' => 'active',
        ])
        ->assertSessionHasErrors('state_id')
        ->assertRedirect(route('master.cities.index'));
});

test('duplicate city names in the same state are rejected', function () {
    $user = User::factory()->create();
    $state = State::factory()->create();

    City::factory()->create([
        'country_id' => $state->country_id,
        'state_id' => $state->id,
        'name' => 'Mumbai',
    ]);

    $this->actingAs($user)
        ->from(route('master.cities.index'))
        ->post(route('master.cities.store'), [
            'country_id' => $state->country_id,
            'state_id' => $state->id,
            'name' => 'Mumbai',
            'status' => 'active',
        ])
        ->assertSessionHasErrors(['name' => 'The name has already been taken.'])
        ->assertRedirect(route('master.cities.index'));
});

test('the same city name can exist in a different state', function () {
    $user = User::factory()->create();
    $firstState = State::factory()->create();
    $secondState = State::factory()->create();

    City::factory()->create([
        'country_id' => $firstState->country_id,
        'state_id' => $firstState->id,
        'name' => 'Springfield',
    ]);

    $this->actingAs($user)
        ->from(route('master.cities.index'))
        ->post(route('master.cities.store'), [
            'country_id' => $secondState->country_id,
            'state_id' => $secondState->id,
            'name' => 'Springfield',
            'status' => 'active',
        ])
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('master.cities.index'));

    expect(City::query()->where('name', 'Springfield')->count())->toBe(2);
});

test('authenticated users can update a city', function () {
    $user = User::factory()->create();
    $state = State::factory()->create();
    $city = City::factory()->create([
        'country_id' => $state->country_id,
        'state_id' => $state->id,
        'name' => 'Mumbai',
        'status' => Status::Active,
    ]);

    $this->actingAs($user)
        ->from(route('master.cities.index'))
        ->put(route('master.cities.update', $city), [
            'country_id' => $state->country_id,
            'state_id' => $state->id,
            'name' => 'Pune',
            'status' => 'inactive',
        ])
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('master.cities.index'));

    $city->refresh();

    expect($city->name)->toBe('Pune')
        ->and($city->status)->toBe(Status::Inactive);
});

test('authenticated users can delete a city', function () {
    $user = User::factory()->create();
    $city = City::factory()->create();

    $this->actingAs($user)
        ->from(route('master.cities.index'))
        ->delete(route('master.cities.destroy', $city))
        ->assertRedirect(route('master.cities.index'));

    $this->assertModelMissing($city);
});

test('cities can be searched by name', function () {
    $user = User::factory()->create();
    $state = State::factory()->create();

    City::factory()->create([
        'country_id' => $state->country_id,
        'state_id' => $state->id,
        'name' => 'Mumbai',
    ]);
    City::factory()->create([
        'country_id' => $state->country_id,
        'state_id' => $state->id,
        'name' => 'Pune',
    ]);

    $this->actingAs($user)
        ->get(route('master.cities.index', ['search' => 'Mum']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->has('cities.data', 1)
            ->where('cities.data.0.name', 'Mumbai')
            ->where('filters.search', 'Mum')
        );
});

test('cities can be searched by country or state name', function () {
    $user = User::factory()->create();

    $india = Country::factory()->active()->create(['name' => 'India']);
    $maharashtra = State::factory()->create([
        'country_id' => $india->id,
        'name' => 'Maharashtra',
    ]);
    City::factory()->create([
        'country_id' => $india->id,
        'state_id' => $maharashtra->id,
        'name' => 'Mumbai',
    ]);

    $usa = Country::factory()->active()->create(['name' => 'United States']);
    $california = State::factory()->create([
        'country_id' => $usa->id,
        'name' => 'California',
    ]);
    City::factory()->create([
        'country_id' => $usa->id,
        'state_id' => $california->id,
        'name' => 'Los Angeles',
    ]);

    $this->actingAs($user)
        ->get(route('master.cities.index', ['search' => 'Maha']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->has('cities.data', 1)
            ->where('cities.data.0.state_name', 'Maharashtra')
        );
});

test('cities can be sorted by name', function () {
    $user = User::factory()->create();
    $state = State::factory()->create();

    City::factory()->create([
        'country_id' => $state->country_id,
        'state_id' => $state->id,
        'name' => 'Pune',
    ]);
    City::factory()->create([
        'country_id' => $state->country_id,
        'state_id' => $state->id,
        'name' => 'Mumbai',
    ]);

    $this->actingAs($user)
        ->get(route('master.cities.index', [
            'sortBy' => 'name',
            'sortOrder' => 'asc',
        ]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('cities.data.0.name', 'Mumbai')
            ->where('cities.data.1.name', 'Pune')
            ->where('filters.sortBy', 'name')
            ->where('filters.sortOrder', 'asc')
        );
});

test('invalid sort columns fall back to the default sort', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('master.cities.index', [
            'sortBy' => 'id;drop table users',
            'sortOrder' => 'desc',
        ]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('filters.sortBy', 'created_on')
        );
});

test('the city list paginates with the selected page size', function () {
    $user = User::factory()->create();
    $state = State::factory()->create();

    City::factory()->count(11)->create([
        'country_id' => $state->country_id,
        'state_id' => $state->id,
    ]);

    $this->actingAs($user)
        ->get(route('master.cities.index', ['pageSize' => 10]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->has('cities.data', 10)
            ->where('cities.meta.total', 11)
            ->where('cities.meta.per_page', 10)
            ->where('cities.meta.last_page', 2)
        );
});
