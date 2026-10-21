<?php

namespace App\Services\Mailbox;

use App\Enums\Mailbox\MailboxProvider;
use App\Enums\Master\EmailProvider;
use App\Exceptions\EmailProviderException;
use App\Models\Mailbox\EmailAccount;
use App\Models\User;
use App\Services\Mail\OAuth\OAuthAccountIdentity;
use App\Services\Mail\OAuth\OAuthMailProvider;
use App\Services\Mail\OAuth\OAuthTokenSet;
use App\Services\Mailbox\OAuth\MailboxOAuthProviderRegistry;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class MailboxOAuthConnectionService
{
    private const STATE_TTL_SECONDS = 600;

    private const REFRESH_BUFFER_SECONDS = 300;

    private const REDIRECT_ROUTE = 'mailbox.connect';

    public function __construct(
        private MailboxOAuthProviderRegistry $providers,
    ) {}

    public function authorizationRedirectUrl(User $user, EmailProvider $provider, ?EmailAccount $account = null): string
    {
        try {
            if ($account !== null && $account->provider->value !== $provider->value) {
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
        } catch (EmailProviderException $exception) {
            throw $this->mailboxException($exception);
        }
    }

    public function complete(User $user, EmailProvider $provider, ?string $state, ?string $code, ?string $error): EmailAccount
    {
        try {
            return $this->completeConnection($user, $provider, $state, $code, $error);
        } catch (EmailProviderException $exception) {
            throw $this->mailboxException($exception);
        }
    }

    public function disconnect(EmailAccount $account): void
    {
        if (! $account->usesOAuth()) {
            throw new EmailProviderException('SMTP / IMAP accounts do not use OAuth.', self::REDIRECT_ROUTE);
        }

        $provider = EmailProvider::tryFrom($account->provider->value);

        if ($provider === null || ! $provider->usesOAuth()) {
            throw new EmailProviderException('SMTP / IMAP accounts do not use OAuth.', self::REDIRECT_ROUTE);
        }

        $oauth = $this->providers->for($provider);
        $oauth->revokeToken($account->access_token ?? $account->refresh_token);

        $account->update([
            'access_token' => null,
            'refresh_token' => null,
            'token_expiry' => null,
            'is_active' => false,
        ]);
    }

    public function ensureValidAccessToken(EmailAccount $account): EmailAccount
    {
        if (! $account->usesOAuth()) {
            return $account;
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
            ->whereIn('provider', [MailboxProvider::Gmail, MailboxProvider::Outlook])
            ->where('is_active', true)
            ->whereNotNull('refresh_token')
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
        $lock = Cache::lock('mailbox-oauth-refresh:'.$account->id, 20);

        $lock->block(10, function () use ($account, &$refreshed): void {
            $account->refresh();

            if (! $account->usesOAuth()) {
                return;
            }

            if ($account->accessTokenIsUsable(self::REFRESH_BUFFER_SECONDS)) {
                return;
            }

            $refreshToken = $account->refresh_token;

            if (! is_string($refreshToken) || $refreshToken === '') {
                $account->update(['is_active' => false]);

                throw EmailProviderException::reconnectRequired();
            }

            $provider = EmailProvider::from($account->provider->value);

            try {
                $tokens = $this->providers->for($provider)->refreshAccessToken($refreshToken);
            } catch (EmailProviderException $exception) {
                $account->update(['is_active' => false]);

                Log::warning('Mailbox account requires OAuth reauthorization.', [
                    'email_account_id' => $account->id,
                    'provider' => $account->provider->value,
                ]);

                throw $exception;
            }

            $account->update([
                'access_token' => $tokens->accessToken,
                'refresh_token' => $tokens->refreshToken ?? $refreshToken,
                'token_expiry' => $tokens->expiresAt,
                'is_active' => true,
            ]);

            $refreshed = true;
        });

        return $refreshed;
    }

    private function completeConnection(
        User $user,
        EmailProvider $provider,
        ?string $state,
        ?string $code,
        ?string $error,
    ): EmailAccount {
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

        return DB::transaction(function () use ($user, $oauth, $provider, $tokens, $identity, $reconnectId): EmailAccount {
            $existing = $this->findExistingAccount($user, $provider, $identity);

            if ($reconnectId !== null) {
                $account = EmailAccount::query()->lockForUpdate()->find($reconnectId);

                if ($account === null || $account->user_id !== $user->id || $account->provider->value !== $provider->value) {
                    throw EmailProviderException::invalidState();
                }

                if ($existing !== null && $existing->id !== $account->id) {
                    throw EmailProviderException::alreadyConnected($existing->email);
                }

                $this->applyOAuthCredentials($account, $oauth, $identity, $tokens);

                return $account->refresh();
            }

            if ($existing !== null) {
                $this->applyOAuthCredentials($existing, $oauth, $identity, $tokens);

                return $existing->refresh();
            }

            $account = EmailAccount::query()->create([
                'user_id' => $user->id,
                'provider' => MailboxProvider::from($provider->value),
                'email' => $identity->email,
                'display_name' => $identity->name,
                'access_token' => $tokens->accessToken,
                'refresh_token' => $tokens->refreshToken,
                'token_expiry' => $tokens->expiresAt,
                'is_active' => true,
                'is_default' => ! EmailAccount::query()->ownedBy($user)->active()->exists(),
            ]);

            return $account;
        });
    }

    private function findExistingAccount(User $user, EmailProvider $provider, OAuthAccountIdentity $identity): ?EmailAccount
    {
        return EmailAccount::query()
            ->ownedBy($user)
            ->where('provider', $provider->value)
            ->where('email', $identity->email)
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
            'provider' => MailboxProvider::from($oauth->provider()->value),
            'email' => $identity->email,
            'display_name' => $account->display_name ?: $identity->name,
            'access_token' => $tokens->accessToken,
            'refresh_token' => $tokens->refreshToken ?? $account->refresh_token,
            'token_expiry' => $tokens->expiresAt,
            'is_active' => true,
            'smtp_host' => null,
            'smtp_port' => null,
            'smtp_username' => null,
            'smtp_password' => null,
            'smtp_encryption' => null,
            'imap_host' => null,
            'imap_port' => null,
            'imap_username' => null,
            'imap_password' => null,
            'imap_encryption' => null,
        ]);
    }

    private function mailboxException(EmailProviderException $exception): EmailProviderException
    {
        return new EmailProviderException($exception->getMessage(), self::REDIRECT_ROUTE);
    }

    private function stateKey(string $state): string
    {
        return 'mailbox-oauth-state:'.$state;
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
