<?php

namespace App\Enums\Mailbox;

enum MailboxProvider: string
{
    case Gmail = 'gmail';
    case Outlook = 'outlook';
    case SmtpImap = 'smtp_imap';

    public function label(): string
    {
        return match ($this) {
            self::Gmail => 'Gmail',
            self::Outlook => 'Outlook',
            self::SmtpImap => 'SMTP / IMAP',
        };
    }

    public function usesOAuth(): bool
    {
        return $this === self::Gmail || $this === self::Outlook;
    }
}
