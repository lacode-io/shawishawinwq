<?php

namespace App\Enums;

enum InvestorStatus: string
{
    case Active = 'active';
    case Completed = 'completed';

    public function label(): string
    {
        return match ($this) {
            self::Active => 'نشط',
            self::Completed => 'مكتمل',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Active => 'success',
            self::Completed => 'gray',
        };
    }
}
