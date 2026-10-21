<?php

use App\Jobs\SyncMailboxAccountJob;
use App\Models\Mailbox\BlockedEmail;
use App\Models\Mailbox\Email;
use App\Models\Mailbox\EmailAccount;
use App\Models\Mailbox\EmailAttachment;
use App\Models\Mailbox\EmailFolder;
use App\Models\Mailbox\EmailThread;
use App\Models\User;
use App\Services\Mailbox\MailboxSyncService;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Inertia\Testing\AssertableInertia as Assert;

test('guests cannot view the mailbox', function () {
    $this->get(route('mailbox.index'))
        ->assertRedirect(route('login'));
});

test('users without a mailbox are redirected to connect', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('mailbox.index'))
        ->assertRedirect(route('mailbox.connect'));
});

test('the inbox lists threads for the authenticated users mailbox', function () {
    $user = User::factory()->create();
    $account = EmailAccount::factory()->gmail()->default()->create(['user_id' => $user->id]);
    $folder = EmailFolder::factory()->inbox()->create(['account_id' => $account->id]);
    $thread = EmailThread::factory()->create([
        'account_id' => $account->id,
        'subject' => 'Hello from inbox',
        'last_message_at' => now(),
    ]);
    Email::factory()->create([
        'account_id' => $account->id,
        'thread_id' => $thread->id,
        'folder_id' => $folder->id,
        'subject' => 'Hello from inbox',
        'from_email' => 'sender@example.com',
        'is_draft' => false,
    ]);

    $this->actingAs($user)
        ->get(route('mailbox.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('mailbox/index')
            ->where('account.email', $account->email)
            ->has('threads.data', 1)
            ->where('threads.data.0.subject', 'Hello from inbox')
            ->where('threads.data.0.sender_blocked', false)
        );
});

test('inbox threads include an attachment flag when a message has files', function () {
    $user = User::factory()->create();
    $account = EmailAccount::factory()->gmail()->default()->create(['user_id' => $user->id]);
    $folder = EmailFolder::factory()->inbox()->create(['account_id' => $account->id]);
    $thread = EmailThread::factory()->create([
        'account_id' => $account->id,
        'subject' => 'With file',
        'last_message_at' => now(),
    ]);
    $email = Email::factory()->create([
        'account_id' => $account->id,
        'thread_id' => $thread->id,
        'folder_id' => $folder->id,
        'subject' => 'With file',
        'from_email' => 'sender@example.com',
        'is_draft' => false,
    ]);
    EmailAttachment::factory()->create(['email_id' => $email->id]);

    $this->actingAs($user)
        ->get(route('mailbox.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('threads.data.0.has_attachments', true)
        );
});

test('a user cannot view another users thread', function () {
    $user = User::factory()->create();
    $other = User::factory()->create();
    $account = EmailAccount::factory()->gmail()->create(['user_id' => $other->id]);
    $thread = EmailThread::factory()->create(['account_id' => $account->id]);

    $this->actingAs($user)
        ->get(route('mailbox.threads.show', $thread))
        ->assertForbidden();
});

test('blocked senders are hidden from the inbox', function () {
    $user = User::factory()->create();
    $account = EmailAccount::factory()->gmail()->default()->create(['user_id' => $user->id]);
    $folder = EmailFolder::factory()->inbox()->create(['account_id' => $account->id]);
    $thread = EmailThread::factory()->create(['account_id' => $account->id, 'last_message_at' => now()]);
    Email::factory()->create([
        'account_id' => $account->id,
        'thread_id' => $thread->id,
        'folder_id' => $folder->id,
        'from_email' => 'spam@example.com',
        'is_draft' => false,
    ]);
    BlockedEmail::factory()->create([
        'user_id' => $user->id,
        'email_id' => 'spam@example.com',
        'blocked_by' => $user->id,
    ]);

    $this->actingAs($user)
        ->get(route('mailbox.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->has('threads.data', 0)
        );
});

test('blocked senders remain visible in search with an unblock flag', function () {
    $user = User::factory()->create();
    $account = EmailAccount::factory()->gmail()->default()->create(['user_id' => $user->id]);
    $folder = EmailFolder::factory()->inbox()->create(['account_id' => $account->id]);
    $thread = EmailThread::factory()->create([
        'account_id' => $account->id,
        'subject' => 'Blocked offer',
        'last_message_at' => now(),
    ]);
    Email::factory()->create([
        'account_id' => $account->id,
        'thread_id' => $thread->id,
        'folder_id' => $folder->id,
        'from_email' => 'spam@example.com',
        'subject' => 'Blocked offer',
        'is_draft' => false,
    ]);
    BlockedEmail::factory()->create([
        'user_id' => $user->id,
        'email_id' => 'spam@example.com',
        'blocked_by' => $user->id,
    ]);

    $this->actingAs($user)
        ->get(route('mailbox.index', ['search' => 'spam@example.com']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->has('threads.data', 1)
            ->where('threads.data.0.sender_blocked', true)
            ->where('threads.data.0.from_email', 'spam@example.com')
        );
});

test('the thread page flags blocked senders on each message', function () {
    $user = User::factory()->create();
    $account = EmailAccount::factory()->gmail()->default()->create(['user_id' => $user->id]);
    $folder = EmailFolder::factory()->inbox()->create(['account_id' => $account->id]);
    $thread = EmailThread::factory()->create(['account_id' => $account->id]);
    Email::factory()->create([
        'account_id' => $account->id,
        'thread_id' => $thread->id,
        'folder_id' => $folder->id,
        'from_email' => 'spam@example.com',
        'internal_date' => now()->subMinute(),
        'is_draft' => false,
    ]);
    Email::factory()->create([
        'account_id' => $account->id,
        'thread_id' => $thread->id,
        'folder_id' => $folder->id,
        'from_email' => $account->email,
        'internal_date' => now(),
        'is_draft' => false,
    ]);
    BlockedEmail::factory()->create([
        'user_id' => $user->id,
        'email_id' => 'spam@example.com',
        'blocked_by' => $user->id,
    ]);

    $this->actingAs($user)
        ->get(route('mailbox.threads.show', $thread))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('mailbox/show')
            ->where('sender_blocked', true)
            ->where('thread.sender_blocked', true)
            ->where('emails.0.sender_blocked', true)
            ->where('emails.0.from_email', 'spam@example.com')
            ->where('emails.1.sender_blocked', false)
            ->where('emails.1.from_email', $account->email)
        );
});

test('a user can block a sender from the mailbox', function () {
    $user = User::factory()->create();
    $account = EmailAccount::factory()->gmail()->default()->create(['user_id' => $user->id]);

    $this->actingAs($user)
        ->from(route('mailbox.threads.show', EmailThread::factory()->create(['account_id' => $account->id])))
        ->post(route('mailbox.block-sender'), [
            'email' => 'spam@example.com',
            'account_id' => $account->id,
        ])
        ->assertRedirect();

    $this->assertDatabaseHas('ems_block_email', [
        'user_id' => $user->id,
        'email_id' => 'spam@example.com',
        'is_block' => 1,
    ]);
});

test('a user can unblock a sender from the mailbox', function () {
    $user = User::factory()->create();
    $account = EmailAccount::factory()->gmail()->default()->create(['user_id' => $user->id]);
    BlockedEmail::factory()->create([
        'user_id' => $user->id,
        'email_id' => 'spam@example.com',
        'blocked_by' => $user->id,
    ]);

    $this->actingAs($user)
        ->post(route('mailbox.unblock-sender'), [
            'email' => 'spam@example.com',
            'account_id' => $account->id,
        ])
        ->assertRedirect();

    $this->assertDatabaseMissing('ems_block_email', [
        'user_id' => $user->id,
        'email_id' => 'spam@example.com',
    ]);
});

test('the thread page hides draft messages', function () {
    $user = User::factory()->create();
    $account = EmailAccount::factory()->gmail()->default()->create(['user_id' => $user->id]);
    $folder = EmailFolder::factory()->inbox()->create(['account_id' => $account->id]);
    $thread = EmailThread::factory()->create(['account_id' => $account->id]);
    Email::factory()->create([
        'account_id' => $account->id,
        'thread_id' => $thread->id,
        'folder_id' => $folder->id,
        'from_email' => 'sender@example.com',
        'subject' => 'Visible message',
        'is_draft' => false,
    ]);
    Email::factory()->create([
        'account_id' => $account->id,
        'thread_id' => $thread->id,
        'folder_id' => $folder->id,
        'from_email' => $account->email,
        'subject' => 'Draft copy',
        'is_draft' => true,
    ]);

    $this->actingAs($user)
        ->get(route('mailbox.threads.show', $thread))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('mailbox/show')
            ->has('emails', 1)
            ->where('emails.0.subject', 'Visible message')
        );
});

test('the sent folder lists one thread after a local sent copy is collapsed', function () {
    Http::preventStrayRequests();
    Http::fake(function (Request $request) {
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
            return Http::response(['threads' => []]);
        }

        if (str_contains($url, '/users/me/profile')) {
            return Http::response(['historyId' => '99']);
        }

        return Http::response(['error' => 'unexpected '.$url], 500);
    });

    $user = User::factory()->create();
    $account = EmailAccount::factory()->gmail()->default()->create([
        'user_id' => $user->id,
        'email' => 'me@example.com',
        'token_expiry' => now()->addHour(),
    ]);
    $sent = EmailFolder::factory()->sent()->create(['account_id' => $account->id]);
    $localThread = EmailThread::factory()->create([
        'account_id' => $account->id,
        'thread_id' => 'local-sent-list',
        'subject' => 'Hello from sent',
        'last_message_at' => now(),
    ]);
    $gmailThread = EmailThread::factory()->create([
        'account_id' => $account->id,
        'thread_id' => 'gmail-sent-list',
        'subject' => 'Hello from sent',
        'last_message_at' => now(),
    ]);
    Email::factory()->create([
        'account_id' => $account->id,
        'thread_id' => $localThread->id,
        'folder_id' => $sent->id,
        'message_id' => 'dddddddd-eeee-ffff-aaaa-222222222222',
        'provider_message_id' => null,
        'from_email' => $account->email,
        'subject' => 'Hello from sent',
        'is_draft' => false,
    ]);
    Email::factory()->create([
        'account_id' => $account->id,
        'thread_id' => $gmailThread->id,
        'folder_id' => $sent->id,
        'message_id' => 'hello-from-sent@example.com',
        'provider_message_id' => 'gmail-sent-1',
        'from_email' => $account->email,
        'subject' => 'Hello from sent',
        'is_draft' => false,
    ]);

    app(MailboxSyncService::class)->syncAccount($account);

    $this->actingAs($user)
        ->get(route('mailbox.index', ['folder' => 'sent']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('mailbox/index')
            ->has('threads.data', 1)
            ->where('threads.data.0.subject', 'Hello from sent')
        );
});

test('syncing a mailbox queues a job', function () {
    Queue::fake([SyncMailboxAccountJob::class]);

    $user = User::factory()->create();
    $account = EmailAccount::factory()->gmail()->default()->create(['user_id' => $user->id]);

    $this->actingAs($user)
        ->post(route('mailbox.sync'), ['account_id' => $account->id])
        ->assertRedirect();

    Queue::assertPushedOn(
        (string) config('mailbox.queues.sync'),
        SyncMailboxAccountJob::class,
        fn (SyncMailboxAccountJob $job): bool => $job->account->is($account),
    );
});

test('inbox page size cannot exceed the allowed maximum', function () {
    $user = User::factory()->create();
    EmailAccount::factory()->gmail()->default()->create(['user_id' => $user->id]);

    $this->actingAs($user)
        ->get(route('mailbox.index', ['pageSize' => 1000]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->where('filters.pageSize', 25));
});

test('a user can bulk move threads to another folder', function () {
    $user = User::factory()->create();
    $account = EmailAccount::factory()->gmail()->default()->create(['user_id' => $user->id]);
    $inbox = EmailFolder::factory()->inbox()->create(['account_id' => $account->id]);
    $trash = EmailFolder::factory()->trash()->create(['account_id' => $account->id]);
    $thread = EmailThread::factory()->create(['account_id' => $account->id]);
    $email = Email::factory()->create([
        'account_id' => $account->id,
        'thread_id' => $thread->id,
        'folder_id' => $inbox->id,
        'is_draft' => false,
    ]);

    $this->actingAs($user)
        ->post(route('mailbox.bulk-move'), [
            'folder_id' => $trash->id,
            'thread_ids' => [$thread->id],
        ])
        ->assertRedirect();

    expect($email->fresh()?->folder_id)->toBe($trash->id);
});

test('a user can mark a message as spam', function () {
    $user = User::factory()->create();
    $account = EmailAccount::factory()->gmail()->default()->create(['user_id' => $user->id]);
    $inbox = EmailFolder::factory()->inbox()->create(['account_id' => $account->id]);
    $spam = EmailFolder::factory()->spam()->create(['account_id' => $account->id]);
    $thread = EmailThread::factory()->create(['account_id' => $account->id]);
    $email = Email::factory()->create([
        'account_id' => $account->id,
        'thread_id' => $thread->id,
        'folder_id' => $inbox->id,
        'is_draft' => false,
    ]);

    $this->actingAs($user)
        ->post(route('mailbox.action'), [
            'action' => 'spam',
            'email_id' => $email->id,
        ])
        ->assertRedirect();

    expect($email->fresh()?->folder_id)->toBe($spam->id);
});

test('gmail sync stores a message once for the same message id', function () {
    Http::preventStrayRequests();
    Http::fake(function (Request $request) {
        $url = $request->url();

        if (str_contains($url, '/users/me/labels')) {
            return Http::response([
                'labels' => [
                    ['id' => 'INBOX', 'name' => 'INBOX'],
                    ['id' => 'SENT', 'name' => 'SENT'],
                ],
            ]);
        }

        if (str_contains($url, '/users/me/threads/thread-1')) {
            return Http::response([
                'id' => 'thread-1',
                'snippet' => 'Hello',
                'messages' => [
                    gmailMessagePayload(),
                ],
            ]);
        }

        if (str_contains($url, '/users/me/threads')) {
            return Http::response([
                'threads' => [['id' => 'thread-1']],
            ]);
        }

        if (str_contains($url, '/users/me/profile')) {
            return Http::response([
                'historyId' => '99',
            ]);
        }

        return Http::response(['error' => 'unexpected '.$url], 500);
    });

    $account = EmailAccount::factory()->gmail()->create([
        'token_expiry' => now()->addHour(),
    ]);

    $sync = app(MailboxSyncService::class);
    $first = $sync->syncAccount($account);
    $second = $sync->syncAccount($account);

    expect($first['success'])->toBeTrue()
        ->and($first['synced'])->toBe(1)
        ->and($second['synced'])->toBe(0)
        ->and(Email::query()->where('account_id', $account->id)->count())->toBe(1);
});

test('connecting smtp imap creates a user owned mailbox account', function () {
    Queue::fake([SyncMailboxAccountJob::class]);
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('mailbox.connect.smtp.store'), [
            'email' => 'me@example.com',
            'display_name' => 'Me',
            'smtp_host' => 'smtp.example.com',
            'smtp_port' => 587,
            'smtp_encryption' => 'tls',
            'imap_host' => 'imap.example.com',
            'imap_port' => 993,
            'imap_encryption' => 'ssl',
            'smtp_password' => 'secret',
            'imap_password' => 'secret',
        ])
        ->assertRedirect(route('mailbox.index'));

    $account = EmailAccount::query()->where('email', 'me@example.com')->first();

    expect($account)->not->toBeNull()
        ->and($account->user_id)->toBe($user->id)
        ->and($account->is_default)->toBeTrue()
        ->and($account->smtp_password)->toBe('secret');
});

function gmailMessagePayload(): array
{
    return [
        'id' => 'msg-1',
        'threadId' => 'thread-1',
        'snippet' => 'Hello',
        'internalDate' => (string) (now()->getTimestamp() * 1000),
        'labelIds' => ['INBOX', 'UNREAD'],
        'payload' => [
            'mimeType' => 'text/plain',
            'headers' => [
                ['name' => 'Message-ID', 'value' => '<unique-message@example.com>'],
                ['name' => 'From', 'value' => 'Sender <sender@example.com>'],
                ['name' => 'To', 'value' => 'me@example.com'],
                ['name' => 'Subject', 'value' => 'Hello'],
            ],
            'body' => [
                'data' => rtrim(strtr(base64_encode('Hello body'), '+/', '-_'), '='),
            ],
        ],
    ];
}
