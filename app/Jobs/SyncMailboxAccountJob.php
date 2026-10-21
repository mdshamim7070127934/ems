<?php

namespace App\Jobs;

use App\Models\Mailbox\EmailAccount;
use App\Services\Mailbox\MailboxSyncService;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Throwable;

class SyncMailboxAccountJob implements ShouldBeUnique, ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    /**
     * @var list<int>
     */
    public array $backoff = [30, 60, 120];

    public int $timeout = 180;

    public int $uniqueFor = 600;

    public function __construct(public EmailAccount $account)
    {
        $this->onQueue((string) config('mailbox.queues.sync', 'email-sync'));
        $this->afterCommit();
    }

    public function uniqueId(): string
    {
        return (string) $this->account->id;
    }

    public function handle(MailboxSyncService $sync): void
    {
        $result = $sync->syncAccount($this->account);

        if (! $result['success']) {
            Log::warning('Queued mailbox sync did not succeed.', [
                'email_account_id' => $this->account->id,
                'error' => $result['error'] ?? null,
            ]);

            throw new RuntimeException($result['error'] ?? 'Mailbox sync failed.');
        }
    }

    public function failed(?Throwable $exception): void
    {
        Log::error('Queued mailbox sync failed.', [
            'email_account_id' => $this->account->id,
            'exception' => $exception instanceof Throwable ? $exception::class : null,
        ]);
    }
}
