<?php

use App\Enums\Master\CountryStatus;
use App\Enums\Master\Status;
use App\Models\Master\Country;
use App\Models\Master\State;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

test('guests are redirected to the login page', function () {
    $this->get(route('master.states.index'))
        ->assertRedirect(route('login'));
});

test('authenticated users can view the state list', function () {
    $user = User::factory()->create();
    $country = Country::factory()->active()->create([
        'name' => 'India',
    ]);

    $this->travelTo('2026-09-20 10:00:00');

    State::factory()->create([
        'country_id' => $country->id,
        'name' => 'Maharashtra',
        'status' => Status::Active,
    ]);

    $this->actingAs($user)
        ->get(route('master.states.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('master/states/index')
            ->has('states.data', 1)
            ->where('states.data.0.name', 'Maharashtra')
            ->where('states.data.0.country_name', 'India')
            ->where('states.data.0.status', 'active')
            ->where('states.data.0.status_label', 'Active')
            ->where('states.data.0.created_on', '20-09-2026')
            ->where('filters.sortBy', 'created_on')
            ->where('filters.sortOrder', 'desc')
            ->where('filters.pageSize', 10)
            ->has('countryOptions', 1)
        );
});

test('inactive countries are excluded from country options', function () {
    $user = User::factory()->create();

    Country::factory()->create([
        'name' => 'Inactive Land',
        'status' => CountryStatus::Inactive,
    ]);
    Country::factory()->active()->create([
        'name' => 'India',
    ]);

    $this->actingAs($user)
        ->get(route('master.states.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->has('countryOptions', 1)
            ->where('countryOptions.0.name', 'India')
        );
});

test('unauthenticated users cannot create a state', function () {
    $country = Country::factory()->active()->create();

    $this->post(route('master.states.store'), [
        'country_id' => $country->id,
        'name' => 'Maharashtra',
        'status' => 'active',
    ])->assertRedirect(route('login'));

    $this->assertDatabaseMissing('mst_state', [
        'name' => 'Maharashtra',
    ]);
});

test('authenticated users can create a state', function () {
    $user = User::factory()->create();
    $country = Country::factory()->active()->create();

    $this->actingAs($user)
        ->from(route('master.states.index'))
        ->post(route('master.states.store'), [
            'country_id' => $country->id,
            'name' => 'Maharashtra',
            'status' => 'active',
        ])
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('master.states.index'));

    $this->assertDatabaseHas('mst_state', [
        'country_id' => $country->id,
        'name' => 'Maharashtra',
        'status' => 'active',
    ]);
});

test('state name is required when creating a state', function () {
    $user = User::factory()->create();
    $country = Country::factory()->active()->create();

    $this->actingAs($user)
        ->from(route('master.states.index'))
        ->post(route('master.states.store'), [
            'country_id' => $country->id,
            'name' => '',
            'status' => 'active',
        ])
        ->assertSessionHasErrors(['name' => 'The name field is required.'])
        ->assertRedirect(route('master.states.index'));
});

test('a state must belong to an existing country', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->from(route('master.states.index'))
        ->post(route('master.states.store'), [
            'country_id' => 999,
            'name' => 'Maharashtra',
            'status' => 'active',
        ])
        ->assertSessionHasErrors('country_id')
        ->assertRedirect(route('master.states.index'));
});

test('duplicate state names in the same country are rejected', function () {
    $user = User::factory()->create();
    $country = Country::factory()->active()->create();

    State::factory()->create([
        'country_id' => $country->id,
        'name' => 'Maharashtra',
    ]);

    $this->actingAs($user)
        ->from(route('master.states.index'))
        ->post(route('master.states.store'), [
            'country_id' => $country->id,
            'name' => 'Maharashtra',
            'status' => 'active',
        ])
        ->assertSessionHasErrors(['name' => 'The name has already been taken.'])
        ->assertRedirect(route('master.states.index'));
});

test('the same state name can exist in a different country', function () {
    $user = User::factory()->create();
    $firstCountry = Country::factory()->active()->create();
    $secondCountry = Country::factory()->active()->create();

    State::factory()->create([
        'country_id' => $firstCountry->id,
        'name' => 'Georgia',
    ]);

    $this->actingAs($user)
        ->from(route('master.states.index'))
        ->post(route('master.states.store'), [
            'country_id' => $secondCountry->id,
            'name' => 'Georgia',
            'status' => 'active',
        ])
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('master.states.index'));

    expect(State::query()->where('name', 'Georgia')->count())->toBe(2);
});

test('invalid state status is rejected', function () {
    $user = User::factory()->create();
    $country = Country::factory()->active()->create();

    $this->actingAs($user)
        ->from(route('master.states.index'))
        ->post(route('master.states.store'), [
            'country_id' => $country->id,
            'name' => 'Maharashtra',
            'status' => 'delete',
        ])
        ->assertSessionHasErrors('status')
        ->assertRedirect(route('master.states.index'));
});

test('authenticated users can update a state', function () {
    $user = User::factory()->create();
    $country = Country::factory()->active()->create();
    $state = State::factory()->create([
        'country_id' => $country->id,
        'name' => 'Maharashtra',
        'status' => Status::Active,
    ]);

    $this->actingAs($user)
        ->from(route('master.states.index'))
        ->put(route('master.states.update', $state), [
            'country_id' => $country->id,
            'name' => 'Karnataka',
            'status' => 'inactive',
        ])
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('master.states.index'));

    $state->refresh();

    expect($state->name)->toBe('Karnataka')
        ->and($state->status)->toBe(Status::Inactive);
});

test('authenticated users can delete a state', function () {
    $user = User::factory()->create();
    $state = State::factory()->create();

    $this->actingAs($user)
        ->from(route('master.states.index'))
        ->delete(route('master.states.destroy', $state))
        ->assertRedirect(route('master.states.index'));

    $this->assertModelMissing($state);
});

test('states can be searched by name', function () {
    $user = User::factory()->create();
    $country = Country::factory()->active()->create();

    State::factory()->create([
        'country_id' => $country->id,
        'name' => 'Maharashtra',
    ]);
    State::factory()->create([
        'country_id' => $country->id,
        'name' => 'Karnataka',
    ]);

    $this->actingAs($user)
        ->get(route('master.states.index', ['search' => 'Maha']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->has('states.data', 1)
            ->where('states.data.0.name', 'Maharashtra')
            ->where('filters.search', 'Maha')
        );
});

test('states can be searched by country name', function () {
    $user = User::factory()->create();

    State::factory()->create([
        'country_id' => Country::factory()->active()->create(['name' => 'India'])->id,
        'name' => 'Maharashtra',
    ]);
    State::factory()->create([
        'country_id' => Country::factory()->active()->create(['name' => 'United States'])->id,
        'name' => 'California',
    ]);

    $this->actingAs($user)
        ->get(route('master.states.index', ['search' => 'Ind']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->has('states.data', 1)
            ->where('states.data.0.country_name', 'India')
        );
});

test('states can be sorted by name', function () {
    $user = User::factory()->create();
    $country = Country::factory()->active()->create();

    State::factory()->create([
        'country_id' => $country->id,
        'name' => 'Karnataka',
    ]);
    State::factory()->create([
        'country_id' => $country->id,
        'name' => 'Maharashtra',
    ]);

    $this->actingAs($user)
        ->get(route('master.states.index', [
            'sortBy' => 'name',
            'sortOrder' => 'asc',
        ]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('states.data.0.name', 'Karnataka')
            ->where('states.data.1.name', 'Maharashtra')
            ->where('filters.sortBy', 'name')
            ->where('filters.sortOrder', 'asc')
        );
});

test('invalid sort columns fall back to the default sort', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('master.states.index', [
            'sortBy' => 'id;drop table users',
            'sortOrder' => 'desc',
        ]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('filters.sortBy', 'created_on')
        );
});

test('the state list paginates with the selected page size', function () {
    $user = User::factory()->create();
    $country = Country::factory()->active()->create();

    State::factory()->count(11)->create([
        'country_id' => $country->id,
    ]);

    $this->actingAs($user)
        ->get(route('master.states.index', ['pageSize' => 10]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->has('states.data', 10)
            ->where('states.meta.total', 11)
            ->where('states.meta.per_page', 10)
            ->where('states.meta.last_page', 2)
        );
});
