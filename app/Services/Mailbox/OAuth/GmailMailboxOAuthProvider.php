<?php

namespace App\Services\Mailbox\OAuth;

use App\Services\Mail\OAuth\GmailOAuthProvider;

class GmailMailboxOAuthProvider extends GmailOAuthProvider
{
    protected function oauthCallbackRouteName(): string
    {
        return 'mailbox.oauth.callback';
    }

    protected function scopeConfigKey(): string
    {
        return 'services.google.mailbox_scopes';
    }
}
