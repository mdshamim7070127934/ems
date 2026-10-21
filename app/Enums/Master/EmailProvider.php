<?php

namespace App\Enums\Master;

enum EmailProvider: string
{
    case Gmail = 'gmail';
    case Outlook = 'outlook';
    case Smtp = 'smtp';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::Gmail => 'Gmail',
            self::Outlook => 'Outlook',
            self::Smtp => 'SMTP',
            self::Other => 'Other',
        };
    }

    public function usesOAuth(): bool
    {
        return $this === self::Gmail || $this === self::Outlook;
    }

    public function oauthMailer(): EmailMailer
    {
        return match ($this) {
            self::Gmail => EmailMailer::GmailApi,
            self::Outlook => EmailMailer::GraphApi,
            default => throw new \InvalidArgumentException('Provider does not use OAuth.'),
        };
    }
}
