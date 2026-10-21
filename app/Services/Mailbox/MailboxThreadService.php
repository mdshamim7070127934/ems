<?php

namespace App\Services\Mailbox;

use App\Enums\Mailbox\FolderType;
use App\Models\Mailbox\Email;
use App\Models\Mailbox\EmailAccount;
use App\Models\Mailbox\EmailFolder;
use App\Models\Mailbox\EmailThread;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Str;

class MailboxThreadService
{
    public function __construct(
        private MailboxFolderService $folders,
        private BlockedSenderService $blockedSenders,
    ) {}

    /**
     * @return LengthAwarePaginator<int, EmailThread>
     */
    public function paginate(
        EmailAccount $account,
        User $user,
        string $folderSlug,
        ?int $labelId,
        ?string $search,
        int $page,
        int $pageSize,
    ): LengthAwarePaginator {
        $query = EmailThread::query()
            ->where('account_id', $account->id)
            ->whereHas('emails', function (Builder $emails) use ($account, $folderSlug, $labelId): void {
                $emails->where('account_id', $account->id)
                    ->where('is_draft', false);

                if ($labelId !== null) {
                    $emails->whereHas('labels', fn (Builder $labels) => $labels->where('email_labels.id', $labelId));

                    return;
                }

                if ($folderSlug === 'starred') {
                    $emails->where(function (Builder $query): void {
                        $query->where('is_starred', true)
                            ->orWhereHas('thread', fn (Builder $thread) => $thread->where('is_starred', true));
                    });

                    return;
                }

                $folder = EmailFolder::query()
                    ->where('account_id', $account->id)
                    ->where('slug', $folderSlug)
                    ->first();

                if ($folder !== null) {
                    $emails->where('folder_id', $folder->id);
                }
            });

        $blockedAddresses = $this->blockedSenders->blockedAddresses($user);
        $hideBlocked = $search === null || $search === '';

        if ($hideBlocked && $blockedAddresses !== []) {
            $query->whereNotIn('email_threads.id', function ($sub) use ($account, $blockedAddresses): void {
                $sub->from('emails')
                    ->select('thread_id')
                    ->where('account_id', $account->id)
                    ->whereIn('from_email', $blockedAddresses)
                    ->whereRaw('emails.id = (select min(first_emails.id) from emails as first_emails where first_emails.thread_id = emails.thread_id)');
            });
        }

        $query->with([
            'emails' => fn ($emails) => $emails
                ->orderBy('internal_date')
                ->orderBy('id')
                ->with('labels')
                ->withExists('attachments'),
        ]);

        if (is_string($search) && $search !== '') {
            $escaped = addcslashes($search, '%_\\');
            $query->where(function (Builder $threads) use ($escaped): void {
                $threads->whereLike('subject', '%'.$escaped.'%')
                    ->orWhereLike('snippet', '%'.$escaped.'%')
                    ->orWhereHas('emails', function (Builder $emails) use ($escaped): void {
                        $emails->whereLike('subject', '%'.$escaped.'%')
                            ->orWhereLike('body_plain', '%'.$escaped.'%')
                            ->orWhereLike('from_email', '%'.$escaped.'%')
                            ->orWhereLike('from_name', '%'.$escaped.'%');
                    });
            });
        }

        $paginator = $query
            ->orderByDesc('last_message_at')
            ->orderByDesc('id')
            ->paginate($pageSize, ['*'], 'page', $page)
            ->withQueryString();

        $blockedLookup = $this->blockedSenders->blockedLookup($user);

        foreach ($paginator->getCollection() as $thread) {
            $from = Str::lower((string) $thread->emails->sortBy('id')->first()?->from_email);
            $thread->setAttribute('sender_blocked', $from !== '' && isset($blockedLookup[$from]));
        }

        return $paginator;
    }

    public function markRead(Email $email): void
    {
        $email->update(['is_unread' => false]);
    }

    public function markUnread(Email $email): void
    {
        $email->update(['is_unread' => true]);
    }

    public function markThreadRead(EmailThread $thread): void
    {
        Email::query()->where('thread_id', $thread->id)->update(['is_unread' => false]);
    }

    public function star(Email $email): void
    {
        $email->update(['is_starred' => true]);
        $email->thread?->update(['is_starred' => true]);
    }

    public function unstar(Email $email): void
    {
        $email->update(['is_starred' => false]);

        if ($email->thread !== null && ! Email::query()->where('thread_id', $email->thread->id)->where('is_starred', true)->exists()) {
            $email->thread->update(['is_starred' => false]);
        }
    }

    public function starThread(EmailThread $thread): void
    {
        $thread->update(['is_starred' => true]);
        Email::query()->where('thread_id', $thread->id)->update(['is_starred' => true]);
    }

    public function unstarThread(EmailThread $thread): void
    {
        $thread->update(['is_starred' => false]);
        Email::query()->where('thread_id', $thread->id)->update(['is_starred' => false]);
    }

    public function moveToTrash(Email $email): void
    {
        $this->moveToSlug($email, 'trash');
    }

    public function moveToSpam(Email $email): void
    {
        $this->moveToSlug($email, 'spam');
    }

    public function moveToFolder(Email $email, EmailFolder $folder): void
    {
        $email->update(['folder_id' => $folder->id]);
    }

    /**
     * @param  list<int>  $threadIds
     */
    public function moveThreadsToFolder(EmailAccount $account, array $threadIds, EmailFolder $folder): void
    {
        Email::query()
            ->where('account_id', $account->id)
            ->whereIn('thread_id', $threadIds)
            ->update(['folder_id' => $folder->id]);
    }

    private function moveToSlug(Email $email, string $slug): void
    {
        $folder = EmailFolder::query()
            ->where('account_id', $email->account_id)
            ->where('slug', $slug)
            ->first();

        if ($folder === null && $email->account !== null) {
            $folder = $this->folders->upsert(
                $email->account,
                $slug,
                ucfirst($slug),
                $slug,
                FolderType::System,
                'local-'.$slug,
            );
        }

        if ($folder !== null) {
            $email->update(['folder_id' => $folder->id]);
        }
    }
}
