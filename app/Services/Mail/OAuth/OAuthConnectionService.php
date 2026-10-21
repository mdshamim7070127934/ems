<?php

namespace App\Services\Mail\OAuth;

use App\Enums\Master\EmailProvider;
use App\Enums\Master\Status;
use App\Exceptions\EmailProviderException;
use App\Models\Master\EmailAccount;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class OAuthConnectionService
{
    private const STATE_TTL_SECONDS = 600;

    private const REFRESH_BUFFER_SECONDS = 300;

    public function __construct(
        private OAuthMailProviderRegistry $providers,
    ) {}

    public function authorizationRedirectUrl(User $user, EmailProvider $provider, ?EmailAccount $account = null): string
    {
        if ($account !== null && $account->provider !== $provider) {
            throw EmailProviderException::unsupported();
        }

        $oauth = $this->providers->for($provider);

        if (! $oauth->isConfigured()) {
            throw EmailProviderException::notConfigured($provider);
        }

        $verifier = $this->codeVerifier();
        $state = Str::random(64);

        Cache::put($this->stateKey($state), [
            'user_id' => $user->id,
            'provider' => $provider->value,
            'account_id' => $account?->id,
            'code_verifier' => $verifier,
        ], self::STATE_TTL_SECONDS);

        return $oauth->authorizationUrl($state, $this->codeChallenge($verifier));
    }

    public function complete(User $user, EmailProvider $provider, ?string $state, ?string $code, ?string $error): EmailAccount
    {
        if ($error === 'access_denied' || $error === 'consent_required') {
            throw EmailProviderException::cancelled();
        }

        if (filled($error)) {
            throw EmailProviderException::authorizationFailed();
        }

        if (! is_string($state) || $state === '' || ! is_string($code) || $code === '') {
            throw EmailProviderException::invalidState();
        }

        $payload = Cache::pull($this->stateKey($state));

        if (! is_array($payload)
            || (int) ($payload['user_id'] ?? 0) !== $user->id
            || ($payload['provider'] ?? null) !== $provider->value
            || ! is_string($payload['code_verifier'] ?? null)
        ) {
            throw EmailProviderException::invalidState();
        }

        $oauth = $this->providers->for($provider);
        $tokens = $oauth->exchangeAuthorizationCode($code, $payload['code_verifier']);
        $identity = $oauth->fetchIdentity($tokens->accessToken);

        $reconnectId = isset($payload['account_id']) ? (int) $payload['account_id'] : null;

        return DB::transaction(function () use ($oauth, $provider, $tokens, $identity, $reconnectId): EmailAccount {
            $existing = $this->findExistingAccount($provider, $identity);

            if ($reconnectId !== null) {
                $account = EmailAccount::query()->lockForUpdate()->find($reconnectId);

                if ($account === null || $account->provider !== $provider) {
                    throw EmailProviderException::invalidState();
                }

                if ($existing !== null && $existing->id !== $account->id) {
                    throw EmailProviderException::alreadyConnected($existing->name);
                }

                $this->applyOAuthCredentials($account, $oauth, $identity, $tokens);

                return $account->refresh();
            }

            if ($existing !== null) {
                $this->applyOAuthCredentials($existing, $oauth, $identity, $tokens);

                return $existing->refresh();
            }

            $account = EmailAccount::query()->create([
                'name' => $this->uniqueAccountName($provider, $identity),
                'provider' => $provider,
                'mailer' => $oauth->mailer(),
                'from_name' => $identity->name,
                'from_email' => $identity->email,
                'oauth_provider_account_id' => $identity->providerAccountId,
                'oauth_access_token' => $tokens->accessToken,
                'oauth_refresh_token' => $tokens->refreshToken,
                'oauth_token_expires_at' => $tokens->expiresAt,
                'last_token_refresh_at' => now(),
                'reauthorization_required' => false,
                'oauth_error' => null,
                'status' => Status::Active,
                'is_default' => false,
            ]);

            return $account;
        });
    }

    public function disconnect(EmailAccount $account): void
    {
        if (! $account->usesOAuth()) {
            throw EmailProviderException::smtpAccount();
        }

        $oauth = $this->providers->for($account->provider);
        $oauth->revokeToken($account->oauth_access_token ?? $account->oauth_refresh_token);

        $account->update([
            'oauth_access_token' => null,
            'oauth_refresh_token' => null,
            'oauth_token_expires_at' => null,
            'last_token_refresh_at' => null,
            'reauthorization_required' => true,
            'oauth_error' => 'Account disconnected.',
        ]);
    }

    public function ensureValidAccessToken(EmailAccount $account): EmailAccount
    {
        if (! $account->usesOAuth()) {
            return $account;
        }

        if ($account->reauthorization_required) {
            throw EmailProviderException::reconnectRequired();
        }

        if ($account->accessTokenIsUsable(self::REFRESH_BUFFER_SECONDS)) {
            return $account;
        }

        $this->refreshAccount($account);

        return $account->refresh();
    }

    /**
     * @return array{refreshed: int, failed: int, skipped: int}
     */
    public function refreshExpiringAccounts(): array
    {
        $result = [
            'refreshed' => 0,
            'failed' => 0,
            'skipped' => 0,
        ];

        EmailAccount::query()
            ->whereIn('provider', [EmailProvider::Gmail, EmailProvider::Outlook])
            ->where('reauthorization_required', false)
            ->whereNotNull('oauth_refresh_token')
            ->orderBy('id')
            ->chunkById(50, function (Collection $accounts) use (&$result): void {
                foreach ($accounts as $account) {
                    try {
                        if ($account->accessTokenIsUsable(self::REFRESH_BUFFER_SECONDS)) {
                            $result['skipped']++;

                            continue;
                        }

                        if ($this->refreshAccount($account)) {
                            $result['refreshed']++;
                        } else {
                            $result['skipped']++;
                        }
                    } catch (\Throwable) {
                        $result['failed']++;
                    }
                }
            });

        return $result;
    }

    public function refreshAccount(EmailAccount $account): bool
    {
        $refreshed = false;
        $lock = Cache::lock('email-account-oauth-refresh:'.$account->id, 20);

        $lock->block(10, function () use ($account, &$refreshed): void {
            $account->refresh();

            if (! $account->usesOAuth() || $account->reauthorization_required) {
                return;
            }

            if ($account->accessTokenIsUsable(self::REFRESH_BUFFER_SECONDS)) {
                return;
            }

            $refreshToken = $account->oauth_refresh_token;

            if (! is_string($refreshToken) || $refreshToken === '') {
                $this->markReauthorizationRequired($account, 'Reconnect required.');

                throw EmailProviderException::reconnectRequired();
            }

            try {
                $tokens = $this->providers->for($account->provider)->refreshAccessToken($refreshToken);
            } catch (EmailProviderException $exception) {
                $this->markReauthorizationRequired($account, 'Token refresh failed. Reconnect the account.');

                throw $exception;
            }

            $account->update([
                'oauth_access_token' => $tokens->accessToken,
                'oauth_refresh_token' => $tokens->refreshToken ?? $refreshToken,
                'oauth_token_expires_at' => $tokens->expiresAt,
                'last_token_refresh_at' => now(),
                'reauthorization_required' => false,
                'oauth_error' => null,
            ]);

            $refreshed = true;
        });

        return $refreshed;
    }

    private function findExistingAccount(EmailProvider $provider, OAuthAccountIdentity $identity): ?EmailAccount
    {
        return EmailAccount::query()
            ->where('provider', $provider)
            ->where(function ($query) use ($identity): void {
                $query->where('from_email', $identity->email)
                    ->orWhere('oauth_provider_account_id', $identity->providerAccountId);
            })
            ->lockForUpdate()
            ->first();
    }

    private function applyOAuthCredentials(
        EmailAccount $account,
        OAuthMailProvider $oauth,
        OAuthAccountIdentity $identity,
        OAuthTokenSet $tokens,
    ): void {
        $account->update([
            'provider' => $oauth->provider(),
            'mailer' => $oauth->mailer(),
            'from_email' => $identity->email,
            'from_name' => $account->from_name !== '' ? $account->from_name : $identity->name,
            'oauth_provider_account_id' => $identity->providerAccountId,
            'oauth_access_token' => $tokens->accessToken,
            'oauth_refresh_token' => $tokens->refreshToken ?? $account->oauth_refresh_token,
            'oauth_token_expires_at' => $tokens->expiresAt,
            'last_token_refresh_at' => now(),
            'reauthorization_required' => false,
            'oauth_error' => null,
            'password' => null,
            'username' => null,
            'smtp_host' => null,
            'smtp_port' => null,
            'smtp_encryption' => null,
        ]);
    }

    private function uniqueAccountName(EmailProvider $provider, OAuthAccountIdentity $identity): string
    {
        $base = sprintf('%s — %s', $provider->label(), $identity->email);

        if (! EmailAccount::query()->where('name', $base)->exists()) {
            return $base;
        }

        return sprintf('%s (%s)', $base, Str::limit($identity->providerAccountId, 12, ''));
    }

    private function markReauthorizationRequired(EmailAccount $account, string $error): void
    {
        $account->update([
            'reauthorization_required' => true,
            'oauth_error' => $error,
        ]);

        Log::warning('Email account requires OAuth reauthorization.', [
            'email_account_id' => $account->id,
            'provider' => $account->provider->value,
        ]);
    }

    private function stateKey(string $state): string
    {
        return 'email-account-oauth-state:'.$state;
    }

    private function codeVerifier(): string
    {
        return rtrim(strtr(base64_encode(random_bytes(32)), '+/', '-_'), '=');
    }

    private function codeChallenge(string $verifier): string
    {
        return rtrim(strtr(base64_encode(hash('sha256', $verifier, true)), '+/', '-_'), '=');
    }
}
