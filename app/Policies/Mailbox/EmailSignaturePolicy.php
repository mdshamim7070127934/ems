<?php

namespace App\Policies\Mailbox;

use App\Models\Mailbox\EmailSignature;
use App\Models\User;

class EmailSignaturePolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, EmailSignature $emailSignature): bool
    {
        return $this->owns($user, $emailSignature);
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, EmailSignature $emailSignature): bool
    {
        return $this->owns($user, $emailSignature);
    }

    public function delete(User $user, EmailSignature $emailSignature): bool
    {
        return $this->owns($user, $emailSignature);
    }

    private function owns(User $user, EmailSignature $emailSignature): bool
    {
        return $emailSignature->user_id === $user->id;
    }
}
