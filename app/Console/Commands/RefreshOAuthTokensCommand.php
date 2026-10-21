<?php

namespace App\Console\Commands;

use App\Services\Mail\OAuth\OAuthConnectionService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('email:refresh-oauth-tokens')]
#[Description('Refresh expiring Gmail and Outlook OAuth access tokens.')]
class RefreshOAuthTokensCommand extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(OAuthConnectionService $connections): int
    {
        $result = $connections->refreshExpiringAccounts();

        $this->info(sprintf(
            'OAuth token refresh finished. Refreshed: %d. Failed: %d. Skipped: %d.',
            $result['refreshed'],
            $result['failed'],
            $result['skipped'],
        ));

        return self::SUCCESS;
    }
}
