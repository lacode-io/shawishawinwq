<?php

namespace App\Enums;

enum NoteType: string
{
    case Note = 'note';
    case Inventory = 'inventory';

    public function label(): string
    {
        return match ($this) {
            self::Note => 'ملاحظة',
            self::Inventory => 'جرد',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Note => 'info',
            self::Inventory => 'warning',
        };
    }
}
