<?php

namespace App\Enums;

enum PaymentMethod: string
{
    case Cash = 'cash';
    case Transfer = 'transfer';

    public function label(): string
    {
        return match ($this) {
            self::Cash => 'نقدي',
            self::Transfer => 'حوالة',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Cash => 'success',
            self::Transfer => 'info',
        };
    }
}
