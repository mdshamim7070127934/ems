<?php

namespace App\Policies\Mailbox;

use App\Enums\Mailbox\MailboxPermission;
use App\Models\Mailbox\EmailThread;
use App\Models\User;

class EmailThreadPolicy
{
    public function view(User $user, EmailThread $mailboxThread): bool
    {
        return $this->owns($user, $mailboxThread) && $user->can(MailboxPermission::Inbox->value);
    }

    public function update(User $user, EmailThread $mailboxThread): bool
    {
        return $this->view($user, $mailboxThread);
    }

    private function owns(User $user, EmailThread $mailboxThread): bool
    {
        $mailboxThread->loadMissing('account');

        return $mailboxThread->account?->user_id === $user->id;
    }
}
