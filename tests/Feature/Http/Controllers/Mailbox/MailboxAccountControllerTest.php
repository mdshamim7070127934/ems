<?php

use App\Enums\Mailbox\MailboxProvider;
use App\Enums\Master\SmtpEncryption;
use App\Jobs\SyncMailboxAccountJob;
use App\Models\Mailbox\EmailAccount;
use App\Models\User;
use Illuminate\Support\Facades\Queue;
use Inertia\Testing\AssertableInertia as Assert;

test('guests are redirected to the login page', function () {
    $this->get(route('mailbox.connect.smtp'))
        ->assertRedirect(route('login'));
});

test('authenticated users can view the smtp imap connect form', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('mailbox.connect.smtp'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('mailbox/smtp-form'));
});

test('connecting smtp imap creates a user owned mailbox account', function () {
    Queue::fake([SyncMailboxAccountJob::class]);
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('mailbox.connect.smtp.store'), [
            'email' => 'me@example.com',
            'display_name' => 'Me',
            'smtp_host' => 'smtp.example.com',
            'smtp_port' => 587,
            'smtp_encryption' => 'tls',
            'imap_host' => 'imap.example.com',
            'imap_port' => 993,
            'imap_encryption' => 'ssl',
            'smtp_password' => 'secret',
            'imap_password' => 'secret',
        ])
        ->assertRedirect(route('mailbox.index'));

    $account = EmailAccount::query()->where('email', 'me@example.com')->first();

    expect($account)->not->toBeNull()
        ->and($account->user_id)->toBe($user->id)
        ->and($account->provider)->toBe(MailboxProvider::SmtpImap)
        ->and($account->is_default)->toBeTrue()
        ->and($account->smtp_password)->toBe('secret')
        ->and($account->imap_host)->toBe('imap.example.com');

    Queue::assertPushed(SyncMailboxAccountJob::class);
});

test('smtp connect uses smtp host and credentials when imap fields are blank', function () {
    Queue::fake([SyncMailboxAccountJob::class]);
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('mailbox.connect.smtp.store'), [
            'email' => 'me@example.com',
            'display_name' => 'Me',
            'smtp_host' => 'smtp.example.com',
            'smtp_port' => 587,
            'smtp_username' => 'smtp-user',
            'smtp_password' => 'smtp-secret',
            'smtp_encryption' => 'tls',
            'imap_host' => '',
            'imap_port' => 993,
            'imap_username' => '',
            'imap_password' => '',
            'imap_encryption' => 'ssl',
        ])
        ->assertRedirect(route('mailbox.index'));

    $account = EmailAccount::query()->where('email', 'me@example.com')->first();

    expect($account)->not->toBeNull()
        ->and($account->imap_host)->toBe('smtp.example.com')
        ->and($account->imap_username)->toBe('smtp-user')
        ->and($account->imap_password)->toBe('smtp-secret');

    Queue::assertPushed(SyncMailboxAccountJob::class);
});

test('smtp connect stores none when encryption is submitted empty', function () {
    Queue::fake([SyncMailboxAccountJob::class]);
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('mailbox.connect.smtp.store'), [
            'email' => 'me@example.com',
            'smtp_host' => 'smtp.example.com',
            'smtp_port' => 587,
            'smtp_encryption' => '',
            'imap_host' => 'imap.example.com',
            'imap_port' => 993,
            'imap_encryption' => '',
            'smtp_password' => 'secret',
        ])
        ->assertRedirect(route('mailbox.index'));

    $account = EmailAccount::query()->where('email', 'me@example.com')->first();

    expect($account)->not->toBeNull()
        ->and($account->smtp_encryption)->toBe(SmtpEncryption::None)
        ->and($account->imap_encryption)->toBe(SmtpEncryption::None);
});

test('smtp connect rejects a missing email and smtp host', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->from(route('mailbox.connect.smtp'))
        ->post(route('mailbox.connect.smtp.store'), [])
        ->assertRedirect(route('mailbox.connect.smtp'))
        ->assertSessionHasErrors([
            'email' => 'The email field is required.',
            'smtp_host' => 'The smtp host field is required.',
        ]);
});
