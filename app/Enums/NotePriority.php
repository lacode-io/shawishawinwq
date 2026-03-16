<?php

namespace App\Enums;

enum NotePriority: string
{
    case Low = 'low';
    case Normal = 'normal';
    case High = 'high';

    public function label(): string
    {
        return match ($this) {
            self::Low => 'منخفض',
            self::Normal => 'عادي',
            self::High => 'مهم',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Low => 'gray',
            self::Normal => 'info',
            self::High => 'danger',
        };
    }
}
