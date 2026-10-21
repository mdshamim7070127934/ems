<?php

namespace App\Events;

use App\Models\Mailbox\EmailAccount;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MailboxSynced
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public EmailAccount $account,
        public int $synced,
    ) {}
}
