<?php

namespace App\Listeners;

use App\Events\UserRegistered;
use App\Jobs\SendUserWelcomeEmailJob;

class SendUserWelcomeEmail
{
    public function handle(UserRegistered $event): void
    {
        SendUserWelcomeEmailJob::dispatch($event->user);
    }
}
