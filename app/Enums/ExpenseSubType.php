<?php

namespace App\Enums;

enum ExpenseSubType: string
{
    case Haider = 'haider';
    case Thaqr = 'thaqr';
    case Shared = 'shared';

    public function label(): string
    {
        return match ($this) {
            self::Haider => 'حيدر',
            self::Thaqr => 'ذو الفقار',
            self::Shared => 'مشتركه',
        };
    }
}
