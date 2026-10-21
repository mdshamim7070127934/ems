<?php

namespace App\Services\Mailbox\OAuth;

use App\Services\Mail\OAuth\OutlookOAuthProvider;

class OutlookMailboxOAuthProvider extends OutlookOAuthProvider
{
    protected function oauthCallbackRouteName(): string
    {
        return 'mailbox.oauth.callback';
    }

    protected function scopeConfigKey(): string
    {
        return 'services.microsoft.mailbox_scopes';
    }
}
