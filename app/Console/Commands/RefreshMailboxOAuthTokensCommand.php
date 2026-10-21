<?php

namespace App\Console\Commands;

use App\Services\Mailbox\MailboxOAuthConnectionService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('email:refresh-mailbox-oauth-tokens')]
#[Description('Refresh expiring Gmail and Outlook mailbox OAuth access tokens.')]
class RefreshMailboxOAuthTokensCommand extends Command
{
    public function handle(MailboxOAuthConnectionService $connections): int
    {
        $result = $connections->refreshExpiringAccounts();

        $this->info(sprintf(
            'Mailbox OAuth token refresh finished. Refreshed: %d. Failed: %d. Skipped: %d.',
            $result['refreshed'],
            $result['failed'],
            $result['skipped'],
        ));

        return self::SUCCESS;
    }
}
