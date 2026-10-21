<?php

use App\Enums\Master\Status;
use App\Models\Master\Country;
use App\Models\Master\Timezone;
use App\Models\User;
use DateTimeZone;
use Inertia\Testing\AssertableInertia as Assert;

test('guests are redirected to the login page', function () {
    $this->get(route('master.timezones.index'))
        ->assertRedirect(route('login'));
});

test('authenticated users can view the timezone list', function () {
    $user = User::factory()->create();

    $this->travelTo('2026-09-20 10:00:00');

    Timezone::factory()->create([
        ...Timezone::attributesFor('Asia/Kolkata'),
        'status' => Status::Active,
    ]);

    $this->actingAs($user)
        ->get(route('master.timezones.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('master/timezones/index')
            ->has('timezones.data', 1)
            ->where('timezones.data.0.name', 'Asia/Kolkata')
            ->where('timezones.data.0.display_name', 'Asia/Kolkata')
            ->where('timezones.data.0.utc_offset', now('Asia/Kolkata')->format('P'))
            ->where('timezones.data.0.status', 'active')
            ->where('timezones.data.0.created_on', '20-09-2026')
            ->where('filters.sortBy', 'created_on')
            ->where('filters.sortOrder', 'desc')
            ->where('filters.pageSize', 10)
            ->has('identifierOptions')
        );
});

test('unauthenticated users cannot create a timezone', function () {
    $this->post(route('master.timezones.store'), [
        'name' => 'Asia/Kolkata',
        'display_name' => 'Asia/Kolkata',
        'status' => 'active',
    ])->assertRedirect(route('login'));

    $this->assertDatabaseMissing('mst_timezone', [
        'name' => 'Asia/Kolkata',
    ]);
});

test('authenticated users can create a timezone', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->from(route('master.timezones.index'))
        ->post(route('master.timezones.store'), [
            'name' => 'Asia/Kolkata',
            'display_name' => 'India Standard Time',
            'status' => 'active',
        ])
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('master.timezones.index'));

    $this->assertDatabaseHas('mst_timezone', [
        'name' => 'Asia/Kolkata',
        'code' => 'ASIA_KOLKATA',
        'display_name' => 'India Standard Time',
        'utc_offset' => now('Asia/Kolkata')->format('P'),
        'status' => 'active',
    ]);
});

test('timezone name is required when creating a timezone', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->from(route('master.timezones.index'))
        ->post(route('master.timezones.store'), [
            'name' => '',
            'display_name' => 'India Standard Time',
            'status' => 'active',
        ])
        ->assertSessionHasErrors(['name' => 'The timezone name field is required.'])
        ->assertRedirect(route('master.timezones.index'));
});

test('invalid timezone identifiers are rejected', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->from(route('master.timezones.index'))
        ->post(route('master.timezones.store'), [
            'name' => 'Not/A/Zone',
            'display_name' => 'Invalid',
            'status' => 'active',
        ])
        ->assertSessionHasErrors('name')
        ->assertRedirect(route('master.timezones.index'));
});

test('duplicate timezone names are rejected', function () {
    $user = User::factory()->create();

    Timezone::factory()->create(Timezone::attributesFor('Asia/Kolkata'));

    $this->actingAs($user)
        ->from(route('master.timezones.index'))
        ->post(route('master.timezones.store'), [
            'name' => 'Asia/Kolkata',
            'display_name' => 'Asia/Kolkata',
            'status' => 'active',
        ])
        ->assertSessionHasErrors(['name' => 'The timezone name has already been taken.'])
        ->assertRedirect(route('master.timezones.index'));
});

test('authenticated users can update a timezone', function () {
    $user = User::factory()->create();
    $timezone = Timezone::factory()->create([
        ...Timezone::attributesFor('Asia/Kolkata'),
        'status' => Status::Active,
    ]);

    $this->actingAs($user)
        ->from(route('master.timezones.index'))
        ->put(route('master.timezones.update', $timezone), [
            'name' => 'Asia/Dubai',
            'display_name' => 'Gulf Standard Time',
            'status' => 'inactive',
        ])
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('master.timezones.index'));

    $timezone->refresh();

    expect($timezone->name)->toBe('Asia/Dubai')
        ->and($timezone->display_name)->toBe('Gulf Standard Time')
        ->and($timezone->utc_offset)->toBe(now('Asia/Dubai')->format('P'))
        ->and($timezone->status)->toBe(Status::Inactive);
});

test('authenticated users can delete a timezone', function () {
    $user = User::factory()->create();
    $timezone = Timezone::factory()->create();

    $this->actingAs($user)
        ->from(route('master.timezones.index'))
        ->delete(route('master.timezones.destroy', $timezone))
        ->assertRedirect(route('master.timezones.index'));

    $this->assertModelMissing($timezone);
});

test('a timezone assigned to a country cannot be deleted', function () {
    $user = User::factory()->create();
    $timezone = Timezone::factory()->create();

    Country::factory()->create([
        'timezone_id' => $timezone->id,
    ]);

    $this->actingAs($user)
        ->from(route('master.timezones.index'))
        ->delete(route('master.timezones.destroy', $timezone))
        ->assertRedirect(route('master.timezones.index'));

    $this->assertModelExists($timezone);
});

test('timezones can be searched by name', function () {
    $user = User::factory()->create();

    Timezone::factory()->create(Timezone::attributesFor('Asia/Kolkata'));
    Timezone::factory()->create(Timezone::attributesFor('America/New_York'));

    $this->actingAs($user)
        ->get(route('master.timezones.index', ['search' => 'Kolkata']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->has('timezones.data', 1)
            ->where('timezones.data.0.name', 'Asia/Kolkata')
            ->where('filters.search', 'Kolkata')
        );
});

test('timezones can be sorted by name', function () {
    $user = User::factory()->create();

    Timezone::factory()->create(Timezone::attributesFor('Europe/Paris'));
    Timezone::factory()->create(Timezone::attributesFor('Asia/Kolkata'));

    $this->actingAs($user)
        ->get(route('master.timezones.index', [
            'sortBy' => 'name',
            'sortOrder' => 'asc',
        ]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('timezones.data.0.name', 'Asia/Kolkata')
            ->where('timezones.data.1.name', 'Europe/Paris')
            ->where('filters.sortBy', 'name')
            ->where('filters.sortOrder', 'asc')
        );
});

test('invalid sort columns fall back to the default sort', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('master.timezones.index', [
            'sortBy' => 'id;drop table users',
            'sortOrder' => 'desc',
        ]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('filters.sortBy', 'created_on')
        );
});

test('the timezone list paginates with the selected page size', function () {
    $user = User::factory()->create();

    Timezone::factory()->count(11)->create();

    $this->actingAs($user)
        ->get(route('master.timezones.index', ['pageSize' => 10]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->has('timezones.data', 10)
            ->where('timezones.meta.total', 11)
            ->where('timezones.meta.per_page', 10)
            ->where('timezones.meta.last_page', 2)
        );
});

test('identifier options come from the IANA timezone database', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('master.timezones.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->has('identifierOptions', count(DateTimeZone::listIdentifiers()))
        );
});
