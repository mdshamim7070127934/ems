<?php

namespace App\Policies\Mailbox;

use App\Enums\Mailbox\MailboxPermission;
use App\Models\Mailbox\EmailDraft;
use App\Models\User;

class EmailDraftPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can(MailboxPermission::Drafts->value);
    }

    public function view(User $user, EmailDraft $mailboxDraft): bool
    {
        return $this->owns($user, $mailboxDraft) && $user->can(MailboxPermission::Drafts->value);
    }

    public function create(User $user): bool
    {
        return $user->can(MailboxPermission::Send->value)
            || $user->can(MailboxPermission::Drafts->value);
    }

    public function update(User $user, EmailDraft $mailboxDraft): bool
    {
        return $this->owns($user, $mailboxDraft) && $this->create($user);
    }

    public function delete(User $user, EmailDraft $mailboxDraft): bool
    {
        return $this->view($user, $mailboxDraft);
    }

    public function send(User $user, EmailDraft $mailboxDraft): bool
    {
        return $this->owns($user, $mailboxDraft) && $user->can(MailboxPermission::Send->value);
    }

    private function owns(User $user, EmailDraft $mailboxDraft): bool
    {
        $mailboxDraft->loadMissing('account');

        return $mailboxDraft->account?->user_id === $user->id;
    }
}
