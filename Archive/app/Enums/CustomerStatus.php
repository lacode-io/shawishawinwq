<?php

namespace App\Enums;

enum CustomerStatus: string
{
    case Active = 'active';
    case Completed = 'completed';
    case Trashed = 'trashed';

    public function label(): string
    {
        return match ($this) {
            self::Active => 'نشط',
            self::Completed => 'مكتمل',
            self::Trashed => 'محذوف',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Active => 'success',
            self::Completed => 'gray',
            self::Trashed => 'danger',
        };
    }
}
