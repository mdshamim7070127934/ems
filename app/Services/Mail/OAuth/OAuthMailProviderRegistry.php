<?php

namespace App\Services\Mail\OAuth;

use App\Enums\Master\EmailProvider;
use App\Exceptions\EmailProviderException;

class OAuthMailProviderRegistry
{
    public function __construct(
        private GmailOAuthProvider $gmail,
        private OutlookOAuthProvider $outlook,
    ) {}

    public function for(EmailProvider $provider): OAuthMailProvider
    {
        return match ($provider) {
            EmailProvider::Gmail => $this->gmail,
            EmailProvider::Outlook => $this->outlook,
            default => throw EmailProviderException::unsupported(),
        };
    }
}
