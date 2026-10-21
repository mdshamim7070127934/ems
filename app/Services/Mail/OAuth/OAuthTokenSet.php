<?php

namespace App\Services\Mail\OAuth;

use Illuminate\Support\Carbon;

final readonly class OAuthTokenSet
{
    public function __construct(
        public string $accessToken,
        public ?string $refreshToken,
        public ?Carbon $expiresAt,
    ) {}
}
