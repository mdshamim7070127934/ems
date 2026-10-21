<?php

use App\Enums\Master\Status;
use App\Jobs\SendUserWelcomeEmailJob;
use App\Models\Master\Branch;
use App\Models\Master\Role;
use App\Models\User;
use Illuminate\Support\Facades\Queue;
use Inertia\Testing\AssertableInertia as Assert;

test('guests are redirected to the login page', function () {
    $this->get(route('master.users.index'))
        ->assertRedirect(route('login'));
});

test('authenticated users can view the user list', function () {
    $actor = User::factory()->create();
    $role = Role::factory()->create(['display_name' => 'Manager']);
    $branch = Branch::factory()->create(['branch_name' => 'Central']);
    $manager = User::factory()->create(['name' => 'Priya Nair']);

    $this->travelTo('2026-09-20 10:00:00');

    User::factory()->create([
        'first_name' => 'Amit',
        'last_name' => 'Verma',
        'name' => 'Amit Verma',
        'email' => 'amit.verma@example.com',
        'role_id' => $role->id,
        'branch_id' => $branch->id,
        'reporting_person_id' => $manager->id,
        'extra_branch_ids' => [],
        'exclude_branch_ids' => [],
        'status' => Status::Active,
    ]);

    $this->actingAs($actor)
        ->get(route('master.users.index', ['search' => 'Amit']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('master/users/index')
            ->has('users.data', 1)
            ->where('users.data.0.first_name', 'Amit')
            ->where('users.data.0.last_name', 'Verma')
            ->where('users.data.0.email', 'amit.verma@example.com')
            ->where('users.data.0.role_name', 'Manager')
            ->where('users.data.0.branch_name', 'Central')
            ->where('users.data.0.reporting_person_name', 'Priya Nair')
            ->where('users.data.0.status', 'active')
            ->where('users.data.0.status_label', 'Active')
            ->where('users.data.0.created_at', '20-09-2026')
            ->where('filters.search', 'Amit')
        );
});

test('authenticated users can view the create user page', function () {
    $user = User::factory()->create();
    $role = Role::factory()->create(['display_name' => 'Manager']);
    $branch = Branch::factory()->create(['branch_name' => 'Central']);

    $this->actingAs($user)
        ->get(route('master.users.create'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('master/users/form')
            ->where('user', null)
            ->where('roleOptions.0.id', $role->id)
            ->where('roleOptions.0.name', 'Manager')
            ->has('branchOptions')
            ->has('reportingPersonOptions')
        );
});

test('unauthenticated users cannot create a user', function () {
    $this->post(route('master.users.store'), userPayload())
        ->assertRedirect(route('login'));

    $this->assertDatabaseMissing('users', [
        'email' => 'john.doe@example.com',
    ]);
});

test('authenticated users can create a user', function () {
    $actor = User::factory()->create();
    $role = Role::factory()->create();
    $branch = Branch::factory()->create();
    $extra = Branch::factory()->create();
    $excluded = Branch::factory()->create();
    $manager = User::factory()->create();

    $this->actingAs($actor)
        ->from(route('master.users.create'))
        ->post(route('master.users.store'), userPayload([
            'role_id' => $role->id,
            'branch_id' => $branch->id,
            'reporting_person_id' => $manager->id,
            'extra_branch_ids' => [$extra->id],
            'exclude_branch_ids' => [$excluded->id],
        ]))
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('master.users.index'));

    $created = User::query()->where('email', 'john.doe@example.com')->first();

    expect($created)->not->toBeNull()
        ->and($created->first_name)->toBe('John')
        ->and($created->last_name)->toBe('Doe')
        ->and($created->name)->toBe('John Doe')
        ->and($created->username)->toBe('john.doe')
        ->and($created->role_id)->toBe($role->id)
        ->and($created->branch_id)->toBe($branch->id)
        ->and($created->reporting_person_id)->toBe($manager->id)
        ->and($created->extra_branch_ids)->toBe([$extra->id])
        ->and($created->exclude_branch_ids)->toBe([$excluded->id])
        ->and($created->status)->toBe(Status::Active)
        ->and($created->created_by)->toBe($actor->id);
});

test('creating a user queues a welcome email', function () {
    Queue::fake([SendUserWelcomeEmailJob::class]);

    $actor = User::factory()->create();
    $role = Role::factory()->create();
    $branch = Branch::factory()->create();

    $this->actingAs($actor)
        ->post(route('master.users.store'), userPayload([
            'role_id' => $role->id,
            'branch_id' => $branch->id,
        ]))
        ->assertRedirect(route('master.users.index'));

    Queue::assertPushed(
        SendUserWelcomeEmailJob::class,
        fn (SendUserWelcomeEmailJob $job): bool => $job->user->email === 'john.doe@example.com',
    );
});

test('first name is required when creating a user', function () {
    $user = User::factory()->create();
    $payload = userPayload(['first_name' => '']);

    $this->actingAs($user)
        ->from(route('master.users.create'))
        ->post(route('master.users.store'), $payload)
        ->assertSessionHasErrors(['first_name' => 'The first name field is required.'])
        ->assertRedirect(route('master.users.create'));
});

test('duplicate emails are rejected', function () {
    $user = User::factory()->create();
    User::factory()->create(['email' => 'john.doe@example.com']);

    $this->actingAs($user)
        ->from(route('master.users.create'))
        ->post(route('master.users.store'), userPayload())
        ->assertSessionHasErrors('email')
        ->assertRedirect(route('master.users.create'));
});

test('primary branch cannot also be an extra branch', function () {
    $user = User::factory()->create();
    $branch = Branch::factory()->create();

    $this->actingAs($user)
        ->from(route('master.users.create'))
        ->post(route('master.users.store'), userPayload([
            'branch_id' => $branch->id,
            'extra_branch_ids' => [$branch->id],
        ]))
        ->assertSessionHasErrors(['extra_branch_ids' => 'The primary branch cannot also be in extra branches.'])
        ->assertRedirect(route('master.users.create'));
});

test('a branch cannot be in both extra and excluded lists', function () {
    $user = User::factory()->create();
    $primary = Branch::factory()->create();
    $other = Branch::factory()->create();

    $this->actingAs($user)
        ->from(route('master.users.create'))
        ->post(route('master.users.store'), userPayload([
            'branch_id' => $primary->id,
            'extra_branch_ids' => [$other->id],
            'exclude_branch_ids' => [$other->id],
        ]))
        ->assertSessionHasErrors(['exclude_branch_ids' => 'A branch cannot be in both extra and excluded branches.'])
        ->assertRedirect(route('master.users.create'));
});

test('authenticated users can view the user page', function () {
    $actor = User::factory()->create();
    $role = Role::factory()->create(['display_name' => 'Manager']);
    $branch = Branch::factory()->create(['branch_name' => 'Central']);
    $user = User::factory()->create([
        'first_name' => 'Amit',
        'last_name' => 'Verma',
        'name' => 'Amit Verma',
        'role_id' => $role->id,
        'branch_id' => $branch->id,
        'status' => Status::Active,
    ]);

    $this->actingAs($actor)
        ->get(route('master.users.show', $user))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('master/users/show')
            ->where('user.first_name', 'Amit')
            ->where('user.role_name', 'Manager')
            ->where('user.branch_name', 'Central')
        );
});

test('authenticated users can view the edit user page', function () {
    $actor = User::factory()->create();
    $role = Role::factory()->create(['display_name' => 'Manager']);
    $branch = Branch::factory()->create(['branch_name' => 'Central']);
    $user = User::factory()->create([
        'first_name' => 'Amit',
        'last_name' => 'Verma',
        'role_id' => $role->id,
        'branch_id' => $branch->id,
    ]);

    $this->actingAs($actor)
        ->get(route('master.users.edit', $user))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('master/users/form')
            ->where('user.first_name', 'Amit')
            ->where('user.role_id', $role->id)
            ->where('user.branch_id', $branch->id)
            ->has('roleOptions')
            ->has('branchOptions')
        );
});

test('authenticated users can update a user without changing the password', function () {
    $actor = User::factory()->create();
    $role = Role::factory()->create();
    $newRole = Role::factory()->create();
    $branch = Branch::factory()->create();
    $user = User::factory()->create([
        'first_name' => 'Amit',
        'last_name' => 'Verma',
        'email' => 'amit.verma@example.com',
        'password' => 'password',
        'role_id' => $role->id,
        'branch_id' => $branch->id,
        'status' => Status::Active,
    ]);
    $originalPassword = $user->password;

    $this->actingAs($actor)
        ->from(route('master.users.edit', $user))
        ->put(route('master.users.update', $user), userPayload([
            'first_name' => 'Neha',
            'last_name' => 'Shah',
            'email' => 'neha.shah@example.com',
            'password' => '',
            'role_id' => $newRole->id,
            'branch_id' => $branch->id,
            'status' => 'inactive',
        ]))
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('master.users.index'));

    $user->refresh();

    expect($user->first_name)->toBe('Neha')
        ->and($user->last_name)->toBe('Shah')
        ->and($user->name)->toBe('Neha Shah')
        ->and($user->email)->toBe('neha.shah@example.com')
        ->and($user->role_id)->toBe($newRole->id)
        ->and($user->status)->toBe(Status::Inactive)
        ->and($user->password)->toBe($originalPassword);
});

test('a user cannot report to themselves', function () {
    $actor = User::factory()->create();
    $role = Role::factory()->create();
    $branch = Branch::factory()->create();
    $user = User::factory()->create([
        'role_id' => $role->id,
        'branch_id' => $branch->id,
    ]);

    $this->actingAs($actor)
        ->from(route('master.users.edit', $user))
        ->put(route('master.users.update', $user), userPayload([
            'email' => $user->email,
            'password' => '',
            'role_id' => $role->id,
            'branch_id' => $branch->id,
            'reporting_person_id' => $user->id,
        ]))
        ->assertSessionHasErrors(['reporting_person_id' => 'The user cannot report to themselves.'])
        ->assertRedirect(route('master.users.edit', $user));
});

test('authenticated users can delete a user', function () {
    $actor = User::factory()->create();
    $user = User::factory()->create();

    $this->actingAs($actor)
        ->from(route('master.users.index'))
        ->delete(route('master.users.destroy', $user))
        ->assertRedirect(route('master.users.index'));

    $this->assertModelMissing($user);
});

test('a user cannot delete their own account', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->from(route('master.users.index'))
        ->delete(route('master.users.destroy', $user))
        ->assertRedirect(route('master.users.index'));

    $this->assertModelExists($user);
});

test('a user with reportees cannot be deleted', function () {
    $actor = User::factory()->create();
    $manager = User::factory()->create();
    User::factory()->create(['reporting_person_id' => $manager->id]);

    $this->actingAs($actor)
        ->from(route('master.users.index'))
        ->delete(route('master.users.destroy', $manager))
        ->assertRedirect(route('master.users.index'));

    $this->assertModelExists($manager);
});

test('users can be searched by email', function () {
    $actor = User::factory()->create();
    User::factory()->create([
        'first_name' => 'Amit',
        'email' => 'amit.search@example.com',
    ]);
    User::factory()->create([
        'first_name' => 'Neha',
        'email' => 'neha.search@example.com',
    ]);

    $this->actingAs($actor)
        ->get(route('master.users.index', ['search' => 'amit.search']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->has('users.data', 1)
            ->where('users.data.0.email', 'amit.search@example.com')
            ->where('filters.search', 'amit.search')
        );
});

test('the user list paginates with the selected page size', function () {
    $actor = User::factory()->create();
    User::factory()->count(11)->create();

    $this->actingAs($actor)
        ->get(route('master.users.index', ['pageSize' => 10]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->has('users.data', 10)
            ->where('users.meta.total', 12)
            ->where('users.meta.per_page', 10)
        );
});

/**
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function userPayload(array $overrides = []): array
{
    $role = isset($overrides['role_id'])
        ? null
        : Role::factory()->create();
    $branch = isset($overrides['branch_id'])
        ? null
        : Branch::factory()->create();

    return [
        'first_name' => 'John',
        'last_name' => 'Doe',
        'email' => 'john.doe@example.com',
        'password' => 'password',
        'role_id' => $role?->id,
        'branch_id' => $branch?->id,
        'reporting_person_id' => null,
        'extra_branch_ids' => [],
        'exclude_branch_ids' => [],
        'status' => 'active',
        ...$overrides,
    ];
}
