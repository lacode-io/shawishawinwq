<?php

namespace App\Enums;

enum ExpenseType: string
{
    case Business = 'business';
    case Personal = 'personal';
    case Salary = 'salary';

    public function label(): string
    {
        return match ($this) {
            self::Business => 'مصاريف عمل',
            self::Personal => 'مصاريف شخصية',
            self::Salary => 'رواتب',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Business => 'warning',
            self::Personal => 'info',
            self::Salary => 'success',
        };
    }
}
