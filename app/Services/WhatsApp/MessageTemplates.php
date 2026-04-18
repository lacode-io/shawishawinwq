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
     * تذكير قبل يوم من موعد القسط
     */
    public static function paymentDueTomorrow(Customer $customer): string
    {
        $siteName = Setting::instance()->site_name ?? 'شوي شوي';

        return "مرحباً {$customer->full_name}،\n"
            ."تذكير من {$siteName}: قسطك مستحق غداً.\n\n"
            .'تاريخ الاستحقاق: '.$customer->next_due_date?->format('Y/m/d')."\n"
            .'مبلغ القسط: '.Number::iqd($customer->monthly_installment_amount)."\n"
            .'المتبقي الكلي: '.Number::iqd($customer->remaining_balance)."\n\n"
            .'يرجى تجهيز المبلغ. شكراً لك.';
    }

    /**
     * تذكير بيوم التسديد
     */
    public static function paymentDueToday(Customer $customer): string
    {
        $siteName = Setting::instance()->site_name ?? 'شوي شوي';

        return "مرحباً {$customer->full_name}،\n"
            ."اليوم موعد تسديد قسطك مع {$siteName}.\n\n"
            .'مبلغ القسط: '.Number::iqd($customer->monthly_installment_amount)."\n"
            .'المدفوع حتى الآن: '.Number::iqd($customer->total_paid)."\n"
            .'المتبقي الكلي: '.Number::iqd($customer->remaining_balance)."\n\n"
            .'نرجو التسديد اليوم. شكراً لك.';
    }

    /**
     * تنبيه يومي لمن لم يسدد بعد
     */
    public static function overduePayment(Customer $customer): string
    {
        $siteName = Setting::instance()->site_name ?? 'شوي شوي';
        $monthlyInstallment = (int) $customer->monthly_installment_amount;
        $dueDate = $customer->next_due_date;
        $daysLate = $dueDate ? max(0, (int) $dueDate->startOfDay()->diffInDays(now()->startOfDay(), false)) : 0;
        $monthsLate = max(1, (int) $customer->months_late ?: 1);
        $amountOverdue = $monthlyInstallment * $monthsLate;

        return "مرحباً {$customer->full_name}،\n"
            ."تنبيه من {$siteName}: لديك قسط متأخر لم يتم تسديده.\n\n"
            .'تاريخ الاستحقاق: '.($dueDate?->format('Y/m/d') ?? '-')."\n"
            .'عدد أيام التأخير: '.$daysLate."\n"
            .'المبلغ المستحق الآن: '.Number::iqd($amountOverdue)."\n"
            .'المتبقي الكلي: '.Number::iqd($customer->remaining_balance)."\n\n"
            .'نرجو التسديد في أقرب وقت.';
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
