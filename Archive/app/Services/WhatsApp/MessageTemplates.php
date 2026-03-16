<?php

namespace App\Services\WhatsApp;

use App\Models\Customer;
use App\Models\Investor;
use App\Models\Setting;
use Illuminate\Support\Number;

class MessageTemplates
{
    /**
     * تذكير بموعد القسط
     */
    public static function paymentDueReminder(Customer $customer): string
    {
        $siteName = Setting::instance()->site_name ?? 'شوي شوي';

        return "مرحباً {$customer->full_name}،\n"
            ."هذا تذكير من {$siteName} بخصوص قسطك المستحق.\n\n"
            .'تاريخ الاستحقاق: '.$customer->next_due_date?->format('Y/m/d')."\n"
            .'مبلغ القسط: '.Number::iqd($customer->monthly_installment_amount)."\n"
            .'المدفوع حتى الآن: '.Number::iqd($customer->total_paid)."\n"
            .'المتبقي: '.Number::iqd($customer->remaining_balance)."\n\n"
            .'شكراً لتعاملك معنا.';
    }

    /**
     * تأكيد استلام تسديد
     */
    public static function paymentReceivedConfirmation(Customer $customer, int $amountPaid): string
    {
        $siteName = Setting::instance()->site_name ?? 'شوي شوي';

        return "مرحباً {$customer->full_name}،\n"
            ."تم استلام تسديدك بنجاح من {$siteName}.\n\n"
            .'المبلغ المستلم: '.Number::iqd($amountPaid)."\n"
            .'المبلغ المدفوع الكلي: '.Number::iqd($customer->total_paid)."\n"
            .'المتبقي: '.Number::iqd($customer->remaining_balance)."\n\n"
            .'شكراً لك.';
    }

    /**
     * تذكير/كشف حساب للمستثمر
     */
    public static function investorSummary(Investor $investor): string
    {
        $siteName = Setting::instance()->site_name ?? 'شوي شوي';

        return "مرحباً {$investor->full_name}،\n"
            ."هذا ملخص استثمارك من {$siteName}.\n\n"
            .'مبلغ الاستثمار: '.Number::iqd($investor->amount_invested)."\n"
            .'المبلغ المستحق: '.Number::iqd($investor->total_due)."\n"
            .'المدفوع: '.Number::iqd($investor->total_paid_out)."\n"
            .'المتبقي: '.Number::iqd($investor->remaining_balance)."\n"
            .'نسبة التقدم: '.$investor->progress_percent."%\n\n"
            .'شكراً لثقتك.';
    }

    /**
     * تنبيه داخلي للإدارة عن زبون متأخر
     */
    public static function latePayerAdminAlert(Customer $customer): string
    {
        return "⚠️ تنبيه: زبون متأخر\n\n"
            .'الاسم: '.$customer->full_name."\n"
            .'الهاتف: '.$customer->phone."\n"
            .'متأخر بـ '.$customer->months_late." شهر\n"
            .'القسط الشهري: '.Number::iqd($customer->monthly_installment_amount)."\n"
            .'المتبقي: '.Number::iqd($customer->remaining_balance);
    }
}
