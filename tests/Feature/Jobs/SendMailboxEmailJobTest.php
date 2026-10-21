<?php

use App\Jobs\SendMailboxEmailJob;
use App\Models\Mailbox\EmailAccount;
use App\Models\Mailbox\EmailDraft;
use App\Services\Mailbox\MailboxSendService;
use RuntimeException;

test('a queued send fails when the draft has no recipients', function () {
    $account = EmailAccount::factory()->gmail()->create();
    $draft = EmailDraft::factory()->create([
        'account_id' => $account->id,
        'to_emails' => [],
    ]);

    $job = new SendMailboxEmailJob($draft);

    expect(fn () => $job->handle(app(MailboxSendService::class)))
        ->toThrow(RuntimeException::class, 'Add at least one recipient.');
});

test('the send job listens on the mailbox send queue', function () {
    $draft = EmailDraft::factory()->create();

    $job = new SendMailboxEmailJob($draft);

    expect($job->queue)->toBe((string) config('mailbox.queues.send'));
});
