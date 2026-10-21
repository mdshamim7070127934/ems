<?php

use App\Enums\Master\Status;
use App\Models\Master\Branch;
use App\Models\Master\Country;
use App\Models\Master\Group;
use App\Models\Master\Region;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

test('guests are redirected to the login page', function () {
    $this->get(route('master.branches.index'))
        ->assertRedirect(route('login'));
});

test('authenticated users can view the branch list', function () {
    $user = User::factory()->create();
    $country = Country::factory()->active()->create(['name' => 'India']);
    $region = Region::factory()->create([
        'country_id' => $country->id,
        'region_name' => 'West',
    ]);
    $group = Group::factory()->create(['group_name' => 'North Zone']);
    $manager = User::factory()->create(['name' => 'Branch Manager']);

    $this->travelTo('2026-09-20 10:00:00');

    Branch::factory()->create([
        'branch_name' => 'Andheri',
        'branch_code' => 'BR001',
        'country_id' => $country->id,
        'region_id' => $region->id,
        'group_id' => $group->id,
        'branch_manager' => $manager->id,
        'status' => Status::Active,
    ]);

    $this->actingAs($user)
        ->get(route('master.branches.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('master/branches/index')
            ->has('branches.data', 1)
            ->where('branches.data.0.branch_name', 'Andheri')
            ->where('branches.data.0.country_name', 'India')
            ->where('branches.data.0.region_name', 'West')
            ->where('branches.data.0.group_name', 'North Zone')
            ->where('branches.data.0.manager_name', 'Branch Manager')
            ->where('branches.data.0.status', 'active')
            ->where('branches.data.0.created_on', '20-09-2026')
            ->has('countryOptions', 1)
            ->has('regionOptions', 1)
            ->has('groupOptions', 1)
        );
});

test('authenticated users can create a branch', function () {
    $user = User::factory()->create();
    $country = Country::factory()->active()->create();
    $region = Region::factory()->create(['country_id' => $country->id]);
    $group = Group::factory()->create();
    $manager = User::factory()->create();

    $this->actingAs($user)
        ->from(route('master.branches.index'))
        ->post(route('master.branches.store'), [
            'branch_name' => 'Andheri',
            'branch_code' => 'BR001',
            'country_id' => $country->id,
            'region_id' => $region->id,
            'group_id' => $group->id,
            'branch_manager' => $manager->id,
            'owner_id' => $user->id,
            'branch_fbcountry_code' => 'IN',
            'geofencing' => false,
            'status' => 'active',
        ])
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('master.branches.index'));

    $this->assertDatabaseHas('mst_branch', [
        'branch_name' => 'Andheri',
        'branch_code' => 'BR001',
        'country_id' => $country->id,
        'region_id' => $region->id,
        'group_id' => $group->id,
        'created_by' => $user->id,
        'last_modified_by' => $user->id,
    ]);
});

test('a branch region must belong to the selected country', function () {
    $user = User::factory()->create();
    $india = Country::factory()->active()->create();
    $usa = Country::factory()->active()->create();
    $usaRegion = Region::factory()->create(['country_id' => $usa->id]);
    $group = Group::factory()->create();
    $manager = User::factory()->create();

    $this->actingAs($user)
        ->from(route('master.branches.index'))
        ->post(route('master.branches.store'), [
            'branch_name' => 'Andheri',
            'branch_code' => 'BR001',
            'country_id' => $india->id,
            'region_id' => $usaRegion->id,
            'group_id' => $group->id,
            'branch_manager' => $manager->id,
            'branch_fbcountry_code' => 'IN',
            'geofencing' => false,
            'status' => 'active',
        ])
        ->assertSessionHasErrors('region_id')
        ->assertRedirect(route('master.branches.index'));
});

test('duplicate branch codes are rejected', function () {
    $user = User::factory()->create();
    $region = Region::factory()->create();
    $group = Group::factory()->create();
    $manager = User::factory()->create();

    Branch::factory()->create([
        'branch_code' => 'BR001',
        'country_id' => $region->country_id,
        'region_id' => $region->id,
    ]);

    $this->actingAs($user)
        ->from(route('master.branches.index'))
        ->post(route('master.branches.store'), [
            'branch_name' => 'Andheri',
            'branch_code' => 'BR001',
            'country_id' => $region->country_id,
            'region_id' => $region->id,
            'group_id' => $group->id,
            'branch_manager' => $manager->id,
            'branch_fbcountry_code' => 'IN',
            'geofencing' => false,
            'status' => 'active',
        ])
        ->assertSessionHasErrors(['branch_code' => 'The branch code has already been taken.'])
        ->assertRedirect(route('master.branches.index'));
});

test('geofencing range is required when geofencing is enabled', function () {
    $user = User::factory()->create();
    $region = Region::factory()->create();
    $group = Group::factory()->create();
    $manager = User::factory()->create();

    $this->actingAs($user)
        ->from(route('master.branches.index'))
        ->post(route('master.branches.store'), [
            'branch_name' => 'Andheri',
            'branch_code' => 'BR002',
            'country_id' => $region->country_id,
            'region_id' => $region->id,
            'group_id' => $group->id,
            'branch_manager' => $manager->id,
            'branch_fbcountry_code' => 'IN',
            'geofencing' => true,
            'status' => 'active',
        ])
        ->assertSessionHasErrors('geofencing_range')
        ->assertRedirect(route('master.branches.index'));
});

test('authenticated users can update a branch', function () {
    $user = User::factory()->create();
    $region = Region::factory()->create();
    $group = Group::factory()->create();
    $manager = User::factory()->create();
    $branch = Branch::factory()->create([
        'branch_name' => 'Andheri',
        'branch_code' => 'BR001',
        'country_id' => $region->country_id,
        'region_id' => $region->id,
        'group_id' => $group->id,
        'status' => Status::Active,
    ]);

    $this->actingAs($user)
        ->from(route('master.branches.index'))
        ->put(route('master.branches.update', $branch), [
            'branch_name' => 'Bandra',
            'branch_code' => 'BR001',
            'country_id' => $region->country_id,
            'region_id' => $region->id,
            'group_id' => $group->id,
            'branch_manager' => $manager->id,
            'branch_fbcountry_code' => 'IN',
            'geofencing' => false,
            'status' => 'inactive',
        ])
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('master.branches.index'));

    $branch->refresh();

    expect($branch->branch_name)->toBe('Bandra')
        ->and($branch->status)->toBe(Status::Inactive)
        ->and($branch->last_modified_by)->toBe($user->id);
});

test('authenticated users can delete a branch', function () {
    $user = User::factory()->create();
    $branch = Branch::factory()->create();

    $this->actingAs($user)
        ->from(route('master.branches.index'))
        ->delete(route('master.branches.destroy', $branch))
        ->assertRedirect(route('master.branches.index'));

    $this->assertModelMissing($branch);
});

test('branches can be searched by name', function () {
    $user = User::factory()->create();
    $region = Region::factory()->create();

    Branch::factory()->create([
        'branch_name' => 'Andheri',
        'country_id' => $region->country_id,
        'region_id' => $region->id,
    ]);
    Branch::factory()->create([
        'branch_name' => 'Bandra',
        'country_id' => $region->country_id,
        'region_id' => $region->id,
    ]);

    $this->actingAs($user)
        ->get(route('master.branches.index', ['search' => 'Andh']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->has('branches.data', 1)
            ->where('branches.data.0.branch_name', 'Andheri')
        );
});
