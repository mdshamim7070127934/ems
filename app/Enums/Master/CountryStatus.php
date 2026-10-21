<?php

namespace App\Enums\Master;

enum CountryStatus: string
{
    case Active = 'active';
    case Inactive = 'inactive';
    case Delete = 'delete';

    public function label(): string
    {
        return match ($this) {
            self::Active => 'Active',
            self::Inactive => 'Inactive',
            self::Delete => 'Delete',
        };
    }
}
