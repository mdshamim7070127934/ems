<?php

use App\Enums\Master\EmailMailer;
use App\Enums\Master\EmailProvider;
use App\Enums\Master\Status;
use App\Models\Master\EmailAccount;
use App\Models\Master\EmailType;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

test('guests are redirected to the login page', function () {
    $this->get(route('master.email-accounts.index'))
        ->assertRedirect(route('login'));
});

test('authenticated users can view the email account list', function () {
    $user = User::factory()->create();
    $type = EmailType::factory()->create([
        'code' => 'REGISTRATION',
        'name' => 'Registration',
    ]);

    $this->travelTo('2026-09-20 10:00:00');

    $account = EmailAccount::factory()->create([
        'name' => 'Company Mail',
        'provider' => EmailProvider::Smtp,
        'from_email' => 'noreply@example.com',
        'status' => Status::Active,
        'is_default' => true,
    ]);
    $account->syncEmailTypes([$type->id]);

    $this->actingAs($user)
        ->get(route('master.email-accounts.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('master/email-accounts/index')
            ->has('emailAccounts.data', 1)
            ->where('emailAccounts.data.0.name', 'Company Mail')
            ->where('emailAccounts.data.0.provider', 'smtp')
            ->where('emailAccounts.data.0.from_email', 'noreply@example.com')
            ->where('emailAccounts.data.0.email_type_names', 'Registration')
            ->where('emailAccounts.data.0.is_default', true)
            ->where('emailAccounts.data.0.has_password', true)
            ->missing('emailAccounts.data.0.password')
            ->where('emailAccounts.data.0.created_at', '20-09-2026')
            ->where('filters.sortBy', 'created_at')
            ->where('filters.sortOrder', 'desc')
        );
});

test('authenticated users can view the create email account page', function () {
    $user = User::factory()->create();
    EmailType::factory()->create(['code' => 'REGISTRATION', 'name' => 'Registration']);

    $this->actingAs($user)
        ->get(route('master.email-accounts.create'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('master/email-accounts/form')
            ->where('emailAccount', null)
            ->has('emailTypeOptions', 1)
            ->has('providerOptions')
            ->has('mailerOptions')
        );
});

test('unauthenticated users cannot create an email account', function () {
    $this->post(route('master.email-accounts.store'), accountPayload())
        ->assertRedirect(route('login'));

    $this->assertDatabaseMissing('mst_email_account', [
        'name' => 'Company Mail',
    ]);
});

test('authenticated users can create an email account with multiple email types', function () {
    $user = User::factory()->create();
    $registration = EmailType::factory()->create(['code' => 'REGISTRATION', 'name' => 'Registration']);
    $notification = EmailType::factory()->create(['code' => 'NOTIFICATION', 'name' => 'Notification']);

    $this->actingAs($user)
        ->from(route('master.email-accounts.create'))
        ->post(route('master.email-accounts.store'), accountPayload([
            'email_type_ids' => [$registration->id, $notification->id],
            'is_default' => true,
        ]))
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('master.email-accounts.index'));

    $account = EmailAccount::query()->where('name', 'Company Mail')->first();

    expect($account)->not->toBeNull()
        ->and($account->provider)->toBe(EmailProvider::Smtp)
        ->and($account->mailer)->toBe(EmailMailer::Smtp)
        ->and($account->from_email)->toBe('noreply@example.com')
        ->and($account->password)->toBe('smtp-password')
        ->and($account->is_default)->toBeTrue()
        ->and($account->emailTypes()->pluck('mst_email_type.id')->all())->toEqualCanonicalizing([
            $registration->id,
            $notification->id,
        ]);
});

test('account name is required when creating an email account', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->from(route('master.email-accounts.create'))
        ->post(route('master.email-accounts.store'), accountPayload([
            'name' => '',
        ]))
        ->assertSessionHasErrors(['name' => 'The account name field is required.'])
        ->assertRedirect(route('master.email-accounts.create'));
});

test('inactive email types cannot be assigned to new mappings', function () {
    $user = User::factory()->create();
    $inactive = EmailType::factory()->inactive()->create();

    $this->actingAs($user)
        ->from(route('master.email-accounts.create'))
        ->post(route('master.email-accounts.store'), accountPayload([
            'email_type_ids' => [$inactive->id],
        ]))
        ->assertSessionHasErrors(['email_type_ids' => 'Inactive email types cannot be assigned to new mappings.'])
        ->assertRedirect(route('master.email-accounts.create'));
});

test('an inactive email account cannot be the default sender', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->from(route('master.email-accounts.create'))
        ->post(route('master.email-accounts.store'), accountPayload([
            'status' => 'inactive',
            'is_default' => true,
        ]))
        ->assertSessionHasErrors(['is_default' => 'An inactive email account cannot be the default sender.'])
        ->assertRedirect(route('master.email-accounts.create'));
});

test('setting a default account clears the previous default', function () {
    $user = User::factory()->create();
    $existing = EmailAccount::factory()->default()->create(['name' => 'Old Default']);

    $this->actingAs($user)
        ->from(route('master.email-accounts.create'))
        ->post(route('master.email-accounts.store'), accountPayload([
            'name' => 'New Default',
            'from_email' => 'new@example.com',
            'is_default' => true,
        ]))
        ->assertSessionHasNoErrors();

    expect($existing->refresh()->is_default)->toBeFalse()
        ->and(EmailAccount::query()->where('is_default', true)->count())->toBe(1);
});

test('authenticated users can view the email account page', function () {
    $user = User::factory()->create();
    $type = EmailType::factory()->create(['code' => 'REGISTRATION', 'name' => 'Registration']);
    $account = EmailAccount::factory()->create(['name' => 'Company Mail']);
    $account->syncEmailTypes([$type->id]);

    $this->actingAs($user)
        ->get(route('master.email-accounts.show', $account))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('master/email-accounts/show')
            ->where('emailAccount.name', 'Company Mail')
            ->where('emailAccount.email_type_ids', [$type->id])
            ->missing('emailAccount.password')
        );
});

test('authenticated users can update an email account without changing the password', function () {
    $user = User::factory()->create();
    $type = EmailType::factory()->create();
    $account = EmailAccount::factory()->create([
        'name' => 'Company Mail',
        'password' => 'original-password',
    ]);
    $account->syncEmailTypes([$type->id]);

    $this->actingAs($user)
        ->from(route('master.email-accounts.edit', $account))
        ->put(route('master.email-accounts.update', $account), accountPayload([
            'name' => 'Company SMTP',
            'provider' => 'smtp',
            'from_email' => 'support@example.com',
            'password' => '',
            'email_type_ids' => [$type->id],
        ]))
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('master.email-accounts.index'));

    $account->refresh();

    expect($account->name)->toBe('Company SMTP')
        ->and($account->provider)->toBe(EmailProvider::Smtp)
        ->and($account->password)->toBe('original-password');
});

test('authenticated users can delete an email account and its mappings', function () {
    $user = User::factory()->create();
    $type = EmailType::factory()->create();
    $account = EmailAccount::factory()->create();
    $account->syncEmailTypes([$type->id]);

    $this->actingAs($user)
        ->from(route('master.email-accounts.index'))
        ->delete(route('master.email-accounts.destroy', $account))
        ->assertRedirect(route('master.email-accounts.index'));

    $this->assertModelMissing($account);
    $this->assertDatabaseMissing('mst_email_account_type', [
        'email_account_id' => $account->id,
    ]);
    $this->assertModelExists($type);
});

test('gmail and outlook cannot be created with a password form', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->from(route('master.email-accounts.create'))
        ->post(route('master.email-accounts.store'), accountPayload([
            'provider' => 'gmail',
        ]))
        ->assertSessionHasErrors(['provider'])
        ->assertRedirect(route('master.email-accounts.create'));

    $this->assertDatabaseMissing('mst_email_account', [
        'from_email' => 'noreply@example.com',
        'provider' => 'gmail',
    ]);
});

test('email accounts can be searched by from email', function () {
    $user = User::factory()->create();
    EmailAccount::factory()->create([
        'name' => 'Gmail',
        'from_email' => 'noreply@example.com',
    ]);
    EmailAccount::factory()->create([
        'name' => 'Outlook',
        'from_email' => 'support@company.com',
    ]);

    $this->actingAs($user)
        ->get(route('master.email-accounts.index', ['search' => 'noreply']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->has('emailAccounts.data', 1)
            ->where('emailAccounts.data.0.from_email', 'noreply@example.com')
            ->where('filters.search', 'noreply')
        );
});

/**
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function accountPayload(array $overrides = []): array
{
    $type = isset($overrides['email_type_ids'])
        ? null
        : EmailType::factory()->create();

    return [
        'name' => 'Company Mail',
        'provider' => 'smtp',
        'mailer' => 'smtp',
        'from_name' => 'EMS',
        'from_email' => 'noreply@example.com',
        'username' => 'noreply@example.com',
        'password' => 'smtp-password',
        'smtp_host' => 'smtp.example.com',
        'smtp_port' => 587,
        'smtp_encryption' => 'tls',
        'status' => 'active',
        'is_default' => false,
        'email_type_ids' => $type ? [$type->id] : [],
        ...$overrides,
    ];
}
