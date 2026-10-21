<?php

namespace App\Services\Mailbox\OAuth;

use App\Enums\Master\EmailProvider;
use App\Exceptions\EmailProviderException;
use App\Services\Mail\OAuth\OAuthMailProvider;

class MailboxOAuthProviderRegistry
{
    public function __construct(
        private GmailMailboxOAuthProvider $gmail,
        private OutlookMailboxOAuthProvider $outlook,
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
