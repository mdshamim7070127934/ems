<?php

namespace App\Console\Commands;

use App\Jobs\SyncMailboxAccountJob;
use App\Models\Mailbox\EmailAccount;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('email:sync-mailboxes')]
#[Description('Queue incremental sync for every active mailbox account.')]
class SyncMailboxesCommand extends Command
{
    public function handle(): int
    {
        $queued = 0;

        EmailAccount::query()
            ->active()
            ->orderBy('id')
            ->chunkById(50, function ($accounts) use (&$queued): void {
                foreach ($accounts as $account) {
                    SyncMailboxAccountJob::dispatch($account);
                    $queued++;
                }
            });

        $this->info(sprintf('Queued mailbox sync for %d account(s).', $queued));

        return self::SUCCESS;
    }
}
