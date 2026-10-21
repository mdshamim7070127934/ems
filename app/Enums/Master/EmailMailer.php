<?php

namespace App\Enums\Master;

enum EmailMailer: string
{
    case Smtp = 'smtp';
    case GmailApi = 'gmail_api';
    case GraphApi = 'graph_api';

    public function label(): string
    {
        return match ($this) {
            self::Smtp => 'SMTP',
            self::GmailApi => 'Gmail API',
            self::GraphApi => 'Microsoft Graph',
        };
    }
}
