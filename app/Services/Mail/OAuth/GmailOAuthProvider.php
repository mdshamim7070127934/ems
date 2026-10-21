<?php

namespace App\Services\Mail\OAuth;

use App\Enums\Master\EmailMailer;
use App\Enums\Master\EmailProvider;
use App\Exceptions\EmailProviderException;
use Illuminate\Support\Facades\Log;

class GmailOAuthProvider extends AbstractOAuthMailProvider
{
    public function provider(): EmailProvider
    {
        return EmailProvider::Gmail;
    }

    public function mailer(): EmailMailer
    {
        return EmailMailer::GmailApi;
    }

    protected function scopeConfigKey(): string
    {
        return 'services.google.scopes';
    }

    public function isConfigured(): bool
    {
        return $this->clientId() !== null && $this->clientSecret() !== null;
    }

    public function authorizationUrl(string $state, string $codeChallenge): string
    {
        $this->assertConfigured();

        return 'https://accounts.google.com/o/oauth2/v2/auth?'.http_build_query([
            'client_id' => $this->clientId(),
            'redirect_uri' => $this->redirectUri(),
            'response_type' => 'code',
            'scope' => implode(' ', $this->scopes($this->scopeConfigKey())),
            'access_type' => 'offline',
            'prompt' => 'consent',
            'include_granted_scopes' => 'true',
            'state' => $state,
            'code_challenge' => $codeChallenge,
            'code_challenge_method' => 'S256',
        ], '', '&', PHP_QUERY_RFC3986);
    }

    public function exchangeAuthorizationCode(string $code, string $codeVerifier): OAuthTokenSet
    {
        $this->assertConfigured();

        $tokens = $this->requestToken('https://oauth2.googleapis.com/token', [
            'code' => $code,
            'client_id' => (string) $this->clientId(),
            'client_secret' => (string) $this->clientSecret(),
            'redirect_uri' => $this->redirectUri(),
            'grant_type' => 'authorization_code',
            'code_verifier' => $codeVerifier,
        ]);

        if ($tokens->refreshToken === null) {
            Log::warning('Gmail OAuth did not return a refresh token.');

            throw EmailProviderException::tokenExchangeFailed();
        }

        return $tokens;
    }

    public function refreshAccessToken(string $refreshToken): OAuthTokenSet
    {
        $this->assertConfigured();

        return $this->requestToken('https://oauth2.googleapis.com/token', [
            'refresh_token' => $refreshToken,
            'client_id' => (string) $this->clientId(),
            'client_secret' => (string) $this->clientSecret(),
            'grant_type' => 'refresh_token',
        ]);
    }

    public function fetchIdentity(string $accessToken): OAuthAccountIdentity
    {
        $response = $this->http()
            ->withToken($accessToken)
            ->get('https://www.googleapis.com/oauth2/v3/userinfo');

        if (! $response->successful()) {
            Log::warning('Gmail identity request failed.', [
                'status' => $response->status(),
            ]);

            throw EmailProviderException::identityMissing();
        }

        /** @var array<string, mixed> $payload */
        $payload = $response->json() ?? [];
        $email = $payload['email'] ?? null;
        $id = $payload['sub'] ?? $payload['id'] ?? null;
        $name = $payload['name'] ?? $email;

        if (! is_string($email) || $email === '' || ! is_string($id) || $id === '') {
            throw EmailProviderException::identityMissing();
        }

        return new OAuthAccountIdentity(
            providerAccountId: $id,
            email: strtolower($email),
            name: is_string($name) && $name !== '' ? $name : $email,
        );
    }

    public function revokeToken(?string $token): void
    {
        if ($token === null || $token === '') {
            return;
        }

        $response = $this->http()
            ->asForm()
            ->post('https://oauth2.googleapis.com/revoke', [
                'token' => $token,
            ]);

        if (! $response->successful()) {
            Log::warning('Gmail token revoke failed.', [
                'status' => $response->status(),
            ]);
        }
    }

    private function assertConfigured(): void
    {
        if (! $this->isConfigured()) {
            throw EmailProviderException::notConfigured($this->provider());
        }
    }

    private function clientId(): ?string
    {
        return $this->configuredValue('services.google.client_id');
    }

    private function clientSecret(): ?string
    {
        return $this->configuredValue('services.google.client_secret');
    }

    private function redirectUri(): string
    {
        return $this->callbackRedirectUri('services.google.redirect');
    }
}
