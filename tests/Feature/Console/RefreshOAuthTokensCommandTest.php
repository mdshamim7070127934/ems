<?php

use App\Models\Master\EmailAccount;
use App\Services\Mail\OAuth\OAuthConnectionService;
use Illuminate\Http\Client\Request;
use Illuminate\Log\Events\MessageLogged;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    config([
        'services.google.client_id' => 'google-client-id',
        'services.google.client_secret' => 'google-client-secret',
        'services.microsoft.client_id' => 'microsoft-client-id',
        'services.microsoft.client_secret' => 'microsoft-client-secret',
        'services.microsoft.tenant' => 'common',
    ]);
});

test('valid oauth tokens are not refreshed', function () {
    $account = EmailAccount::factory()->gmailOAuth()->create([
        'oauth_token_expires_at' => now()->addHour(),
        'oauth_access_token' => 'still-valid',
    ]);

    Http::fake();

    $this->artisan('email:refresh-oauth-tokens')
        ->assertSuccessful()
        ->expectsOutputToContain('Skipped: 1');

    expect($account->refresh()->oauth_access_token)->toBe('still-valid');

    Http::assertNothingSent();
});

test('expired oauth tokens are refreshed', function () {
    $account = EmailAccount::factory()->gmailOAuth()->create([
        'oauth_token_expires_at' => now()->subMinute(),
        'oauth_refresh_token' => 'old-refresh',
        'oauth_access_token' => 'expired-access',
    ]);

    Http::fake([
        'https://oauth2.googleapis.com/token' => Http::response([
            'access_token' => 'new-access',
            'expires_in' => 3600,
        ]),
    ]);

    $this->artisan('email:refresh-oauth-tokens')
        ->assertSuccessful()
        ->expectsOutputToContain('Refreshed: 1');

    $account->refresh();

    expect($account->oauth_access_token)->toBe('new-access')
        ->and($account->oauth_refresh_token)->toBe('old-refresh')
        ->and($account->reauthorization_required)->toBeFalse()
        ->and($account->last_token_refresh_at)->not->toBeNull();
});

test('refresh failure marks the account for reconnection', function () {
    Event::fake([MessageLogged::class]);

    $account = EmailAccount::factory()->gmailOAuth()->create([
        'oauth_token_expires_at' => now()->subMinute(),
        'oauth_refresh_token' => 'revoked-refresh',
    ]);

    Http::fake([
        'https://oauth2.googleapis.com/token' => Http::response([
            'error' => 'invalid_grant',
            'refresh_token' => 'revoked-refresh',
        ], 400),
    ]);

    $this->artisan('email:refresh-oauth-tokens')
        ->assertSuccessful()
        ->expectsOutputToContain('Failed: 1');

    $account->refresh();

    expect($account->reauthorization_required)->toBeTrue()
        ->and($account->oauth_error)->not->toBeNull();

    foreach (Event::dispatched(MessageLogged::class) as $dispatched) {
        $event = $dispatched[0];
        $encoded = json_encode($event->context) ?: '';

        expect($encoded)->not->toContain('revoked-refresh')
            ->and($event->context)->not->toHaveKey('refresh_token')
            ->and($event->context)->not->toHaveKey('access_token');
    }
});

test('one failed account does not stop other accounts from refreshing', function () {
    $failing = EmailAccount::factory()->outlookOAuth()->create([
        'from_email' => 'fail@outlook.com',
        'oauth_token_expires_at' => now()->subMinute(),
        'oauth_refresh_token' => 'bad-refresh',
    ]);
    $working = EmailAccount::factory()->gmailOAuth()->create([
        'from_email' => 'ok@gmail.com',
        'oauth_token_expires_at' => now()->subMinute(),
        'oauth_refresh_token' => 'good-refresh',
    ]);

    Http::preventStrayRequests();

    Http::fake(function (Request $request) {
        if (str_contains($request->url(), 'oauth2.googleapis.com/token')) {
            return Http::response([
                'access_token' => 'gmail-new-access',
                'expires_in' => 3600,
            ]);
        }

        if (str_contains($request->url(), 'login.microsoftonline.com')) {
            return Http::response(['error' => 'invalid_grant'], 400);
        }

        return Http::failedConnection();
    });

    $result = app(OAuthConnectionService::class)->refreshExpiringAccounts();

    expect($result['refreshed'])->toBe(1)
        ->and($result['failed'])->toBe(1)
        ->and($working->refresh()->oauth_access_token)->toBe('gmail-new-access')
        ->and($failing->refresh()->reauthorization_required)->toBeTrue();
});

test('the scheduler command can run repeatedly without changing valid tokens', function () {
    EmailAccount::factory()->gmailOAuth()->create([
        'oauth_token_expires_at' => now()->addHour(),
        'oauth_access_token' => 'still-valid',
    ]);

    Http::fake();

    $this->artisan('email:refresh-oauth-tokens')->assertSuccessful();
    $this->artisan('email:refresh-oauth-tokens')->assertSuccessful();

    Http::assertNothingSent();
});

test('email operations refresh an expired token before continuing', function () {
    $account = EmailAccount::factory()->gmailOAuth()->create([
        'oauth_token_expires_at' => now()->subMinute(),
        'oauth_refresh_token' => 'live-refresh',
        'oauth_access_token' => 'expired-access',
    ]);

    Http::fake([
        'https://oauth2.googleapis.com/token' => Http::response([
            'access_token' => 'on-demand-access',
            'expires_in' => 3600,
        ]),
    ]);

    $refreshed = app(OAuthConnectionService::class)->ensureValidAccessToken($account);

    expect($refreshed->oauth_access_token)->toBe('on-demand-access')
        ->and($refreshed->reauthorization_required)->toBeFalse();
});
