<?php

use App\Enums\Mailbox\MailboxProvider;
use App\Jobs\SyncMailboxAccountJob;
use App\Models\Mailbox\EmailAccount;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;

beforeEach(function () {
    config([
        'services.google.client_id' => 'google-client-id',
        'services.google.client_secret' => 'google-client-secret',
        'services.google.redirect' => 'http://localhost/mailbox/oauth/gmail/callback',
        'services.microsoft.client_id' => 'microsoft-client-id',
        'services.microsoft.client_secret' => 'microsoft-client-secret',
        'services.microsoft.redirect' => 'http://localhost/mailbox/oauth/outlook/callback',
        'services.microsoft.tenant' => 'common',
    ]);
});

test('gmail mailbox authorization url uses mailbox scopes and callback', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)
        ->get(route('mailbox.oauth.redirect', 'gmail'));

    $response->assertRedirect();

    $query = [];
    parse_str((string) parse_url((string) $response->headers->get('Location'), PHP_URL_QUERY), $query);

    expect($query['scope'])->toContain('gmail.modify')
        ->and($query['redirect_uri'])->toBe(route('mailbox.oauth.callback', 'gmail'));
});

test('gmail mailbox callback creates an email_accounts row for the user', function () {
    Queue::fake([SyncMailboxAccountJob::class]);
    $user = User::factory()->create();

    $response = $this->actingAs($user)
        ->get(route('mailbox.oauth.redirect', 'gmail'));

    $query = [];
    parse_str((string) parse_url((string) $response->headers->get('Location'), PHP_URL_QUERY), $query);

    Http::fake([
        'https://oauth2.googleapis.com/token' => Http::response([
            'access_token' => 'ya29.mailbox',
            'refresh_token' => '1//mailbox-refresh',
            'expires_in' => 3600,
        ]),
        'https://www.googleapis.com/oauth2/v3/userinfo' => Http::response([
            'sub' => 'google-mailbox-1',
            'email' => 'mailbox@gmail.com',
            'name' => 'Mailbox User',
        ]),
    ]);

    $this->actingAs($user)
        ->get(route('mailbox.oauth.callback', [
            'provider' => 'gmail',
            'state' => $query['state'],
            'code' => 'auth-code',
        ]))
        ->assertRedirect(route('mailbox.index'));

    $account = EmailAccount::query()->where('email', 'mailbox@gmail.com')->first();

    expect($account)->not->toBeNull()
        ->and($account->user_id)->toBe($user->id)
        ->and($account->provider)->toBe(MailboxProvider::Gmail)
        ->and($account->access_token)->toBe('ya29.mailbox')
        ->and($account->refresh_token)->toBe('1//mailbox-refresh');

    $this->assertDatabaseCount('mst_email_account', 0);
    Queue::assertPushed(SyncMailboxAccountJob::class);
});
