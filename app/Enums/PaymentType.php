<?php

namespace App\Enums;

enum PaymentType: string
{
    case Installment = 'installment';
    case LumpSum = 'lump_sum';
    case NoPayment = 'no_payment';
    case DurationBased = 'duration_based';

    public function label(): string
    {
        return match ($this) {
            self::Installment => 'أقساط شهرية',
            self::LumpSum => 'دفعة واحدة',
            self::NoPayment => 'لا يوجد تسديد',
            self::DurationBased => 'تسديد حسب مدة',
        };
    }
}
