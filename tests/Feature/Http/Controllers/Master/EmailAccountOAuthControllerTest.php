<?php

use App\Enums\Master\EmailMailer;
use App\Enums\Master\EmailProvider;
use App\Models\Master\EmailAccount;
use App\Models\User;
use Illuminate\Log\Events\MessageLogged;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    config([
        'services.google.client_id' => 'google-client-id',
        'services.google.client_secret' => 'google-client-secret',
        'services.google.redirect' => 'http://localhost/master/email-accounts/oauth/gmail/callback',
        'services.microsoft.client_id' => 'microsoft-client-id',
        'services.microsoft.client_secret' => 'microsoft-client-secret',
        'services.microsoft.redirect' => 'http://localhost/master/email-accounts/oauth/outlook/callback',
        'services.microsoft.tenant' => 'common',
    ]);
});

test('guests cannot start gmail oauth', function () {
    $this->get(route('master.email-accounts.oauth.redirect', 'gmail'))
        ->assertRedirect(route('login'));
});

test('authenticated users can view the connect account page', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('master.email-accounts.connect'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('master/email-accounts/connect')
        );
});

test('gmail authorization url includes state and offline access', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)
        ->get(route('master.email-accounts.oauth.redirect', 'gmail'));

    $response->assertRedirect();

    $location = $response->headers->get('Location');

    expect($location)->toStartWith('https://accounts.google.com/o/oauth2/v2/auth');

    $query = [];
    parse_str((string) parse_url((string) $location, PHP_URL_QUERY), $query);

    expect($query['client_id'])->toBe('google-client-id')
        ->and($query['access_type'])->toBe('offline')
        ->and($query['response_type'])->toBe('code')
        ->and($query['code_challenge_method'])->toBe('S256')
        ->and($query['state'])->toBeString()->not->toBeEmpty()
        ->and($query['scope'])->toContain('https://www.googleapis.com/auth/gmail.send')
        ->and($query['redirect_uri'])->toBe(route('master.email-accounts.oauth.callback', 'gmail'));
});

test('outlook authorization url includes microsoft login and state', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)
        ->get(route('master.email-accounts.oauth.redirect', 'outlook'));

    $location = $response->headers->get('Location');

    expect($location)->toStartWith('https://login.microsoftonline.com/common/oauth2/v2.0/authorize');

    $query = [];
    parse_str((string) parse_url((string) $location, PHP_URL_QUERY), $query);

    expect($query['client_id'])->toBe('microsoft-client-id')
        ->and($query['state'])->toBeString()->not->toBeEmpty()
        ->and($query['scope'])->toContain('Mail.Send')
        ->and($query['scope'])->toContain('offline_access')
        ->and($query['redirect_uri'])->toBe(route('master.email-accounts.oauth.callback', 'outlook'));
});

test('gmail authorization callback stays on the host the user started from', function () {
    config([
        'services.google.redirect' => 'http://localhost:8000/master/email-accounts/oauth/gmail/callback',
    ]);

    $user = User::factory()->create();

    $response = $this->actingAs($user)
        ->get('http://ems.test/master/email-accounts/oauth/gmail/redirect');

    $response->assertRedirect();

    $query = [];
    parse_str((string) parse_url((string) $response->headers->get('Location'), PHP_URL_QUERY), $query);

    expect($query['redirect_uri'])->toBe('http://ems.test/master/email-accounts/oauth/gmail/callback');
});

test('gmail callback creates an oauth account and hides tokens', function () {
    $user = User::factory()->create();
    $state = startOAuth($user, 'gmail');

    fakeGmailTokenExchange();

    $this->actingAs($user)
        ->get(route('master.email-accounts.oauth.callback', [
            'provider' => 'gmail',
            'state' => $state,
            'code' => 'auth-code',
        ]))
        ->assertRedirect();

    $account = EmailAccount::query()->where('from_email', 'user@gmail.com')->first();

    expect($account)->not->toBeNull()
        ->and($account->provider)->toBe(EmailProvider::Gmail)
        ->and($account->mailer)->toBe(EmailMailer::GmailApi)
        ->and($account->oauth_provider_account_id)->toBe('google-user-1')
        ->and($account->oauth_access_token)->toBe('ya29.access')
        ->and($account->oauth_refresh_token)->toBe('1//refresh')
        ->and($account->reauthorization_required)->toBeFalse()
        ->and($account->password)->toBeNull();

    $this->actingAs($user)
        ->get(route('master.email-accounts.show', $account))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('emailAccount.from_email', 'user@gmail.com')
            ->where('emailAccount.uses_oauth', true)
            ->where('emailAccount.has_oauth_refresh_token', true)
            ->where('emailAccount.connection_status', 'connected')
            ->missing('emailAccount.oauth_access_token')
            ->missing('emailAccount.oauth_refresh_token')
            ->missing('emailAccount.password')
        );
});

test('outlook callback creates a microsoft graph account', function () {
    $user = User::factory()->create();
    $state = startOAuth($user, 'outlook');

    Http::fake([
        'https://login.microsoftonline.com/common/oauth2/v2.0/token' => Http::response([
            'access_token' => 'ms-access',
            'refresh_token' => 'ms-refresh',
            'expires_in' => 3600,
        ]),
        'https://graph.microsoft.com/v1.0/me' => Http::response([
            'id' => 'microsoft-user-1',
            'mail' => 'user@outlook.com',
            'displayName' => 'Outlook User',
        ]),
    ]);

    $this->actingAs($user)
        ->get(route('master.email-accounts.oauth.callback', [
            'provider' => 'outlook',
            'state' => $state,
            'code' => 'auth-code',
        ]))
        ->assertRedirect();

    $account = EmailAccount::query()->where('from_email', 'user@outlook.com')->first();

    expect($account)->not->toBeNull()
        ->and($account->provider)->toBe(EmailProvider::Outlook)
        ->and($account->mailer)->toBe(EmailMailer::GraphApi)
        ->and($account->oauth_refresh_token)->toBe('ms-refresh');
});

test('invalid oauth state is rejected', function () {
    $user = User::factory()->create();

    fakeGmailTokenExchange();

    $this->actingAs($user)
        ->get(route('master.email-accounts.oauth.callback', [
            'provider' => 'gmail',
            'state' => 'not-a-real-state',
            'code' => 'auth-code',
        ]))
        ->assertRedirect(route('master.email-accounts.connect'));

    $this->assertDatabaseCount('mst_email_account', 0);
});

test('oauth callback cannot be reused after the state is consumed', function () {
    $user = User::factory()->create();
    $state = startOAuth($user, 'gmail');

    fakeGmailTokenExchange();

    $this->actingAs($user)
        ->get(route('master.email-accounts.oauth.callback', [
            'provider' => 'gmail',
            'state' => $state,
            'code' => 'auth-code',
        ]))
        ->assertRedirect();

    $this->actingAs($user)
        ->get(route('master.email-accounts.oauth.callback', [
            'provider' => 'gmail',
            'state' => $state,
            'code' => 'auth-code',
        ]))
        ->assertRedirect(route('master.email-accounts.connect'));

    $this->assertDatabaseCount('mst_email_account', 1);
});

test('oauth callback from another user is rejected', function () {
    $owner = User::factory()->create();
    $attacker = User::factory()->create();
    $state = startOAuth($owner, 'gmail');

    fakeGmailTokenExchange();

    $this->actingAs($attacker)
        ->get(route('master.email-accounts.oauth.callback', [
            'provider' => 'gmail',
            'state' => $state,
            'code' => 'auth-code',
        ]))
        ->assertRedirect(route('master.email-accounts.connect'));

    $this->assertDatabaseCount('mst_email_account', 0);
});

test('user cancellation is handled without creating an account', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('master.email-accounts.oauth.callback', [
            'provider' => 'gmail',
            'error' => 'access_denied',
        ]))
        ->assertRedirect(route('master.email-accounts.connect'));

    $this->assertDatabaseCount('mst_email_account', 0);
});

test('token exchange failure does not store an account', function () {
    $user = User::factory()->create();
    $state = startOAuth($user, 'gmail');

    Http::fake([
        'https://oauth2.googleapis.com/token' => Http::response(['error' => 'invalid_grant'], 400),
    ]);

    $this->actingAs($user)
        ->get(route('master.email-accounts.oauth.callback', [
            'provider' => 'gmail',
            'state' => $state,
            'code' => 'bad-code',
        ]))
        ->assertRedirect(route('master.email-accounts.connect'));

    $this->assertDatabaseCount('mst_email_account', 0);
});

test('connecting the same gmail account updates tokens instead of duplicating', function () {
    $user = User::factory()->create();
    $existing = EmailAccount::factory()->gmailOAuth()->create([
        'from_email' => 'user@gmail.com',
        'oauth_provider_account_id' => 'google-user-1',
        'oauth_access_token' => 'old-access',
        'oauth_refresh_token' => 'old-refresh',
    ]);

    $state = startOAuth($user, 'gmail');
    fakeGmailTokenExchange();

    $this->actingAs($user)
        ->get(route('master.email-accounts.oauth.callback', [
            'provider' => 'gmail',
            'state' => $state,
            'code' => 'auth-code',
        ]))
        ->assertRedirect(route('master.email-accounts.show', $existing));

    $this->assertDatabaseCount('mst_email_account', 1);
    expect($existing->refresh()->oauth_access_token)->toBe('ya29.access')
        ->and($existing->oauth_refresh_token)->toBe('1//refresh');
});

test('reconnect cannot attach a different already connected gmail account', function () {
    $user = User::factory()->create();
    $current = EmailAccount::factory()->gmailOAuth()->create([
        'from_email' => 'current@gmail.com',
        'oauth_provider_account_id' => 'google-current',
    ]);
    $other = EmailAccount::factory()->gmailOAuth()->create([
        'from_email' => 'user@gmail.com',
        'oauth_provider_account_id' => 'google-user-1',
    ]);

    $response = $this->actingAs($user)
        ->get(route('master.email-accounts.reconnect', $current));

    $query = [];
    parse_str((string) parse_url((string) $response->headers->get('Location'), PHP_URL_QUERY), $query);

    fakeGmailTokenExchange();

    $this->actingAs($user)
        ->get(route('master.email-accounts.oauth.callback', [
            'provider' => 'gmail',
            'state' => $query['state'],
            'code' => 'auth-code',
        ]))
        ->assertRedirect(route('master.email-accounts.index'));

    expect($current->refresh()->from_email)->toBe('current@gmail.com')
        ->and($other->refresh()->from_email)->toBe('user@gmail.com');
});

test('disconnecting a gmail account clears tokens and requires reconnection', function () {
    $user = User::factory()->create();
    $account = EmailAccount::factory()->gmailOAuth()->create();

    Http::fake([
        'https://oauth2.googleapis.com/revoke' => Http::response('', 200),
    ]);

    $this->actingAs($user)
        ->from(route('master.email-accounts.show', $account))
        ->post(route('master.email-accounts.disconnect', $account))
        ->assertRedirect(route('master.email-accounts.show', $account));

    $account->refresh();

    expect($account->oauth_access_token)->toBeNull()
        ->and($account->oauth_refresh_token)->toBeNull()
        ->and($account->reauthorization_required)->toBeTrue()
        ->and($account->connectionStatus())->toBe('reconnect_required');
});

test('oauth failures do not log tokens', function () {
    Event::fake([MessageLogged::class]);

    $user = User::factory()->create();
    $state = startOAuth($user, 'gmail');

    Http::fake([
        'https://oauth2.googleapis.com/token' => Http::response([
            'error' => 'invalid_grant',
            'access_token' => 'should-not-be-logged',
        ], 400),
    ]);

    $this->actingAs($user)
        ->get(route('master.email-accounts.oauth.callback', [
            'provider' => 'gmail',
            'state' => $state,
            'code' => 'auth-code',
        ]))
        ->assertRedirect(route('master.email-accounts.connect'));

    foreach (Event::dispatched(MessageLogged::class) as $dispatched) {
        $event = $dispatched[0];
        $encoded = json_encode($event->context) ?: '';

        expect($event->message)->not->toContain('should-not-be-logged')
            ->and($encoded)->not->toContain('should-not-be-logged')
            ->and($encoded)->not->toContain('google-client-secret')
            ->and($event->context)->not->toHaveKey('access_token');
    }
});

function startOAuth(User $user, string $provider): string
{
    $response = test()->actingAs($user)
        ->get(route('master.email-accounts.oauth.redirect', $provider));

    $query = [];
    parse_str((string) parse_url((string) $response->headers->get('Location'), PHP_URL_QUERY), $query);

    expect($query['state'] ?? null)->toBeString();

    return $query['state'];
}

function fakeGmailTokenExchange(): void
{
    Http::fake([
        'https://oauth2.googleapis.com/token' => Http::response([
            'access_token' => 'ya29.access',
            'refresh_token' => '1//refresh',
            'expires_in' => 3600,
        ]),
        'https://www.googleapis.com/oauth2/v3/userinfo' => Http::response([
            'sub' => 'google-user-1',
            'email' => 'user@gmail.com',
            'name' => 'Gmail User',
        ]),
    ]);
}
