<?php

use App\Models\Mailbox\Email;
use App\Models\Mailbox\EmailAccount;
use App\Models\Mailbox\EmailDraft;
use App\Models\Mailbox\EmailThread;
use App\Services\Mailbox\MailboxSendService;
use App\Services\Mailbox\MailboxSyncService;
use GuzzleHttp\Promise\PromiseInterface;
use Illuminate\Http\Client\Request;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

test('gmail send then sync keeps one email on the provider thread', function () {
    Http::preventStrayRequests();
    Http::fake(fn (Request $request) => gmailSendThenSyncResponse($request));

    $account = EmailAccount::factory()->gmail()->create([
        'email' => 'me@example.com',
        'token_expiry' => now()->addHour(),
    ]);
    $draft = EmailDraft::factory()->create([
        'account_id' => $account->id,
        'to_emails' => ['to@example.com'],
        'subject' => 'Hello',
        'body_html' => '<p>Hi</p>',
    ]);

    $result = app(MailboxSendService::class)->sendDraft($draft);
    $sync = app(MailboxSyncService::class)->syncAccount($account);

    expect($result['success'])->toBeTrue()
        ->and($sync['success'])->toBeTrue()
        ->and($sync['synced'])->toBe(0);

    $email = Email::query()->where('account_id', $account->id)->first();

    expect(Email::query()->where('account_id', $account->id)->count())->toBe(1)
        ->and(EmailThread::query()->where('account_id', $account->id)->count())->toBe(1)
        ->and($email?->message_id)->toBe('sent-message@example.com')
        ->and($email?->provider_message_id)->toBe('gmail-msg-1')
        ->and($email?->thread?->thread_id)->toBe('gmail-thread-1');

    Http::assertSent(function (Request $request): bool {
        if ($request->method() !== 'POST' || ! str_contains($request->url(), '/users/me/messages/send')) {
            return false;
        }

        return ! array_key_exists('threadId', $request->data());
    });
});

test('a gmail reply stays on the original conversation thread', function () {
    Http::preventStrayRequests();
    Http::fake(fn (Request $request) => gmailSendThenSyncResponse($request, 'Re: Hello'));

    $account = EmailAccount::factory()->gmail()->create([
        'email' => 'me@example.com',
        'token_expiry' => now()->addHour(),
    ]);
    $thread = EmailThread::factory()->create([
        'account_id' => $account->id,
        'thread_id' => 'gmail-thread-1',
        'subject' => 'Hello',
    ]);
    Email::factory()->create([
        'account_id' => $account->id,
        'thread_id' => $thread->id,
        'message_id' => 'parent-message@example.com',
        'from_email' => 'sender@example.com',
        'subject' => 'Hello',
        'is_draft' => false,
    ]);
    $draft = EmailDraft::factory()->create([
        'account_id' => $account->id,
        'thread_id' => $thread->id,
        'in_reply_to' => 'parent-message@example.com',
        'to_emails' => ['sender@example.com'],
        'subject' => 'Re: Hello',
        'body_html' => '<p>Reply</p>',
    ]);

    $result = app(MailboxSendService::class)->sendDraft($draft);
    $sync = app(MailboxSyncService::class)->syncAccount($account);

    expect($result['success'])->toBeTrue()
        ->and($sync['success'])->toBeTrue()
        ->and(EmailThread::query()->where('account_id', $account->id)->count())->toBe(1)
        ->and(Email::query()->where('account_id', $account->id)->count())->toBe(2)
        ->and(Email::query()->where('account_id', $account->id)->pluck('thread_id')->unique()->all())
        ->toBe([$thread->id]);

    $reply = Email::query()
        ->where('account_id', $account->id)
        ->where('in_reply_to', 'parent-message@example.com')
        ->first();

    expect($reply?->message_id)->toBe('sent-message@example.com')
        ->and($reply?->provider_message_id)->toBe('gmail-msg-1');

    Http::assertSent(function (Request $request): bool {
        return $request->method() === 'POST'
            && str_contains($request->url(), '/users/me/messages/send')
            && ($request->data()['threadId'] ?? null) === 'gmail-thread-1';
    });
});

test('sync collapses a local sent copy onto the provider message', function () {
    $this->freezeTime();
    Http::preventStrayRequests();
    Http::fake(fn (Request $request) => gmailSendThenSyncResponse($request));

    $account = EmailAccount::factory()->gmail()->create([
        'email' => 'me@example.com',
        'token_expiry' => now()->addHour(),
    ]);
    $localThread = EmailThread::factory()->create([
        'account_id' => $account->id,
        'thread_id' => 'local-old-sent',
        'subject' => 'Hello',
    ]);
    $placeholder = Email::factory()->create([
        'account_id' => $account->id,
        'thread_id' => $localThread->id,
        'message_id' => 'aaaaaaaa-bbbb-cccc-dddd-eeeeeeeeeeee',
        'provider_message_id' => null,
        'from_email' => $account->email,
        'subject' => 'Hello',
        'internal_date' => now()->subHours(5),
        'is_draft' => false,
    ]);

    $sync = app(MailboxSyncService::class)->syncAccount($account);

    expect($sync['success'])->toBeTrue()
        ->and(Email::query()->where('account_id', $account->id)->count())->toBe(1);

    $placeholder->refresh();

    expect($placeholder->message_id)->toBe('sent-message@example.com')
        ->and($placeholder->provider_message_id)->toBe('gmail-msg-1')
        ->and($placeholder->thread?->thread_id)->toBe('gmail-thread-1')
        ->and(EmailThread::query()->whereKey($localThread->id)->exists())->toBeFalse();
});

test('gmail send keeps a rfc message id when the provider message headers are missing', function () {
    Http::preventStrayRequests();
    Http::fake(fn (Request $request) => gmailSendThenSyncResponse($request, 'Hello', false));

    $account = EmailAccount::factory()->gmail()->create([
        'email' => 'me@example.com',
        'token_expiry' => now()->addHour(),
    ]);
    $draft = EmailDraft::factory()->create([
        'account_id' => $account->id,
        'to_emails' => ['to@example.com'],
        'subject' => 'Hello',
        'body_html' => '<p>Hi</p>',
    ]);

    $result = app(MailboxSendService::class)->sendDraft($draft);

    $sent = Email::query()->where('account_id', $account->id)->first();

    expect($result['success'])->toBeTrue()
        ->and($sent?->message_id)->toEndWith('@example.com')
        ->and($sent?->provider_message_id)->toBe('gmail-msg-1');

    $sync = app(MailboxSyncService::class)->syncAccount($account);

    expect($sync['success'])->toBeTrue()
        ->and(Email::query()->where('account_id', $account->id)->count())->toBe(1);

    $email = Email::query()->where('account_id', $account->id)->first();

    expect($email?->message_id)->toBe('sent-message@example.com')
        ->and($email?->provider_message_id)->toBe('gmail-msg-1')
        ->and($email?->thread?->thread_id)->toBe('gmail-thread-1');
});

test('sync removes a leftover local sent copy when the gmail row already exists', function () {
    $this->freezeTime();
    Http::preventStrayRequests();
    Http::fake(fn (Request $request) => gmailSendThenSyncResponse($request));

    $account = EmailAccount::factory()->gmail()->create([
        'email' => 'me@example.com',
        'token_expiry' => now()->addHour(),
    ]);
    $localThread = EmailThread::factory()->create([
        'account_id' => $account->id,
        'thread_id' => 'local-duplicate-sent',
        'subject' => 'Hello',
    ]);
    $gmailThread = EmailThread::factory()->create([
        'account_id' => $account->id,
        'thread_id' => 'gmail-thread-1',
        'subject' => 'Hello',
    ]);
    Email::factory()->create([
        'account_id' => $account->id,
        'thread_id' => $localThread->id,
        'message_id' => 'bbbbbbbb-cccc-dddd-eeee-ffffffffffff',
        'provider_message_id' => null,
        'from_email' => $account->email,
        'subject' => 'Hello',
        'internal_date' => now()->subHours(5),
        'is_draft' => false,
    ]);
    Email::factory()->create([
        'account_id' => $account->id,
        'thread_id' => $gmailThread->id,
        'message_id' => 'sent-message@example.com',
        'provider_message_id' => 'gmail-msg-1',
        'from_email' => $account->email,
        'subject' => 'Hello',
        'internal_date' => now()->subHours(5),
        'is_draft' => false,
    ]);

    $sync = app(MailboxSyncService::class)->syncAccount($account);

    expect($sync['success'])->toBeTrue()
        ->and(Email::query()->where('account_id', $account->id)->count())->toBe(1)
        ->and(Email::query()->where('account_id', $account->id)->value('message_id'))->toBe('sent-message@example.com')
        ->and(EmailThread::query()->whereKey($localThread->id)->exists())->toBeFalse()
        ->and(EmailThread::query()->whereKey($gmailThread->id)->exists())->toBeTrue();
});

test('sync removes leftover local sent copies without refetching the gmail thread', function () {
    Http::preventStrayRequests();
    Http::fake(fn (Request $request) => gmailEmptySyncResponse($request));

    $account = EmailAccount::factory()->gmail()->create([
        'email' => 'me@example.com',
        'token_expiry' => now()->addHour(),
    ]);
    $localThread = EmailThread::factory()->create([
        'account_id' => $account->id,
        'thread_id' => 'local-sent-leftover',
        'subject' => 'Hello',
    ]);
    $gmailThread = EmailThread::factory()->create([
        'account_id' => $account->id,
        'thread_id' => 'gmail-thread-1',
        'subject' => 'Hello',
    ]);
    Email::factory()->create([
        'account_id' => $account->id,
        'thread_id' => $localThread->id,
        'message_id' => 'cccccccc-dddd-eeee-ffff-111111111111',
        'provider_message_id' => null,
        'from_email' => $account->email,
        'subject' => 'Hello',
        'internal_date' => now()->subHours(3),
        'is_draft' => false,
    ]);
    Email::factory()->create([
        'account_id' => $account->id,
        'thread_id' => $gmailThread->id,
        'message_id' => 'sent-message@example.com',
        'provider_message_id' => 'gmail-msg-1',
        'from_email' => $account->email,
        'subject' => 'Hello',
        'internal_date' => now()->subHours(3),
        'is_draft' => false,
    ]);

    $sync = app(MailboxSyncService::class)->syncAccount($account);

    expect($sync['success'])->toBeTrue()
        ->and(Email::query()->where('account_id', $account->id)->count())->toBe(1)
        ->and(EmailThread::query()->whereKey($localThread->id)->exists())->toBeFalse()
        ->and(Email::query()->where('account_id', $account->id)->value('message_id'))->toBe('sent-message@example.com');
});

/**
 * @return PromiseInterface|Response
 */
function gmailSendThenSyncResponse(Request $request, string $subject = 'Hello', bool $includeSentHeaders = true)
{
    $url = $request->url();

    if ($request->method() === 'POST' && str_contains($url, '/users/me/messages/send')) {
        return Http::response([
            'id' => 'gmail-msg-1',
            'threadId' => 'gmail-thread-1',
        ]);
    }

    if (str_contains($url, '/users/me/messages/gmail-msg-1')) {
        if (! $includeSentHeaders) {
            return Http::response(['error' => 'Not Found'], 404);
        }

        return Http::response([
            'id' => 'gmail-msg-1',
            'threadId' => 'gmail-thread-1',
            'payload' => [
                'headers' => [
                    ['name' => 'Message-ID', 'value' => '<sent-message@example.com>'],
                ],
            ],
        ]);
    }

    if (str_contains($url, '/users/me/labels')) {
        return Http::response([
            'labels' => [
                ['id' => 'INBOX', 'name' => 'INBOX'],
                ['id' => 'SENT', 'name' => 'SENT'],
            ],
        ]);
    }

    if (str_contains($url, '/users/me/threads/gmail-thread-1')) {
        return Http::response([
            'id' => 'gmail-thread-1',
            'snippet' => $subject,
            'messages' => [
                [
                    'id' => 'gmail-msg-1',
                    'threadId' => 'gmail-thread-1',
                    'snippet' => $subject,
                    'internalDate' => (string) (now()->getTimestamp() * 1000),
                    'labelIds' => ['SENT', 'INBOX'],
                    'payload' => [
                        'mimeType' => 'text/plain',
                        'headers' => [
                            ['name' => 'Message-ID', 'value' => '<sent-message@example.com>'],
                            ['name' => 'From', 'value' => 'Me <me@example.com>'],
                            ['name' => 'To', 'value' => 'to@example.com'],
                            ['name' => 'Subject', 'value' => $subject],
                        ],
                        'body' => [
                            'data' => rtrim(strtr(base64_encode('Hi'), '+/', '-_'), '='),
                        ],
                    ],
                ],
            ],
        ]);
    }

    if (str_contains($url, '/users/me/threads')) {
        return Http::response([
            'threads' => [['id' => 'gmail-thread-1']],
        ]);
    }

    if (str_contains($url, '/users/me/profile')) {
        return Http::response([
            'historyId' => '99',
        ]);
    }

    return Http::response(['error' => 'unexpected '.$url], 500);
}

/**
 * @return PromiseInterface|Response
 */
function gmailEmptySyncResponse(Request $request)
{
    $url = $request->url();

    if (str_contains($url, '/users/me/labels')) {
        return Http::response([
            'labels' => [
                ['id' => 'INBOX', 'name' => 'INBOX'],
                ['id' => 'SENT', 'name' => 'SENT'],
            ],
        ]);
    }

    if (str_contains($url, '/users/me/threads')) {
        return Http::response([
            'threads' => [],
        ]);
    }

    if (str_contains($url, '/users/me/profile')) {
        return Http::response([
            'historyId' => '99',
        ]);
    }

    return Http::response(['error' => 'unexpected '.$url], 500);
}
