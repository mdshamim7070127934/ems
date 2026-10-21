<?php

use App\Enums\Master\Status;
use App\Models\Master\EmailAccount;
use App\Models\Master\EmailType;
use App\Models\User;
use Database\Seeders\EmailTypeSeeder;
use Inertia\Testing\AssertableInertia as Assert;

test('guests are redirected to the login page', function () {
    $this->get(route('master.email-types.index'))
        ->assertRedirect(route('login'));
});

test('authenticated users can view the email type list', function () {
    $user = User::factory()->create();

    $this->travelTo('2026-09-20 10:00:00');

    EmailType::factory()->create([
        'code' => 'REGISTRATION',
        'name' => 'Registration',
        'sort_order' => 1,
        'status' => Status::Active,
    ]);

    $this->actingAs($user)
        ->get(route('master.email-types.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('master/email-types/index')
            ->has('emailTypes.data', 1)
            ->where('emailTypes.data.0.code', 'REGISTRATION')
            ->where('emailTypes.data.0.name', 'Registration')
            ->where('emailTypes.data.0.status', 'active')
            ->where('emailTypes.data.0.status_label', 'Active')
            ->where('emailTypes.data.0.created_at', '20-09-2026')
            ->where('filters.sortBy', 'sort_order')
            ->where('filters.sortOrder', 'desc')
            ->where('filters.pageSize', 10)
        );
});

test('unauthenticated users cannot create an email type', function () {
    $this->post(route('master.email-types.store'), [
        'code' => 'REGISTRATION',
        'name' => 'Registration',
        'sort_order' => 1,
        'status' => 'active',
    ])->assertRedirect(route('login'));

    $this->assertDatabaseMissing('mst_email_type', [
        'code' => 'REGISTRATION',
    ]);
});

test('authenticated users can create an email type', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->from(route('master.email-types.index'))
        ->post(route('master.email-types.store'), [
            'code' => 'registration',
            'name' => 'Registration',
            'description' => 'Sent after a user registers.',
            'sort_order' => 1,
            'status' => 'active',
        ])
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('master.email-types.index'));

    $this->assertDatabaseHas('mst_email_type', [
        'code' => 'REGISTRATION',
        'name' => 'Registration',
        'description' => 'Sent after a user registers.',
        'sort_order' => 1,
        'status' => 'active',
    ]);
});

test('email type name is required when creating an email type', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->from(route('master.email-types.index'))
        ->post(route('master.email-types.store'), [
            'code' => 'REGISTRATION',
            'name' => '',
            'sort_order' => 1,
            'status' => 'active',
        ])
        ->assertSessionHasErrors(['name' => 'The name field is required.'])
        ->assertRedirect(route('master.email-types.index'));
});

test('duplicate email type codes are rejected', function () {
    $user = User::factory()->create();
    EmailType::factory()->create(['code' => 'REGISTRATION']);

    $this->actingAs($user)
        ->from(route('master.email-types.index'))
        ->post(route('master.email-types.store'), [
            'code' => 'REGISTRATION',
            'name' => 'Sign up',
            'sort_order' => 2,
            'status' => 'active',
        ])
        ->assertSessionHasErrors('code')
        ->assertRedirect(route('master.email-types.index'));
});

test('authenticated users can update an email type', function () {
    $user = User::factory()->create();
    $emailType = EmailType::factory()->create([
        'code' => 'REGISTRATION',
        'name' => 'Registration',
        'status' => Status::Active,
    ]);

    $this->actingAs($user)
        ->from(route('master.email-types.index'))
        ->put(route('master.email-types.update', $emailType), [
            'code' => 'SIGNUP',
            'name' => 'Sign up',
            'description' => 'Updated description',
            'sort_order' => 3,
            'status' => 'inactive',
        ])
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('master.email-types.index'));

    $emailType->refresh();

    expect($emailType->code)->toBe('SIGNUP')
        ->and($emailType->name)->toBe('Sign up')
        ->and($emailType->status)->toBe(Status::Inactive)
        ->and($emailType->sort_order)->toBe(3);
});

test('authenticated users can delete an email type', function () {
    $user = User::factory()->create();
    $emailType = EmailType::factory()->create();

    $this->actingAs($user)
        ->from(route('master.email-types.index'))
        ->delete(route('master.email-types.destroy', $emailType))
        ->assertRedirect(route('master.email-types.index'));

    $this->assertModelMissing($emailType);
});

test('an email type assigned to an account cannot be deleted', function () {
    $user = User::factory()->create();
    $emailType = EmailType::factory()->create();
    $account = EmailAccount::factory()->create();
    $account->syncEmailTypes([$emailType->id]);

    $this->actingAs($user)
        ->from(route('master.email-types.index'))
        ->delete(route('master.email-types.destroy', $emailType))
        ->assertRedirect(route('master.email-types.index'));

    $this->assertModelExists($emailType);
});

test('email types can be searched by code', function () {
    $user = User::factory()->create();
    EmailType::factory()->create([
        'code' => 'REGISTRATION',
        'name' => 'Registration',
    ]);
    EmailType::factory()->create([
        'code' => 'NOTIFICATION',
        'name' => 'Notification',
    ]);

    $this->actingAs($user)
        ->get(route('master.email-types.index', ['search' => 'REGIST']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->has('emailTypes.data', 1)
            ->where('emailTypes.data.0.code', 'REGISTRATION')
            ->where('filters.search', 'REGIST')
        );
});

test('the email type list paginates with the selected page size', function () {
    $user = User::factory()->create();
    EmailType::factory()->count(11)->create();

    $this->actingAs($user)
        ->get(route('master.email-types.index', ['pageSize' => 10]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->has('emailTypes.data', 10)
            ->where('emailTypes.meta.total', 11)
            ->where('emailTypes.meta.per_page', 10)
        );
});

test('the email type seeder is idempotent', function () {
    $this->seed(EmailTypeSeeder::class);
    $this->seed(EmailTypeSeeder::class);

    expect(EmailType::query()->count())->toBe(5)
        ->and(EmailType::query()->where('code', 'REGISTRATION')->count())->toBe(1);
});
