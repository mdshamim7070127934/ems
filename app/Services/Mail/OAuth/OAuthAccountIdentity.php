<?php

namespace App\Services\Mail\OAuth;

final readonly class OAuthAccountIdentity
{
    public function __construct(
        public string $providerAccountId,
        public string $email,
        public string $name,
    ) {}
}
