<?php

namespace App\Enums\Mailbox;

enum FolderType: string
{
    case System = 'system';
    case Custom = 'custom';

    public function label(): string
    {
        return match ($this) {
            self::System => 'System',
            self::Custom => 'Custom',
        };
    }
}
