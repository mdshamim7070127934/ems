<?php

namespace App\Jobs;

use App\Models\Mailbox\EmailDraft;
use App\Services\Mailbox\MailboxSendService;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Throwable;

class SendMailboxEmailJob implements ShouldBeUnique, ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    /**
     * @var list<int>
     */
    public array $backoff = [15, 60, 120];

    public int $timeout = 120;

    public int $uniqueFor = 3600;

    public function __construct(public EmailDraft $draft)
    {
        $this->onQueue((string) config('mailbox.queues.send', 'emails'));
        $this->afterCommit();
    }

    public function uniqueId(): string
    {
        return (string) $this->draft->id;
    }

    public function handle(MailboxSendService $send): void
    {
        $result = $send->sendDraft($this->draft);

        if (! $result['success']) {
            Log::warning('Queued mailbox send did not succeed.', [
                'draft_id' => $this->draft->id,
                'error' => $result['error'] ?? null,
            ]);

            throw new RuntimeException($result['error'] ?? 'Could not send the email.');
        }
    }

    public function failed(?Throwable $exception): void
    {
        Log::error('Queued mailbox send failed.', [
            'draft_id' => $this->draft->id,
            'exception' => $exception instanceof Throwable ? $exception::class : null,
        ]);
    }
}
