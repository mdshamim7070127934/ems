<?php

namespace App\Services\Mail\OAuth;

use App\Enums\Master\EmailMailer;
use App\Enums\Master\EmailProvider;

interface OAuthMailProvider
{
    public function provider(): EmailProvider;

    public function mailer(): EmailMailer;

    public function isConfigured(): bool;

    public function authorizationUrl(string $state, string $codeChallenge): string;

    public function exchangeAuthorizationCode(string $code, string $codeVerifier): OAuthTokenSet;

    public function refreshAccessToken(string $refreshToken): OAuthTokenSet;

    public function fetchIdentity(string $accessToken): OAuthAccountIdentity;

    public function revokeToken(?string $token): void;
}
