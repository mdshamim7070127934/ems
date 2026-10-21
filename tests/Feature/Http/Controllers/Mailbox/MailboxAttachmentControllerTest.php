<?php

use App\Models\Mailbox\Email;
use App\Models\Mailbox\EmailAccount;
use App\Models\Mailbox\EmailAttachment;
use App\Models\Mailbox\EmailFolder;
use App\Models\Mailbox\EmailThread;
use App\Models\User;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;

test('guests cannot download mailbox attachments', function () {
    $attachment = EmailAttachment::factory()->create();

    $this->get(route('mailbox.attachments.show', $attachment))
        ->assertRedirect(route('login'));
});

test('a user can download an attachment from their mailbox', function () {
    $user = User::factory()->create();
    $attachment = storedMailboxAttachment($user, 'report.pdf', 'pdf-bytes');

    $this->actingAs($user)
        ->get(route('mailbox.attachments.show', $attachment))
        ->assertOk()
        ->assertDownload('report.pdf');
});

test('a user can preview an attachment from their mailbox', function () {
    $user = User::factory()->create();
    $attachment = storedMailboxAttachment($user, 'photo.png', 'png-bytes', 'image/png');

    $this->actingAs($user)
        ->get(route('mailbox.attachments.preview', $attachment))
        ->assertOk()
        ->assertHeader('content-type', 'image/png');
});

test('a user cannot download another users attachment', function () {
    $user = User::factory()->create();
    $attachment = storedMailboxAttachment(User::factory()->create(), 'secret.pdf', 'secret');

    $this->actingAs($user)
        ->get(route('mailbox.attachments.show', $attachment))
        ->assertForbidden();
});

test('the thread page includes attachments for download and preview', function () {
    $user = User::factory()->create();
    $account = EmailAccount::factory()->gmail()->default()->create(['user_id' => $user->id]);
    $folder = EmailFolder::factory()->inbox()->create(['account_id' => $account->id]);
    $thread = EmailThread::factory()->create(['account_id' => $account->id]);
    $email = Email::factory()->create([
        'account_id' => $account->id,
        'thread_id' => $thread->id,
        'folder_id' => $folder->id,
        'is_draft' => false,
    ]);
    EmailAttachment::factory()->create([
        'email_id' => $email->id,
        'original_filename' => 'one.pdf',
        'size' => 2048,
    ]);
    EmailAttachment::factory()->create([
        'email_id' => $email->id,
        'original_filename' => 'two.png',
        'mime_type' => 'image/png',
        'size' => 4096,
    ]);

    $this->actingAs($user)
        ->get(route('mailbox.threads.show', $thread))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('mailbox/show')
            ->has('emails.0.attachments', 2)
            ->where('emails.0.attachments.0.original_filename', 'one.pdf')
            ->where('emails.0.attachments.1.original_filename', 'two.png')
        );
});

function storedMailboxAttachment(
    User $user,
    string $filename,
    string $contents,
    string $mimeType = 'application/pdf',
): EmailAttachment {
    $disk = (string) config('mailbox.attachments_disk');
    Storage::fake($disk);

    $account = EmailAccount::factory()->gmail()->default()->create(['user_id' => $user->id]);
    $folder = EmailFolder::factory()->inbox()->create(['account_id' => $account->id]);
    $thread = EmailThread::factory()->create(['account_id' => $account->id]);
    $email = Email::factory()->create([
        'account_id' => $account->id,
        'thread_id' => $thread->id,
        'folder_id' => $folder->id,
        'is_draft' => false,
    ]);
    $path = 'email_attachments/'.$filename;
    Storage::disk($disk)->put($path, $contents);

    return EmailAttachment::factory()->create([
        'email_id' => $email->id,
        'filename' => $filename,
        'original_filename' => $filename,
        'mime_type' => $mimeType,
        'size' => strlen($contents),
        'storage_path' => $path,
    ]);
}
