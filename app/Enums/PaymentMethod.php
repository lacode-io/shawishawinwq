<?php

namespace App\Enums;

enum PaymentMethod: string
{
    case Cash = 'cash';
    case Transfer = 'transfer';
    case NoPayment = 'no_payment';

    public function label(): string
    {
        return match ($this) {
            self::Cash => 'نقدي',
            self::Transfer => 'حوالة',
            self::NoPayment => 'لا يوجد تسديد',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Cash => 'success',
            self::Transfer => 'info',
            self::NoPayment => 'gray',
        };
    }
}
