<?php

namespace App\Enums\Master;

enum SmtpEncryption: string
{
    case Tls = 'tls';
    case Ssl = 'ssl';
    case None = 'none';

    public function label(): string
    {
        return match ($this) {
            self::Tls => 'TLS',
            self::Ssl => 'SSL',
            self::None => 'None',
        };
    }
}
