<?php

namespace App\Policies\Mailbox;

use App\Enums\Mailbox\MailboxPermission;
use App\Models\Mailbox\Email;
use App\Models\User;

class EmailPolicy
{
    public function view(User $user, Email $mailboxEmail): bool
    {
        return $this->owns($user, $mailboxEmail) && $user->can(MailboxPermission::Inbox->value);
    }

    public function update(User $user, Email $mailboxEmail): bool
    {
        return $this->view($user, $mailboxEmail);
    }

    public function download(User $user, Email $mailboxEmail): bool
    {
        return $this->view($user, $mailboxEmail);
    }

    private function owns(User $user, Email $mailboxEmail): bool
    {
        $mailboxEmail->loadMissing('account');

        return $mailboxEmail->account?->user_id === $user->id;
    }
}
