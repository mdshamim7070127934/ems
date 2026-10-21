<?php

namespace App\Services\Mail\OAuth;

use App\Exceptions\EmailProviderException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

abstract class AbstractOAuthMailProvider implements OAuthMailProvider
{
    protected function oauthCallbackRouteName(): string
    {
        return 'master.email-accounts.oauth.callback';
    }

    protected function scopeConfigKey(): string
    {
        return 'services.google.scopes';
    }

    protected function http(): PendingRequest
    {
        return Http::connectTimeout(5)
            ->timeout(15)
            ->acceptJson();
    }

    /**
     * @param  array<string, string>  $form
     */
    protected function requestToken(string $url, array $form): OAuthTokenSet
    {
        $response = $this->http()->asForm()->post($url, $form);

        if (! $response->successful()) {
            Log::warning('OAuth token request failed.', [
                'provider' => $this->provider()->value,
                'status' => $response->status(),
            ]);

            throw EmailProviderException::tokenExchangeFailed();
        }

        /** @var array<string, mixed> $payload */
        $payload = $response->json() ?? [];
        $accessToken = $payload['access_token'] ?? null;

        if (! is_string($accessToken) || $accessToken === '') {
            Log::warning('OAuth token response was missing an access token.', [
                'provider' => $this->provider()->value,
            ]);

            throw EmailProviderException::tokenExchangeFailed();
        }

        $refreshToken = $payload['refresh_token'] ?? null;
        $expiresIn = $payload['expires_in'] ?? null;

        return new OAuthTokenSet(
            accessToken: $accessToken,
            refreshToken: is_string($refreshToken) && $refreshToken !== '' ? $refreshToken : null,
            expiresAt: is_numeric($expiresIn)
                ? Carbon::now()->addSeconds((int) $expiresIn)
                : null,
        );
    }

    protected function configuredValue(string $key): ?string
    {
        $value = config($key);

        return is_string($value) && $value !== '' ? $value : null;
    }

    /**
     * Google and Microsoft must return the user to the same origin they started from.
     */
    protected function callbackRedirectUri(?string $configuredKey = null): string
    {
        $path = route(
            $this->oauthCallbackRouteName(),
            ['provider' => $this->provider()->value],
            false,
        );

        $request = request();

        if ($request->getHost() !== '') {
            return $request->root().$path;
        }

        return $this->configuredValue((string) $configuredKey) ?? url($path);
    }

    /**
     * @return list<string>
     */
    protected function scopes(string $key): array
    {
        $scopes = config($key, []);

        if (! is_array($scopes)) {
            return [];
        }

        return array_values(array_filter(
            $scopes,
            fn (mixed $scope): bool => is_string($scope) && $scope !== '',
        ));
    }
}
