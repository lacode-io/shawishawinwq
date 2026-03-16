<?php

namespace App\Enums;

enum PaymentType: string
{
    case Installment = 'installment';
    case LumpSum = 'lump_sum';

    public function label(): string
    {
        return match ($this) {
            self::Installment => 'أقساط شهرية',
            self::LumpSum => 'دفعة واحدة',
        };
    }
}
