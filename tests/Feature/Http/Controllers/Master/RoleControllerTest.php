<?php

use App\Models\Master\Menu;
use App\Models\Master\Role;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

test('guests are redirected to the login page', function () {
    $this->get(route('master.roles.index'))
        ->assertRedirect(route('login'));
});

test('authenticated users can view the role list', function () {
    $user = User::factory()->create();

    $this->travelTo('2026-09-20 10:00:00');

    Role::factory()->create([
        'role_name' => 'manager',
        'display_name' => 'Manager',
        'is_active' => true,
    ]);

    $this->actingAs($user)
        ->get(route('master.roles.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('master/roles/index')
            ->has('roles.data', 1)
            ->where('roles.data.0.role_name', 'manager')
            ->where('roles.data.0.display_name', 'Manager')
            ->where('roles.data.0.status', 'active')
            ->where('roles.data.0.status_label', 'Active')
            ->where('roles.data.0.is_protected', false)
            ->where('roles.data.0.created_at', '20-09-2026')
            ->where('filters.sortBy', 'created_at')
            ->where('filters.sortOrder', 'desc')
            ->where('filters.pageSize', 10)
        );
});

test('authenticated users can view the create role page', function () {
    $user = User::factory()->create();
    $parent = Menu::factory()->create(['menu_name' => 'Masters', 'menu_parent_id' => 0]);
    Menu::factory()->create([
        'menu_name' => 'Countries',
        'menu_parent_id' => $parent->id,
        'menu_route' => '/master/countries',
    ]);

    $this->actingAs($user)
        ->get(route('master.roles.create'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('master/roles/form')
            ->where('role', null)
            ->has('menuGroups', 1)
            ->where('menuGroups.0.label', 'Masters')
            ->has('menuGroups.0.menus', 2)
        );
});

test('unauthenticated users cannot create a role', function () {
    $this->post(route('master.roles.store'), [
        'role_name' => 'manager',
        'display_name' => 'Manager',
        'is_active' => true,
        'menu_ids' => [],
    ])->assertRedirect(route('login'));

    $this->assertDatabaseMissing('roles', [
        'role_name' => 'manager',
    ]);
});

test('authenticated users can create a role with menu permissions', function () {
    $user = User::factory()->create();
    $menu = Menu::factory()->create(['menu_name' => 'Countries']);

    $this->actingAs($user)
        ->from(route('master.roles.create'))
        ->post(route('master.roles.store'), [
            'role_name' => 'Manager',
            'display_name' => 'Manager',
            'is_active' => true,
            'menu_ids' => [$menu->id],
        ])
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('master.roles.index'));

    $this->assertDatabaseHas('roles', [
        'role_name' => 'manager',
        'display_name' => 'Manager',
        'is_active' => true,
        'is_super_admin' => false,
    ]);
    $this->assertDatabaseHas('role_menu_permissions', [
        'menu_id' => $menu->id,
        'can_access' => true,
    ]);
});

test('role name is required when creating a role', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->from(route('master.roles.create'))
        ->post(route('master.roles.store'), [
            'role_name' => '',
            'display_name' => 'Manager',
            'is_active' => true,
            'menu_ids' => [],
        ])
        ->assertSessionHasErrors(['role_name' => 'The role name field is required.'])
        ->assertRedirect(route('master.roles.create'));
});

test('role name must use a valid format', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->from(route('master.roles.create'))
        ->post(route('master.roles.store'), [
            'role_name' => '1manager',
            'display_name' => 'Manager',
            'is_active' => true,
            'menu_ids' => [],
        ])
        ->assertSessionHasErrors('role_name')
        ->assertRedirect(route('master.roles.create'));
});

test('duplicate role names are rejected', function () {
    $user = User::factory()->create();
    Role::factory()->create(['role_name' => 'manager']);

    $this->actingAs($user)
        ->from(route('master.roles.create'))
        ->post(route('master.roles.store'), [
            'role_name' => 'manager',
            'display_name' => 'Team Manager',
            'is_active' => true,
            'menu_ids' => [],
        ])
        ->assertSessionHasErrors('role_name')
        ->assertRedirect(route('master.roles.create'));
});

test('a role menu must exist', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->from(route('master.roles.create'))
        ->post(route('master.roles.store'), [
            'role_name' => 'manager',
            'display_name' => 'Manager',
            'is_active' => true,
            'menu_ids' => [999],
        ])
        ->assertSessionHasErrors('menu_ids.0')
        ->assertRedirect(route('master.roles.create'));
});

test('authenticated users can view the role page', function () {
    $user = User::factory()->create();
    $menu = Menu::factory()->create(['menu_name' => 'Countries']);
    $role = Role::factory()->create([
        'role_name' => 'manager',
        'display_name' => 'Manager',
    ]);
    $role->syncMenuPermissions([$menu->id]);

    $this->actingAs($user)
        ->get(route('master.roles.show', $role))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('master/roles/show')
            ->where('role.role_name', 'manager')
            ->where('role.display_name', 'Manager')
            ->where('role.menu_ids', [$menu->id])
            ->has('menuGroups')
        );
});

test('guests are redirected from the role page', function () {
    $role = Role::factory()->create();

    $this->get(route('master.roles.show', $role))
        ->assertRedirect(route('login'));
});

test('authenticated users can view the edit role page', function () {
    $user = User::factory()->create();
    $menu = Menu::factory()->create(['menu_name' => 'Countries']);
    $role = Role::factory()->create([
        'role_name' => 'manager',
        'display_name' => 'Manager',
    ]);
    $role->syncMenuPermissions([$menu->id]);

    $this->actingAs($user)
        ->get(route('master.roles.edit', $role))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('master/roles/form')
            ->where('role.role_name', 'manager')
            ->where('role.display_name', 'Manager')
            ->where('role.menu_ids', [$menu->id])
        );
});

test('authenticated users can update a role', function () {
    $user = User::factory()->create();
    $firstMenu = Menu::factory()->create();
    $secondMenu = Menu::factory()->create();
    $role = Role::factory()->create([
        'role_name' => 'manager',
        'display_name' => 'Manager',
        'is_active' => true,
    ]);
    $role->syncMenuPermissions([$firstMenu->id]);

    $this->actingAs($user)
        ->from(route('master.roles.edit', $role))
        ->put(route('master.roles.update', $role), [
            'role_name' => 'viewer',
            'display_name' => 'Viewer',
            'is_active' => false,
            'menu_ids' => [$secondMenu->id],
        ])
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('master.roles.index'));

    $role->refresh();

    expect($role->role_name)->toBe('viewer')
        ->and($role->display_name)->toBe('Viewer')
        ->and($role->is_active)->toBeFalse();

    $this->assertDatabaseHas('role_menu_permissions', [
        'role_id' => $role->id,
        'menu_id' => $secondMenu->id,
    ]);
    $this->assertDatabaseMissing('role_menu_permissions', [
        'role_id' => $role->id,
        'menu_id' => $firstMenu->id,
    ]);
});

test('a super administrator role cannot be updated', function () {
    $user = User::factory()->create();
    $role = Role::factory()->superAdmin()->create();

    $this->actingAs($user)
        ->from(route('master.roles.edit', $role))
        ->put(route('master.roles.update', $role), [
            'role_name' => 'changed',
            'display_name' => 'Changed',
            'is_active' => false,
            'menu_ids' => [],
        ])
        ->assertRedirect(route('master.roles.edit', $role));

    $role->refresh();

    expect($role->role_name)->toBe('admin')
        ->and($role->display_name)->toBe('Administrator')
        ->and($role->is_active)->toBeTrue();
});

test('authenticated users can delete a role', function () {
    $user = User::factory()->create();
    $menu = Menu::factory()->create();
    $role = Role::factory()->create();
    $role->syncMenuPermissions([$menu->id]);

    $this->actingAs($user)
        ->from(route('master.roles.index'))
        ->delete(route('master.roles.destroy', $role))
        ->assertRedirect(route('master.roles.index'));

    $this->assertModelMissing($role);
    $this->assertDatabaseMissing('role_menu_permissions', [
        'role_id' => $role->id,
    ]);
});

test('a super administrator role cannot be deleted', function () {
    $user = User::factory()->create();
    $role = Role::factory()->superAdmin()->create();

    $this->actingAs($user)
        ->from(route('master.roles.index'))
        ->delete(route('master.roles.destroy', $role))
        ->assertRedirect(route('master.roles.index'));

    $this->assertModelExists($role);
});

test('a role assigned to users cannot be deleted', function () {
    $user = User::factory()->create();
    $role = Role::factory()->create();
    User::factory()->create(['role_id' => $role->id]);

    $this->actingAs($user)
        ->from(route('master.roles.index'))
        ->delete(route('master.roles.destroy', $role))
        ->assertRedirect(route('master.roles.index'));

    $this->assertModelExists($role);
});

test('roles can be searched by name', function () {
    $user = User::factory()->create();

    Role::factory()->create([
        'role_name' => 'manager',
        'display_name' => 'Branch Manager',
    ]);
    Role::factory()->create([
        'role_name' => 'viewer',
        'display_name' => 'Read Only',
    ]);

    $this->actingAs($user)
        ->get(route('master.roles.index', ['search' => 'manag']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->has('roles.data', 1)
            ->where('roles.data.0.role_name', 'manager')
            ->where('filters.search', 'manag')
        );
});

test('the role list paginates with the selected page size', function () {
    $user = User::factory()->create();
    Role::factory()->count(11)->create();

    $this->actingAs($user)
        ->get(route('master.roles.index', ['pageSize' => 10]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->has('roles.data', 10)
            ->where('roles.meta.total', 11)
            ->where('roles.meta.per_page', 10)
        );
});
