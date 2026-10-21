<?php

namespace App\Events;

use App\Models\User;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class UserRegistered implements ShouldDispatchAfterCommit
{
    use Dispatchable, SerializesModels;

    public function __construct(public User $user) {}
}
