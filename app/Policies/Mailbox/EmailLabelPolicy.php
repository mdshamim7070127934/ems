<?php

namespace App\Policies\Mailbox;

use App\Enums\Mailbox\MailboxPermission;
use App\Models\Mailbox\EmailLabel;
use App\Models\User;

class EmailLabelPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can(MailboxPermission::Assign->value)
            || $user->can(MailboxPermission::Inbox->value);
    }

    public function create(User $user): bool
    {
        return $user->can(MailboxPermission::Assign->value);
    }

    public function update(User $user, EmailLabel $mailboxLabel): bool
    {
        return $this->owns($user, $mailboxLabel) && $this->create($user);
    }

    public function delete(User $user, EmailLabel $mailboxLabel): bool
    {
        return $this->update($user, $mailboxLabel);
    }

    private function owns(User $user, EmailLabel $mailboxLabel): bool
    {
        if ($mailboxLabel->user_id === $user->id) {
            return true;
        }

        $mailboxLabel->loadMissing('account');

        return $mailboxLabel->account?->user_id === $user->id;
    }
}
