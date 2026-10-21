<?php

namespace App\Policies\Mailbox;

use App\Enums\Mailbox\MailboxPermission;
use App\Models\Mailbox\EmailAccount;
use App\Models\User;

class EmailAccountPolicy
{
    public function viewAny(User $user): bool
    {
        return $this->allows($user, MailboxPermission::Inbox);
    }

    public function view(User $user, EmailAccount $mailboxAccount): bool
    {
        return $this->owns($user, $mailboxAccount) && $this->allows($user, MailboxPermission::Inbox);
    }

    public function create(User $user): bool
    {
        return $this->allows($user, MailboxPermission::Connect);
    }

    public function update(User $user, EmailAccount $mailboxAccount): bool
    {
        return $this->owns($user, $mailboxAccount) && $this->allows($user, MailboxPermission::Connect);
    }

    public function delete(User $user, EmailAccount $mailboxAccount): bool
    {
        return $this->owns($user, $mailboxAccount) && $this->allows($user, MailboxPermission::Connect);
    }

    public function sync(User $user, EmailAccount $mailboxAccount): bool
    {
        return $this->owns($user, $mailboxAccount) && $this->allows($user, MailboxPermission::Sync);
    }

    public function send(User $user, EmailAccount $mailboxAccount): bool
    {
        return $this->owns($user, $mailboxAccount) && $this->allows($user, MailboxPermission::Send);
    }

    public function viewFolder(User $user, EmailAccount $mailboxAccount, string $slug): bool
    {
        if (! $this->owns($user, $mailboxAccount)) {
            return false;
        }

        $permission = MailboxPermission::forFolderSlug($slug);

        return $permission === null
            ? $this->allows($user, MailboxPermission::Inbox)
            : $this->allows($user, $permission);
    }

    private function owns(User $user, EmailAccount $mailboxAccount): bool
    {
        return $mailboxAccount->user_id === $user->id;
    }

    private function allows(User $user, MailboxPermission $permission): bool
    {
        return $user->can($permission->value);
    }
}
