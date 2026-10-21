<?php

namespace App\Http\Controllers\Mailbox;

use App\Http\Requests\Mailbox\MailboxBulkMoveRequest;
use App\Http\Requests\Mailbox\MailboxThreadActionRequest;
use App\Http\Resources\Mailbox\MailboxAccountResource;
use App\Http\Resources\Mailbox\MailboxEmailResource;
use App\Http\Resources\Mailbox\MailboxThreadResource;
use App\Models\Mailbox\Email;
use App\Models\Mailbox\EmailFolder;
use App\Models\Mailbox\EmailThread;
use App\Services\Mailbox\BlockedSenderService;
use App\Services\Mailbox\MailboxThreadService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class MailboxThreadController extends MailboxController
{
    public function show(
        Request $request,
        EmailThread $mailboxThread,
        BlockedSenderService $blocked,
        MailboxThreadService $threads,
    ): Response {
        $this->loadThreadMessages($mailboxThread);
        $this->authorize('view', $mailboxThread);

        $account = $mailboxThread->account;
        abort_if($account === null, 404);

        $threads->markThreadRead($mailboxThread);
        $mailboxThread->refresh();
        $this->loadThreadMessages($mailboxThread);

        $blockedLookup = $blocked->blockedLookup($this->user($request));

        foreach ($mailboxThread->emails as $email) {
            $email->setAttribute(
                'sender_blocked',
                isset($blockedLookup[Str::lower((string) $email->from_email)]),
            );
        }

        $first = $mailboxThread->emails->sortBy('id')->first();
        $senderBlocked = $first !== null && isset($blockedLookup[Str::lower((string) $first->from_email)]);
        $mailboxThread->setAttribute('sender_blocked', $senderBlocked);

        return Inertia::render('mailbox/show', [
            ...$this->mailboxChrome($request, $account),
            'account' => (new MailboxAccountResource($account))->resolve(),
            'thread' => (new MailboxThreadResource($mailboxThread))->resolve(),
            'emails' => MailboxEmailResource::collection($mailboxThread->emails->sortBy('internal_date')->values())->resolve(),
            'sender_blocked' => $senderBlocked,
        ]);
    }

    public function action(
        MailboxThreadActionRequest $request,
        MailboxThreadService $threads,
    ): RedirectResponse {
        $email = null;
        $thread = null;

        if ($request->filled('email_id')) {
            $email = Email::query()->with(['account', 'thread'])->findOrFail($request->integer('email_id'));
            $this->authorize('update', $email);
        }

        if ($request->filled('thread_id')) {
            $thread = EmailThread::query()->with('account')->findOrFail($request->integer('thread_id'));
            $this->authorize('update', $thread);
        }

        match ($request->string('action')->toString()) {
            'read' => $email !== null ? $threads->markRead($email) : ($thread !== null ? $threads->markThreadRead($thread) : null),
            'unread' => $email !== null ? $threads->markUnread($email) : null,
            'star' => $email !== null ? $threads->star($email) : ($thread !== null ? $threads->starThread($thread) : null),
            'unstar' => $email !== null ? $threads->unstar($email) : ($thread !== null ? $threads->unstarThread($thread) : null),
            'trash' => $email !== null ? $threads->moveToTrash($email) : null,
            'spam' => $email !== null ? $threads->moveToSpam($email) : null,
            default => null,
        };

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('Mailbox updated.'),
        ]);

        return back();
    }

    public function bulkMove(MailboxBulkMoveRequest $request, MailboxThreadService $threads): RedirectResponse
    {
        $folder = EmailFolder::query()->with('account')->findOrFail($request->integer('folder_id'));
        $account = $folder->account;
        abort_if($account === null, 404);
        $this->authorize('update', $account);

        $threadIds = [];

        foreach ($request->array('thread_ids') as $threadId) {
            $threadIds[] = (int) $threadId;
        }

        $ownedIds = [];

        foreach (EmailThread::query()
            ->where('account_id', $account->id)
            ->whereIn('id', $threadIds)
            ->pluck('id') as $ownedId) {
            $ownedIds[] = (int) $ownedId;
        }

        $threads->moveThreadsToFolder($account, $ownedIds, $folder);

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('Threads moved.'),
        ]);

        return back();
    }

    private function loadThreadMessages(EmailThread $thread): void
    {
        $thread->load([
            'account',
            'emails' => function ($query): void {
                $query->notDraft()
                    ->with(['attachments', 'labels'])
                    ->orderBy('internal_date')
                    ->orderBy('id');
            },
        ]);
    }
}
