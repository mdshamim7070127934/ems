<?php

use App\Enums\Master\Status;
use App\Models\Master\Module;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

test('guests are redirected to the login page', function () {
    $this->get(route('master.modules.index'))
        ->assertRedirect(route('login'));
});

test('authenticated users can view the module list', function () {
    $user = User::factory()->create();

    $this->travelTo('2026-09-20 10:00:00');

    Module::factory()->create([
        'module_name' => 'Users',
        'status' => Status::Active,
    ]);

    $this->actingAs($user)
        ->get(route('master.modules.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('master/modules/index')
            ->has('modules.data', 1)
            ->where('modules.data.0.module_name', 'Users')
            ->where('modules.data.0.status', 'active')
            ->where('modules.data.0.status_label', 'Active')
            ->where('modules.data.0.created_on', '20-09-2026')
            ->where('filters.sortBy', 'created_on')
            ->where('filters.sortOrder', 'desc')
            ->where('filters.pageSize', 10)
        );
});

test('unauthenticated users cannot create a module', function () {
    $this->post(route('master.modules.store'), [
        'module_name' => 'Users',
        'status' => 'active',
    ])->assertRedirect(route('login'));

    $this->assertDatabaseMissing('mst_module', [
        'module_name' => 'Users',
    ]);
});

test('authenticated users can create a module', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->from(route('master.modules.index'))
        ->post(route('master.modules.store'), [
            'module_name' => 'Users',
            'status' => 'active',
        ])
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('master.modules.index'));

    $this->assertDatabaseHas('mst_module', [
        'module_name' => 'Users',
        'status' => 'active',
    ]);
});

test('module name is required when creating a module', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->from(route('master.modules.index'))
        ->post(route('master.modules.store'), [
            'module_name' => '',
            'status' => 'active',
        ])
        ->assertSessionHasErrors(['module_name' => 'The name field is required.'])
        ->assertRedirect(route('master.modules.index'));
});

test('duplicate module names are rejected', function () {
    $user = User::factory()->create();

    Module::factory()->create([
        'module_name' => 'Users',
    ]);

    $this->actingAs($user)
        ->from(route('master.modules.index'))
        ->post(route('master.modules.store'), [
            'module_name' => 'Users',
            'status' => 'active',
        ])
        ->assertSessionHasErrors(['module_name' => 'The name has already been taken.'])
        ->assertRedirect(route('master.modules.index'));
});

test('invalid module status is rejected', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->from(route('master.modules.index'))
        ->post(route('master.modules.store'), [
            'module_name' => 'Users',
            'status' => 'archived',
        ])
        ->assertSessionHasErrors('status')
        ->assertRedirect(route('master.modules.index'));
});

test('authenticated users can update a module', function () {
    $user = User::factory()->create();
    $module = Module::factory()->create([
        'module_name' => 'Users',
        'status' => Status::Active,
    ]);

    $this->actingAs($user)
        ->from(route('master.modules.index'))
        ->put(route('master.modules.update', $module), [
            'module_name' => 'Billing',
            'status' => 'inactive',
        ])
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('master.modules.index'));

    $module->refresh();

    expect($module->module_name)->toBe('Billing')
        ->and($module->status)->toBe(Status::Inactive);
});

test('authenticated users can delete a module', function () {
    $user = User::factory()->create();
    $module = Module::factory()->create();

    $this->actingAs($user)
        ->from(route('master.modules.index'))
        ->delete(route('master.modules.destroy', $module))
        ->assertRedirect(route('master.modules.index'));

    $this->assertModelMissing($module);
});

test('modules can be searched by name', function () {
    $user = User::factory()->create();

    Module::factory()->create(['module_name' => 'Billing']);
    Module::factory()->create(['module_name' => 'Users']);

    $this->actingAs($user)
        ->get(route('master.modules.index', ['search' => 'Bill']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->has('modules.data', 1)
            ->where('modules.data.0.module_name', 'Billing')
            ->where('filters.search', 'Bill')
        );
});

test('modules can be sorted by name', function () {
    $user = User::factory()->create();

    Module::factory()->create(['module_name' => 'Users']);
    Module::factory()->create(['module_name' => 'Billing']);

    $this->actingAs($user)
        ->get(route('master.modules.index', [
            'sortBy' => 'module_name',
            'sortOrder' => 'asc',
        ]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('modules.data.0.module_name', 'Billing')
            ->where('modules.data.1.module_name', 'Users')
            ->where('filters.sortBy', 'module_name')
            ->where('filters.sortOrder', 'asc')
        );
});

test('invalid sort columns fall back to the default sort', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('master.modules.index', [
            'sortBy' => 'module_id;drop table users',
            'sortOrder' => 'desc',
        ]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('filters.sortBy', 'created_on')
        );
});

test('the module list paginates with the selected page size', function () {
    $user = User::factory()->create();

    Module::factory()->count(11)->create();

    $this->actingAs($user)
        ->get(route('master.modules.index', ['pageSize' => 10]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->has('modules.data', 10)
            ->where('modules.meta.total', 11)
            ->where('modules.meta.per_page', 10)
            ->where('modules.meta.last_page', 2)
        );
});
