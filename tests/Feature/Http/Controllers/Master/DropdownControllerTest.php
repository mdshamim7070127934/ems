<?php

use App\Enums\Master\Status;
use App\Models\Master\Dropdown;
use App\Models\Master\Module;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

test('guests are redirected to the login page', function () {
    $this->get(route('master.dropdowns.index'))
        ->assertRedirect(route('login'));
});

test('authenticated users can view the dropdown list', function () {
    $user = User::factory()->create();
    $module = Module::factory()->create([
        'module_name' => 'Users',
    ]);

    $this->travelTo('2026-09-20 10:00:00');

    Dropdown::factory()->create([
        'module_id' => $module->module_id,
        'name' => 'Priority',
        'status' => Status::Active,
    ]);

    $this->actingAs($user)
        ->get(route('master.dropdowns.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('master/dropdowns/index')
            ->has('dropdowns.data', 1)
            ->where('dropdowns.data.0.name', 'Priority')
            ->where('dropdowns.data.0.module_name', 'Users')
            ->where('dropdowns.data.0.status', 'active')
            ->where('dropdowns.data.0.status_label', 'Active')
            ->where('dropdowns.data.0.created_on', '20-09-2026')
            ->where('filters.sortBy', 'created_on')
            ->where('filters.sortOrder', 'desc')
            ->where('filters.pageSize', 10)
            ->has('moduleOptions', 1)
        );
});

test('unauthenticated users cannot create a dropdown', function () {
    $module = Module::factory()->create();

    $this->post(route('master.dropdowns.store'), [
        'module_id' => $module->module_id,
        'name' => 'Priority',
        'status' => 'active',
    ])->assertRedirect(route('login'));

    $this->assertDatabaseMissing('mst_dropdown', [
        'name' => 'Priority',
    ]);
});

test('authenticated users can create a dropdown', function () {
    $user = User::factory()->create();
    $module = Module::factory()->create();

    $this->actingAs($user)
        ->from(route('master.dropdowns.index'))
        ->post(route('master.dropdowns.store'), [
            'module_id' => $module->module_id,
            'name' => 'Priority',
            'status' => 'active',
        ])
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('master.dropdowns.index'));

    $this->assertDatabaseHas('mst_dropdown', [
        'module_id' => $module->module_id,
        'name' => 'Priority',
        'status' => 'active',
    ]);
});

test('dropdown name is required when creating a dropdown', function () {
    $user = User::factory()->create();
    $module = Module::factory()->create();

    $this->actingAs($user)
        ->from(route('master.dropdowns.index'))
        ->post(route('master.dropdowns.store'), [
            'module_id' => $module->module_id,
            'name' => '',
            'status' => 'active',
        ])
        ->assertSessionHasErrors(['name' => 'The name field is required.'])
        ->assertRedirect(route('master.dropdowns.index'));
});

test('a dropdown must belong to an existing module', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->from(route('master.dropdowns.index'))
        ->post(route('master.dropdowns.store'), [
            'module_id' => 999,
            'name' => 'Priority',
            'status' => 'active',
        ])
        ->assertSessionHasErrors('module_id')
        ->assertRedirect(route('master.dropdowns.index'));
});

test('duplicate dropdown names in the same module are rejected', function () {
    $user = User::factory()->create();
    $module = Module::factory()->create();

    Dropdown::factory()->create([
        'module_id' => $module->module_id,
        'name' => 'Priority',
    ]);

    $this->actingAs($user)
        ->from(route('master.dropdowns.index'))
        ->post(route('master.dropdowns.store'), [
            'module_id' => $module->module_id,
            'name' => 'Priority',
            'status' => 'active',
        ])
        ->assertSessionHasErrors(['name' => 'The name has already been taken.'])
        ->assertRedirect(route('master.dropdowns.index'));
});

test('the same dropdown name can exist in a different module', function () {
    $user = User::factory()->create();
    $firstModule = Module::factory()->create();
    $secondModule = Module::factory()->create();

    Dropdown::factory()->create([
        'module_id' => $firstModule->module_id,
        'name' => 'Priority',
    ]);

    $this->actingAs($user)
        ->from(route('master.dropdowns.index'))
        ->post(route('master.dropdowns.store'), [
            'module_id' => $secondModule->module_id,
            'name' => 'Priority',
            'status' => 'active',
        ])
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('master.dropdowns.index'));

    expect(Dropdown::query()->where('name', 'Priority')->count())->toBe(2);
});

test('invalid dropdown status is rejected', function () {
    $user = User::factory()->create();
    $module = Module::factory()->create();

    $this->actingAs($user)
        ->from(route('master.dropdowns.index'))
        ->post(route('master.dropdowns.store'), [
            'module_id' => $module->module_id,
            'name' => 'Priority',
            'status' => 'archived',
        ])
        ->assertSessionHasErrors('status')
        ->assertRedirect(route('master.dropdowns.index'));
});

test('authenticated users can update a dropdown', function () {
    $user = User::factory()->create();
    $module = Module::factory()->create();
    $dropdown = Dropdown::factory()->create([
        'module_id' => $module->module_id,
        'name' => 'Priority',
        'status' => Status::Active,
    ]);

    $this->actingAs($user)
        ->from(route('master.dropdowns.index'))
        ->put(route('master.dropdowns.update', $dropdown), [
            'module_id' => $module->module_id,
            'name' => 'Severity',
            'status' => 'inactive',
        ])
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('master.dropdowns.index'));

    $dropdown->refresh();

    expect($dropdown->name)->toBe('Severity')
        ->and($dropdown->status)->toBe(Status::Inactive);
});

test('authenticated users can delete a dropdown', function () {
    $user = User::factory()->create();
    $dropdown = Dropdown::factory()->create();

    $this->actingAs($user)
        ->from(route('master.dropdowns.index'))
        ->delete(route('master.dropdowns.destroy', $dropdown))
        ->assertRedirect(route('master.dropdowns.index'));

    $this->assertModelMissing($dropdown);
});

test('dropdowns can be searched by name', function () {
    $user = User::factory()->create();
    $module = Module::factory()->create();

    Dropdown::factory()->create([
        'module_id' => $module->module_id,
        'name' => 'Priority',
    ]);
    Dropdown::factory()->create([
        'module_id' => $module->module_id,
        'name' => 'Status',
    ]);

    $this->actingAs($user)
        ->get(route('master.dropdowns.index', ['search' => 'Prio']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->has('dropdowns.data', 1)
            ->where('dropdowns.data.0.name', 'Priority')
            ->where('filters.search', 'Prio')
        );
});

test('dropdowns can be searched by module name', function () {
    $user = User::factory()->create();

    Dropdown::factory()->create([
        'module_id' => Module::factory()->create(['module_name' => 'Billing'])->module_id,
        'name' => 'Priority',
    ]);
    Dropdown::factory()->create([
        'module_id' => Module::factory()->create(['module_name' => 'Users'])->module_id,
        'name' => 'Status',
    ]);

    $this->actingAs($user)
        ->get(route('master.dropdowns.index', ['search' => 'Bill']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->has('dropdowns.data', 1)
            ->where('dropdowns.data.0.module_name', 'Billing')
        );
});

test('dropdowns can be sorted by name', function () {
    $user = User::factory()->create();
    $module = Module::factory()->create();

    Dropdown::factory()->create([
        'module_id' => $module->module_id,
        'name' => 'Status',
    ]);
    Dropdown::factory()->create([
        'module_id' => $module->module_id,
        'name' => 'Priority',
    ]);

    $this->actingAs($user)
        ->get(route('master.dropdowns.index', [
            'sortBy' => 'name',
            'sortOrder' => 'asc',
        ]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('dropdowns.data.0.name', 'Priority')
            ->where('dropdowns.data.1.name', 'Status')
            ->where('filters.sortBy', 'name')
            ->where('filters.sortOrder', 'asc')
        );
});

test('invalid sort columns fall back to the default sort', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('master.dropdowns.index', [
            'sortBy' => 'option_type_id;drop table users',
            'sortOrder' => 'desc',
        ]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('filters.sortBy', 'created_on')
        );
});

test('the dropdown list paginates with the selected page size', function () {
    $user = User::factory()->create();
    $module = Module::factory()->create();

    Dropdown::factory()->count(11)->create([
        'module_id' => $module->module_id,
    ]);

    $this->actingAs($user)
        ->get(route('master.dropdowns.index', ['pageSize' => 10]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->has('dropdowns.data', 10)
            ->where('dropdowns.meta.total', 11)
            ->where('dropdowns.meta.per_page', 10)
            ->where('dropdowns.meta.last_page', 2)
        );
});
