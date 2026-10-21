<?php

use App\Enums\Master\Status;
use App\Models\Master\Menu;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

test('guests are redirected to the login page', function () {
    $this->get(route('master.menus.index'))
        ->assertRedirect(route('login'));
});

test('authenticated users can view the menu list', function () {
    $user = User::factory()->create();

    $this->travelTo('2026-09-20 10:00:00');

    Menu::factory()->create([
        'menu_name' => 'Dashboard',
        'menu_route' => '/dashboard',
        'menu_order' => 1,
        'status' => Status::Active,
    ]);

    $this->actingAs($user)
        ->get(route('master.menus.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('master/menus/index')
            ->has('menus.data', 1)
            ->where('menus.data.0.menu_name', 'Dashboard')
            ->where('menus.data.0.menu_route', '/dashboard')
            ->where('menus.data.0.menu_parent_id', 0)
            ->where('menus.data.0.parent_name', null)
            ->where('menus.data.0.status', 'active')
            ->where('menus.data.0.created_at', '20-09-2026')
            ->where('filters.sortBy', 'created_at')
            ->has('parentOptions', 1)
        );
});

test('unauthenticated users cannot create a menu', function () {
    $this->post(route('master.menus.store'), [
        'menu_parent_id' => 0,
        'menu_name' => 'Dashboard',
        'menu_route' => '/dashboard',
        'menu_order' => 1,
        'status' => 'active',
    ])->assertRedirect(route('login'));

    $this->assertDatabaseMissing('mst_menu', [
        'menu_name' => 'Dashboard',
    ]);
});

test('authenticated users can create a menu', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->from(route('master.menus.index'))
        ->post(route('master.menus.store'), [
            'menu_parent_id' => 0,
            'menu_name' => 'Dashboard',
            'column_description' => 'Main dashboard',
            'menu_route' => '/dashboard',
            'menu_order' => 1,
            'screen_name' => 'Dashboard',
            'web_icon' => 'layout-grid',
            'status' => 'active',
        ])
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('master.menus.index'));

    $this->assertDatabaseHas('mst_menu', [
        'menu_parent_id' => 0,
        'menu_name' => 'Dashboard',
        'menu_route' => '/dashboard',
        'menu_order' => 1,
        'status' => 'active',
    ]);
});

test('menu name is required when creating a record', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->from(route('master.menus.index'))
        ->post(route('master.menus.store'), [
            'menu_parent_id' => 0,
            'menu_name' => '',
            'menu_route' => '/dashboard',
            'menu_order' => 1,
            'status' => 'active',
        ])
        ->assertSessionHasErrors(['menu_name' => 'The menu name field is required.'])
        ->assertRedirect(route('master.menus.index'));
});

test('duplicate menu names under the same parent are rejected', function () {
    $user = User::factory()->create();

    Menu::factory()->create([
        'menu_parent_id' => 0,
        'menu_name' => 'Dashboard',
    ]);

    $this->actingAs($user)
        ->from(route('master.menus.index'))
        ->post(route('master.menus.store'), [
            'menu_parent_id' => 0,
            'menu_name' => 'Dashboard',
            'menu_route' => '/home',
            'menu_order' => 2,
            'status' => 'active',
        ])
        ->assertSessionHasErrors(['menu_name' => 'The menu name has already been taken.'])
        ->assertRedirect(route('master.menus.index'));
});

test('the same menu name can exist under a different parent', function () {
    $user = User::factory()->create();
    $parent = Menu::factory()->create([
        'menu_name' => 'Master',
        'menu_route' => '/master',
    ]);

    Menu::factory()->create([
        'menu_parent_id' => 0,
        'menu_name' => 'List',
        'menu_route' => '/list',
    ]);

    $this->actingAs($user)
        ->from(route('master.menus.index'))
        ->post(route('master.menus.store'), [
            'menu_parent_id' => $parent->id,
            'menu_name' => 'List',
            'menu_route' => '/master/list',
            'menu_order' => 1,
            'status' => 'active',
        ])
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('master.menus.index'));

    expect(Menu::query()->where('menu_name', 'List')->count())->toBe(2);
});

test('authenticated users can create a menu with a parent', function () {
    $user = User::factory()->create();
    $parent = Menu::factory()->create([
        'menu_name' => 'Master',
        'menu_route' => '/master',
    ]);

    $this->actingAs($user)
        ->from(route('master.menus.index'))
        ->post(route('master.menus.store'), [
            'menu_parent_id' => $parent->id,
            'menu_name' => 'Modules',
            'menu_route' => '/master/modules',
            'menu_order' => 1,
            'status' => 'active',
        ])
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('master.menus.index'));

    $this->assertDatabaseHas('mst_menu', [
        'menu_parent_id' => $parent->id,
        'menu_name' => 'Modules',
        'menu_route' => '/master/modules',
    ]);
});

test('an invalid parent is rejected', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->from(route('master.menus.index'))
        ->post(route('master.menus.store'), [
            'menu_parent_id' => 999,
            'menu_name' => 'Modules',
            'menu_route' => '/master/modules',
            'menu_order' => 1,
            'status' => 'active',
        ])
        ->assertSessionHasErrors('menu_parent_id')
        ->assertRedirect(route('master.menus.index'));
});

test('authenticated users can update a menu', function () {
    $user = User::factory()->create();
    $menu = Menu::factory()->create([
        'menu_name' => 'Dashboard',
        'menu_route' => '/dashboard',
        'menu_order' => 1,
        'status' => Status::Active,
    ]);

    $this->actingAs($user)
        ->from(route('master.menus.index'))
        ->put(route('master.menus.update', $menu), [
            'menu_parent_id' => 0,
            'menu_name' => 'Home',
            'menu_route' => '/home',
            'menu_order' => 2,
            'status' => 'inactive',
        ])
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('master.menus.index'));

    $menu->refresh();

    expect($menu->menu_name)->toBe('Home')
        ->and($menu->menu_route)->toBe('/home')
        ->and($menu->menu_order)->toBe(2)
        ->and($menu->status)->toBe(Status::Inactive);
});

test('a record cannot use itself as a parent', function () {
    $user = User::factory()->create();
    $menu = Menu::factory()->create([
        'menu_name' => 'Dashboard',
    ]);

    $this->actingAs($user)
        ->from(route('master.menus.index'))
        ->put(route('master.menus.update', $menu), [
            'menu_parent_id' => $menu->id,
            'menu_name' => 'Dashboard',
            'menu_route' => '/dashboard',
            'menu_order' => 1,
            'status' => 'active',
        ])
        ->assertSessionHasErrors('menu_parent_id')
        ->assertRedirect(route('master.menus.index'));
});

test('authenticated users can delete a menu', function () {
    $user = User::factory()->create();
    $menu = Menu::factory()->create();

    $this->actingAs($user)
        ->from(route('master.menus.index'))
        ->delete(route('master.menus.destroy', $menu))
        ->assertRedirect(route('master.menus.index'));

    $this->assertModelMissing($menu);
});

test('menus can be searched by name', function () {
    $user = User::factory()->create();

    Menu::factory()->create(['menu_name' => 'Dashboard']);
    Menu::factory()->create(['menu_name' => 'Reports']);

    $this->actingAs($user)
        ->get(route('master.menus.index', ['search' => 'Dash']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->has('menus.data', 1)
            ->where('menus.data.0.menu_name', 'Dashboard')
        );
});

test('menus can be sorted by name', function () {
    $user = User::factory()->create();

    Menu::factory()->create(['menu_name' => 'Reports']);
    Menu::factory()->create(['menu_name' => 'Dashboard']);

    $this->actingAs($user)
        ->get(route('master.menus.index', [
            'sortBy' => 'menu_name',
            'sortOrder' => 'asc',
        ]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('menus.data.0.menu_name', 'Dashboard')
            ->where('menus.data.1.menu_name', 'Reports')
            ->where('filters.sortBy', 'menu_name')
        );
});

test('invalid sort columns fall back to the default sort', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('master.menus.index', [
            'sortBy' => 'id;drop table users',
            'sortOrder' => 'desc',
        ]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('filters.sortBy', 'created_at')
        );
});

test('the menu list paginates with the selected page size', function () {
    $user = User::factory()->create();

    Menu::factory()->count(11)->create();

    $this->actingAs($user)
        ->get(route('master.menus.index', ['pageSize' => 10]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->has('menus.data', 10)
            ->where('menus.meta.total', 11)
            ->where('menus.meta.per_page', 10)
            ->where('menus.meta.last_page', 2)
        );
});
