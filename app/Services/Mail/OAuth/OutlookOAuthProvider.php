<?php

namespace App\Services\Mail\OAuth;

use App\Enums\Master\EmailMailer;
use App\Enums\Master\EmailProvider;
use App\Exceptions\EmailProviderException;
use Illuminate\Support\Facades\Log;

class OutlookOAuthProvider extends AbstractOAuthMailProvider
{
    public function provider(): EmailProvider
    {
        return EmailProvider::Outlook;
    }

    public function mailer(): EmailMailer
    {
        return EmailMailer::GraphApi;
    }

    protected function scopeConfigKey(): string
    {
        return 'services.microsoft.scopes';
    }

    public function isConfigured(): bool
    {
        return $this->clientId() !== null && $this->clientSecret() !== null;
    }

    public function authorizationUrl(string $state, string $codeChallenge): string
    {
        $this->assertConfigured();

        return sprintf(
            'https://login.microsoftonline.com/%s/oauth2/v2.0/authorize?',
            rawurlencode($this->tenant()),
        ).http_build_query([
            'client_id' => $this->clientId(),
            'redirect_uri' => $this->redirectUri(),
            'response_type' => 'code',
            'response_mode' => 'query',
            'scope' => implode(' ', $this->scopes($this->scopeConfigKey())),
            'prompt' => 'select_account',
            'state' => $state,
            'code_challenge' => $codeChallenge,
            'code_challenge_method' => 'S256',
        ], '', '&', PHP_QUERY_RFC3986);
    }

    public function exchangeAuthorizationCode(string $code, string $codeVerifier): OAuthTokenSet
    {
        $this->assertConfigured();

        $tokens = $this->requestToken($this->tokenUrl(), [
            'code' => $code,
            'client_id' => (string) $this->clientId(),
            'client_secret' => (string) $this->clientSecret(),
            'redirect_uri' => $this->redirectUri(),
            'grant_type' => 'authorization_code',
            'code_verifier' => $codeVerifier,
            'scope' => implode(' ', $this->scopes($this->scopeConfigKey())),
        ]);

        if ($tokens->refreshToken === null) {
            Log::warning('Microsoft OAuth did not return a refresh token.');

            throw EmailProviderException::tokenExchangeFailed();
        }

        return $tokens;
    }

    public function refreshAccessToken(string $refreshToken): OAuthTokenSet
    {
        $this->assertConfigured();

        return $this->requestToken($this->tokenUrl(), [
            'refresh_token' => $refreshToken,
            'client_id' => (string) $this->clientId(),
            'client_secret' => (string) $this->clientSecret(),
            'grant_type' => 'refresh_token',
            'scope' => implode(' ', $this->scopes($this->scopeConfigKey())),
        ]);
    }

    public function fetchIdentity(string $accessToken): OAuthAccountIdentity
    {
        $response = $this->http()
            ->withToken($accessToken)
            ->get('https://graph.microsoft.com/v1.0/me');

        if (! $response->successful()) {
            Log::warning('Microsoft identity request failed.', [
                'status' => $response->status(),
            ]);

            throw EmailProviderException::identityMissing();
        }

        /** @var array<string, mixed> $payload */
        $payload = $response->json() ?? [];
        $id = $payload['id'] ?? null;
        $email = $payload['mail'] ?? $payload['userPrincipalName'] ?? null;
        $name = $payload['displayName'] ?? $email;

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
    }

    private function assertConfigured(): void
    {
        if (! $this->isConfigured()) {
            throw EmailProviderException::notConfigured($this->provider());
        }
    }

    private function clientId(): ?string
    {
        return $this->configuredValue('services.microsoft.client_id');
    }

    private function clientSecret(): ?string
    {
        return $this->configuredValue('services.microsoft.client_secret');
    }

    private function tenant(): string
    {
        return $this->configuredValue('services.microsoft.tenant') ?? 'common';
    }

    private function redirectUri(): string
    {
        return $this->callbackRedirectUri('services.microsoft.redirect');
    }

    private function tokenUrl(): string
    {
        return sprintf(
            'https://login.microsoftonline.com/%s/oauth2/v2.0/token',
            rawurlencode($this->tenant()),
        );
    }
}
