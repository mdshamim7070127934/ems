<?php

use App\Enums\Master\Status;
use App\Models\Master\Dropdown;
use App\Models\Master\DropdownOptionValue;
use App\Models\Master\Module;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

test('guests are redirected to the login page', function () {
    $this->get(route('master.dropdown-option-values.index'))
        ->assertRedirect(route('login'));
});

test('authenticated users can view the option value list', function () {
    $user = User::factory()->create();
    $module = Module::factory()->create(['module_name' => 'Users']);
    $dropdown = Dropdown::factory()->create([
        'module_id' => $module->module_id,
        'name' => 'Priority',
    ]);

    $this->travelTo('2026-09-20 10:00:00');

    DropdownOptionValue::factory()->create([
        'option_type_id' => $dropdown->option_type_id,
        'module_id' => $module->module_id,
        'option_value' => 'High',
        'status' => Status::Active,
    ]);

    $this->actingAs($user)
        ->get(route('master.dropdown-option-values.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('master/dropdown-option-values/index')
            ->has('optionValues.data', 1)
            ->where('optionValues.data.0.option_value', 'High')
            ->where('optionValues.data.0.dropdown_name', 'Priority')
            ->where('optionValues.data.0.module_name', 'Users')
            ->where('optionValues.data.0.parent_id', 0)
            ->where('optionValues.data.0.status', 'active')
            ->where('optionValues.data.0.created_on', '20-09-2026')
            ->has('moduleOptions', 1)
            ->has('dropdownOptions', 1)
        );
});

test('unauthenticated users cannot create an option value', function () {
    $dropdown = Dropdown::factory()->create();

    $this->post(route('master.dropdown-option-values.store'), [
        'module_id' => $dropdown->module_id,
        'option_type_id' => $dropdown->option_type_id,
        'parent_id' => 0,
        'option_value' => 'High',
        'status' => 'active',
    ])->assertRedirect(route('login'));

    $this->assertDatabaseMissing('mst_dropdown_option_values', [
        'option_value' => 'High',
    ]);
});

test('authenticated users can create an option value', function () {
    $user = User::factory()->create();
    $dropdown = Dropdown::factory()->create();

    $this->actingAs($user)
        ->from(route('master.dropdown-option-values.index'))
        ->post(route('master.dropdown-option-values.store'), [
            'module_id' => $dropdown->module_id,
            'option_type_id' => $dropdown->option_type_id,
            'parent_id' => 0,
            'option_value' => 'High',
            'position' => 1,
            'master_type' => 'Service',
            'status' => 'active',
        ])
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('master.dropdown-option-values.index'));

    $this->assertDatabaseHas('mst_dropdown_option_values', [
        'module_id' => $dropdown->module_id,
        'option_type_id' => $dropdown->option_type_id,
        'option_value' => 'High',
        'position' => 1,
        'master_type' => 'Service',
        'status' => 'active',
    ]);
});

test('option value is required when creating a record', function () {
    $user = User::factory()->create();
    $dropdown = Dropdown::factory()->create();

    $this->actingAs($user)
        ->from(route('master.dropdown-option-values.index'))
        ->post(route('master.dropdown-option-values.store'), [
            'module_id' => $dropdown->module_id,
            'option_type_id' => $dropdown->option_type_id,
            'parent_id' => 0,
            'option_value' => '',
            'status' => 'active',
        ])
        ->assertSessionHasErrors(['option_value' => 'The option value field is required.'])
        ->assertRedirect(route('master.dropdown-option-values.index'));
});

test('an option type must belong to the selected module', function () {
    $user = User::factory()->create();
    $module = Module::factory()->create();
    $dropdown = Dropdown::factory()->create();

    $this->actingAs($user)
        ->from(route('master.dropdown-option-values.index'))
        ->post(route('master.dropdown-option-values.store'), [
            'module_id' => $module->module_id,
            'option_type_id' => $dropdown->option_type_id,
            'parent_id' => 0,
            'option_value' => 'High',
            'status' => 'active',
        ])
        ->assertSessionHasErrors('option_type_id')
        ->assertRedirect(route('master.dropdown-option-values.index'));
});

test('duplicate option values in the same dropdown are rejected', function () {
    $user = User::factory()->create();
    $dropdown = Dropdown::factory()->create();

    DropdownOptionValue::factory()->create([
        'option_type_id' => $dropdown->option_type_id,
        'module_id' => $dropdown->module_id,
        'option_value' => 'High',
    ]);

    $this->actingAs($user)
        ->from(route('master.dropdown-option-values.index'))
        ->post(route('master.dropdown-option-values.store'), [
            'module_id' => $dropdown->module_id,
            'option_type_id' => $dropdown->option_type_id,
            'parent_id' => 0,
            'option_value' => 'High',
            'status' => 'active',
        ])
        ->assertSessionHasErrors(['option_value' => 'The option value has already been taken.'])
        ->assertRedirect(route('master.dropdown-option-values.index'));
});

test('the same option value can exist in a different dropdown', function () {
    $user = User::factory()->create();
    $firstDropdown = Dropdown::factory()->create();
    $secondDropdown = Dropdown::factory()->create();

    DropdownOptionValue::factory()->create([
        'option_type_id' => $firstDropdown->option_type_id,
        'module_id' => $firstDropdown->module_id,
        'option_value' => 'High',
    ]);

    $this->actingAs($user)
        ->from(route('master.dropdown-option-values.index'))
        ->post(route('master.dropdown-option-values.store'), [
            'module_id' => $secondDropdown->module_id,
            'option_type_id' => $secondDropdown->option_type_id,
            'parent_id' => 0,
            'option_value' => 'High',
            'status' => 'active',
        ])
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('master.dropdown-option-values.index'));

    expect(DropdownOptionValue::query()->where('option_value', 'High')->count())->toBe(2);
});

test('authenticated users can create an option value with a parent', function () {
    $user = User::factory()->create();
    $dropdown = Dropdown::factory()->create();
    $parent = DropdownOptionValue::factory()->create([
        'option_type_id' => $dropdown->option_type_id,
        'module_id' => $dropdown->module_id,
        'option_value' => 'Parent',
    ]);

    $this->actingAs($user)
        ->from(route('master.dropdown-option-values.index'))
        ->post(route('master.dropdown-option-values.store'), [
            'module_id' => $dropdown->module_id,
            'option_type_id' => $dropdown->option_type_id,
            'parent_id' => $parent->id,
            'option_value' => 'Child',
            'status' => 'active',
        ])
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('master.dropdown-option-values.index'));

    $this->assertDatabaseHas('mst_dropdown_option_values', [
        'option_type_id' => $dropdown->option_type_id,
        'parent_id' => $parent->id,
        'option_value' => 'Child',
    ]);
});

test('a parent must belong to the same dropdown', function () {
    $user = User::factory()->create();
    $dropdown = Dropdown::factory()->create();
    $otherDropdown = Dropdown::factory()->create();
    $parent = DropdownOptionValue::factory()->create([
        'option_type_id' => $otherDropdown->option_type_id,
        'module_id' => $otherDropdown->module_id,
        'option_value' => 'Parent',
    ]);

    $this->actingAs($user)
        ->from(route('master.dropdown-option-values.index'))
        ->post(route('master.dropdown-option-values.store'), [
            'module_id' => $dropdown->module_id,
            'option_type_id' => $dropdown->option_type_id,
            'parent_id' => $parent->id,
            'option_value' => 'Child',
            'status' => 'active',
        ])
        ->assertSessionHasErrors('parent_id')
        ->assertRedirect(route('master.dropdown-option-values.index'));
});

test('authenticated users can update an option value', function () {
    $user = User::factory()->create();
    $dropdown = Dropdown::factory()->create();
    $optionValue = DropdownOptionValue::factory()->create([
        'option_type_id' => $dropdown->option_type_id,
        'module_id' => $dropdown->module_id,
        'option_value' => 'High',
        'status' => Status::Active,
    ]);

    $this->actingAs($user)
        ->from(route('master.dropdown-option-values.index'))
        ->put(route('master.dropdown-option-values.update', $optionValue), [
            'module_id' => $dropdown->module_id,
            'option_type_id' => $dropdown->option_type_id,
            'parent_id' => 0,
            'option_value' => 'Critical',
            'status' => 'inactive',
        ])
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('master.dropdown-option-values.index'));

    $optionValue->refresh();

    expect($optionValue->option_value)->toBe('Critical')
        ->and($optionValue->status)->toBe(Status::Inactive);
});

test('a record cannot use itself as a parent', function () {
    $user = User::factory()->create();
    $dropdown = Dropdown::factory()->create();
    $optionValue = DropdownOptionValue::factory()->create([
        'option_type_id' => $dropdown->option_type_id,
        'module_id' => $dropdown->module_id,
        'option_value' => 'High',
    ]);

    $this->actingAs($user)
        ->from(route('master.dropdown-option-values.index'))
        ->put(route('master.dropdown-option-values.update', $optionValue), [
            'module_id' => $dropdown->module_id,
            'option_type_id' => $dropdown->option_type_id,
            'parent_id' => $optionValue->id,
            'option_value' => 'High',
            'status' => 'active',
        ])
        ->assertSessionHasErrors('parent_id')
        ->assertRedirect(route('master.dropdown-option-values.index'));
});

test('authenticated users can delete an option value', function () {
    $user = User::factory()->create();
    $optionValue = DropdownOptionValue::factory()->create();

    $this->actingAs($user)
        ->from(route('master.dropdown-option-values.index'))
        ->delete(route('master.dropdown-option-values.destroy', $optionValue))
        ->assertRedirect(route('master.dropdown-option-values.index'));

    $this->assertModelMissing($optionValue);
});

test('option values can be searched by value', function () {
    $user = User::factory()->create();
    $dropdown = Dropdown::factory()->create();

    DropdownOptionValue::factory()->create([
        'option_type_id' => $dropdown->option_type_id,
        'module_id' => $dropdown->module_id,
        'option_value' => 'High',
    ]);
    DropdownOptionValue::factory()->create([
        'option_type_id' => $dropdown->option_type_id,
        'module_id' => $dropdown->module_id,
        'option_value' => 'Low',
    ]);

    $this->actingAs($user)
        ->get(route('master.dropdown-option-values.index', ['search' => 'Hig']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->has('optionValues.data', 1)
            ->where('optionValues.data.0.option_value', 'High')
        );
});

test('option values can be sorted by value', function () {
    $user = User::factory()->create();
    $dropdown = Dropdown::factory()->create();

    DropdownOptionValue::factory()->create([
        'option_type_id' => $dropdown->option_type_id,
        'module_id' => $dropdown->module_id,
        'option_value' => 'Low',
    ]);
    DropdownOptionValue::factory()->create([
        'option_type_id' => $dropdown->option_type_id,
        'module_id' => $dropdown->module_id,
        'option_value' => 'High',
    ]);

    $this->actingAs($user)
        ->get(route('master.dropdown-option-values.index', [
            'sortBy' => 'option_value',
            'sortOrder' => 'asc',
        ]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('optionValues.data.0.option_value', 'High')
            ->where('optionValues.data.1.option_value', 'Low')
            ->where('filters.sortBy', 'option_value')
        );
});

test('invalid sort columns fall back to the default sort', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('master.dropdown-option-values.index', [
            'sortBy' => 'id;drop table users',
            'sortOrder' => 'desc',
        ]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('filters.sortBy', 'created_on')
        );
});

test('the option value list paginates with the selected page size', function () {
    $user = User::factory()->create();
    $dropdown = Dropdown::factory()->create();

    DropdownOptionValue::factory()->count(11)->create([
        'option_type_id' => $dropdown->option_type_id,
        'module_id' => $dropdown->module_id,
    ]);

    $this->actingAs($user)
        ->get(route('master.dropdown-option-values.index', ['pageSize' => 10]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->has('optionValues.data', 10)
            ->where('optionValues.meta.total', 11)
            ->where('optionValues.meta.per_page', 10)
            ->where('optionValues.meta.last_page', 2)
        );
});
