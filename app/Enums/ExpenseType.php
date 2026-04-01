<?php

namespace App\Enums;

enum ExpenseType: string
{
    case Business = 'business';
    case Personal = 'personal';
    case Salary = 'salary';
    case Commission = 'commission';
    case Custom = 'custom';

    public function label(): string
    {
        return match ($this) {
            self::Business => 'مصاريف عمل',
            self::Personal => 'مصاريف شخصية',
            self::Salary => 'رواتب',
            self::Commission => 'كومشن',
            self::Custom => 'مصروف مخصص',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Business => 'warning',
            self::Personal => 'info',
            self::Salary => 'success',
            self::Commission => 'danger',
            self::Custom => 'gray',
        };
    }
}
