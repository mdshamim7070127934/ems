<?php

use App\Enums\Master\Status;
use App\Models\Master\Branch;
use App\Models\Master\Group;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

test('guests are redirected to the login page', function () {
    $this->get(route('master.groups.index'))
        ->assertRedirect(route('login'));
});

test('authenticated users can view the group list', function () {
    $user = User::factory()->create();
    $owner = User::factory()->create(['name' => 'Anita Sharma']);

    $this->travelTo('2026-09-20 10:00:00');

    Group::factory()->create([
        'group_name' => 'North Zone',
        'description' => 'North operations',
        'owner_id' => $owner->id,
        'status' => Status::Active,
    ]);

    $this->actingAs($user)
        ->get(route('master.groups.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('master/groups/index')
            ->has('groups.data', 1)
            ->where('groups.data.0.group_name', 'North Zone')
            ->where('groups.data.0.owner_name', 'Anita Sharma')
            ->where('groups.data.0.status', 'active')
            ->where('groups.data.0.status_label', 'Active')
            ->where('groups.data.0.created_at', '20-09-2026')
            ->where('filters.sortBy', 'created_at')
            ->where('filters.sortOrder', 'desc')
            ->where('filters.pageSize', 10)
            ->has('userOptions', 2)
        );
});

test('unauthenticated users cannot create a group', function () {
    $owner = User::factory()->create();

    $this->post(route('master.groups.store'), [
        'group_name' => 'North Zone',
        'description' => 'North operations',
        'owner_id' => $owner->id,
        'status' => 'active',
    ])->assertRedirect(route('login'));

    $this->assertDatabaseMissing('mst_group', [
        'group_name' => 'North Zone',
    ]);
});

test('authenticated users can create a group', function () {
    $user = User::factory()->create();
    $owner = User::factory()->create();

    $this->actingAs($user)
        ->from(route('master.groups.index'))
        ->post(route('master.groups.store'), [
            'group_name' => 'North Zone',
            'description' => 'North operations',
            'owner_id' => $owner->id,
            'status' => 'active',
        ])
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('master.groups.index'));

    $this->assertDatabaseHas('mst_group', [
        'group_name' => 'North Zone',
        'owner_id' => $owner->id,
        'status' => 'active',
    ]);
});

test('group name is required when creating a group', function () {
    $user = User::factory()->create();
    $owner = User::factory()->create();

    $this->actingAs($user)
        ->from(route('master.groups.index'))
        ->post(route('master.groups.store'), [
            'group_name' => '',
            'description' => 'North operations',
            'owner_id' => $owner->id,
            'status' => 'active',
        ])
        ->assertSessionHasErrors(['group_name' => 'The group name field is required.'])
        ->assertRedirect(route('master.groups.index'));
});

test('a group must belong to an existing owner', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->from(route('master.groups.index'))
        ->post(route('master.groups.store'), [
            'group_name' => 'North Zone',
            'description' => 'North operations',
            'owner_id' => 999,
            'status' => 'active',
        ])
        ->assertSessionHasErrors('owner_id')
        ->assertRedirect(route('master.groups.index'));
});

test('duplicate group names are rejected', function () {
    $user = User::factory()->create();
    $owner = User::factory()->create();

    Group::factory()->create([
        'group_name' => 'North Zone',
    ]);

    $this->actingAs($user)
        ->from(route('master.groups.index'))
        ->post(route('master.groups.store'), [
            'group_name' => 'North Zone',
            'description' => 'Another description',
            'owner_id' => $owner->id,
            'status' => 'active',
        ])
        ->assertSessionHasErrors(['group_name' => 'The group name has already been taken.'])
        ->assertRedirect(route('master.groups.index'));
});

test('authenticated users can update a group', function () {
    $user = User::factory()->create();
    $owner = User::factory()->create();
    $group = Group::factory()->create([
        'group_name' => 'North Zone',
        'status' => Status::Active,
    ]);

    $this->actingAs($user)
        ->from(route('master.groups.index'))
        ->put(route('master.groups.update', $group), [
            'group_name' => 'South Zone',
            'description' => 'South operations',
            'owner_id' => $owner->id,
            'status' => 'inactive',
        ])
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('master.groups.index'));

    $group->refresh();

    expect($group->group_name)->toBe('South Zone')
        ->and($group->status)->toBe(Status::Inactive);
});

test('authenticated users can delete a group', function () {
    $user = User::factory()->create();
    $group = Group::factory()->create();

    $this->actingAs($user)
        ->from(route('master.groups.index'))
        ->delete(route('master.groups.destroy', $group))
        ->assertRedirect(route('master.groups.index'));

    $this->assertModelMissing($group);
});

test('a group assigned to a branch cannot be deleted', function () {
    $user = User::factory()->create();
    $group = Group::factory()->create();

    Branch::factory()->create([
        'group_id' => $group->id,
    ]);

    $this->actingAs($user)
        ->from(route('master.groups.index'))
        ->delete(route('master.groups.destroy', $group))
        ->assertRedirect(route('master.groups.index'));

    $this->assertModelExists($group);
});

test('groups can be searched by name', function () {
    $user = User::factory()->create();

    Group::factory()->create(['group_name' => 'North Zone']);
    Group::factory()->create(['group_name' => 'South Zone']);

    $this->actingAs($user)
        ->get(route('master.groups.index', ['search' => 'North']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->has('groups.data', 1)
            ->where('groups.data.0.group_name', 'North Zone')
            ->where('filters.search', 'North')
        );
});

test('groups can be sorted by name', function () {
    $user = User::factory()->create();

    Group::factory()->create(['group_name' => 'South Zone']);
    Group::factory()->create(['group_name' => 'North Zone']);

    $this->actingAs($user)
        ->get(route('master.groups.index', [
            'sortBy' => 'group_name',
            'sortOrder' => 'asc',
        ]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('groups.data.0.group_name', 'North Zone')
            ->where('groups.data.1.group_name', 'South Zone')
            ->where('filters.sortBy', 'group_name')
            ->where('filters.sortOrder', 'asc')
        );
});

test('invalid sort columns fall back to the default sort', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('master.groups.index', [
            'sortBy' => 'id;drop table users',
            'sortOrder' => 'desc',
        ]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('filters.sortBy', 'created_at')
        );
});

test('the group list paginates with the selected page size', function () {
    $user = User::factory()->create();

    Group::factory()->count(11)->create();

    $this->actingAs($user)
        ->get(route('master.groups.index', ['pageSize' => 10]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->has('groups.data', 10)
            ->where('groups.meta.total', 11)
            ->where('groups.meta.per_page', 10)
            ->where('groups.meta.last_page', 2)
        );
});
