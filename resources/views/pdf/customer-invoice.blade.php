<!DOCTYPE html>
<html dir="rtl" lang="ar">
<head>
    <meta charset="UTF-8">
    <style>
        * {
            font-family: 'ibmplexsansarabic', sans-serif;
            direction: rtl;
        }
        body {
            font-size: 12px;
            color: #1f2937;
            line-height: 1.6;
        }
        .header {
            text-align: center;
            margin-bottom: 25px;
            border-bottom: 3px solid {{ $settings->primary_color ?? '#0ea5e9' }};
            padding-bottom: 15px;
        }
        .header h1 {
            font-size: 24px;
            color: {{ $settings->primary_color ?? '#0ea5e9' }};
            margin: 0 0 5px;
        }
        .header p {
            font-size: 14px;
            color: #6b7280;
            margin: 0;
        }
        .section {
            margin-bottom: 20px;
        }
        .section-title {
            font-size: 14px;
            font-weight: bold;
            color: {{ $settings->primary_color ?? '#0ea5e9' }};
            border-bottom: 1px solid #e5e7eb;
            padding-bottom: 5px;
            margin-bottom: 10px;
        }
        .info-grid {
            width: 100%;
            border-collapse: collapse;
        }
        .info-grid td {
            padding: 6px 10px;
            vertical-align: top;
        }
        .info-grid .label {
            font-weight: bold;
            color: #374151;
            width: 35%;
        }
        .info-grid .value {
            color: #1f2937;
        }
        table.payments {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }
        table.payments th {
            background-color: {{ $settings->primary_color ?? '#0ea5e9' }};
            color: #ffffff;
            padding: 8px 10px;
            font-size: 11px;
            text-align: right;
        }
        table.payments td {
            padding: 7px 10px;
            border-bottom: 1px solid #e5e7eb;
            font-size: 11px;
            text-align: right;
        }
        table.payments tr:nth-child(even) {
            background-color: #f9fafb;
        }
        .summary-box {
            margin-top: 20px;
            border: 2px solid {{ $settings->primary_color ?? '#0ea5e9' }};
            border-radius: 8px;
            padding: 15px;
        }
        .summary-box table {
            width: 100%;
            border-collapse: collapse;
        }
        .summary-box td {
            padding: 5px 10px;
        }
        .summary-box .total-label {
            font-weight: bold;
            font-size: 13px;
        }
        .summary-box .total-value {
            font-weight: bold;
            font-size: 13px;
            text-align: left;
        }
        .footer {
            margin-top: 30px;
            text-align: center;
            font-size: 10px;
            color: #9ca3af;
            border-top: 1px solid #e5e7eb;
            padding-top: 10px;
        }
        .badge-success { color: #059669; font-weight: bold; }
        .badge-danger { color: #dc2626; font-weight: bold; }
    </style>
</head>
<body>

<div class="header">
    <h1>{{ $settings->site_name ?? 'شوي شوي' }}</h1>
    <p>كشف حساب الزبون</p>
</div>

{{-- معلومات الزبون --}}
<div class="section">
    <div class="section-title">معلومات الزبون</div>
    <table class="info-grid">
        <tr>
            <td class="label">الاسم الكامل:</td>
            <td class="value">{{ $customer->full_name }}</td>
            <td class="label">رقم الهاتف:</td>
            <td class="value">{{ $customer->phone }}</td>
        </tr>
        <tr>
            <td class="label">العنوان:</td>
            <td class="value">{{ $customer->address ?? '-' }}</td>
            <td class="label">الكفيل:</td>
            <td class="value">{{ $customer->guarantor_name ?? '-' }} {{ $customer->guarantor_phone ? "({$customer->guarantor_phone})" : '' }}</td>
        </tr>
    </table>
</div>

{{-- تفاصيل المنتج --}}
<div class="section">
    <div class="section-title">تفاصيل المنتج</div>
    <table class="info-grid">
        <tr>
            <td class="label">نوع المنتج:</td>
            <td class="value">{{ $customer->product_type }}</td>
            <td class="label">رأس المال:</td>
            <td class="value">{{ $customer->product_cost_price ? \Illuminate\Support\Number::iqd($customer->product_cost_price) : '-' }}</td>
        </tr>
        <tr>
            <td class="label">السعر الإجمالي:</td>
            <td class="value">{{ \Illuminate\Support\Number::iqd($customer->product_sale_total) }}</td>
            <td class="label">تاريخ التسليم:</td>
            <td class="value">{{ $customer->delivery_date->format('Y/m/d') }}</td>
        </tr>
        <tr>
            <td class="label">المدة:</td>
            <td class="value">{{ $customer->duration_months }} شهر</td>
            <td class="label">القسط الشهري:</td>
            <td class="value">{{ \Illuminate\Support\Number::iqd($customer->monthly_installment_amount) }}</td>
        </tr>
    </table>
</div>

{{-- معلومات البطاقة --}}
@if($customer->card_number || $customer->card_code)
<div class="section">
    <div class="section-title">معلومات البطاقة</div>
    <table class="info-grid">
        <tr>
            <td class="label">رقم البطاقة:</td>
            <td class="value">{{ $customer->card_number ?? '-' }}</td>
            <td class="label">رمز البطاقة:</td>
            <td class="value">{{ $customer->card_code ?? '-' }}</td>
        </tr>
    </table>
</div>
@endif

{{-- سجل التسديدات --}}
<div class="section">
    <div class="section-title">سجل التسديدات ({{ $customer->payments->count() }} تسديد)</div>
    @if($customer->payments->count() > 0)
        <table class="payments">
            <thead>
                <tr>
                    <th>#</th>
                    <th>تاريخ الدفع</th>
                    <th>المبلغ</th>
                    <th>طريقة الدفع</th>
                    <th>استلمها</th>
                </tr>
            </thead>
            <tbody>
                @foreach($customer->payments->sortBy('paid_at') as $index => $payment)
                    <tr>
                        <td>{{ $index + 1 }}</td>
                        <td>{{ $payment->paid_at->format('Y/m/d') }}</td>
                        <td>{{ \Illuminate\Support\Number::iqd($payment->amount) }}</td>
                        <td>{{ $payment->payment_method->label() }}</td>
                        <td>{{ $payment->receivedBy?->name ?? '-' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @else
        <p style="text-align: center; color: #9ca3af;">لا توجد تسديدات حتى الآن</p>
    @endif
</div>

{{-- ملخص الحساب --}}
<div class="summary-box">
    <table>
        <tr>
            <td class="total-label">السعر الإجمالي:</td>
            <td class="total-value">{{ \Illuminate\Support\Number::iqd($customer->product_sale_total) }}</td>
        </tr>
        <tr>
            <td class="total-label">المبلغ المدفوع:</td>
            <td class="total-value badge-success">{{ \Illuminate\Support\Number::iqd($customer->total_paid) }}</td>
        </tr>
        <tr>
            <td class="total-label">المبلغ المتبقي:</td>
            <td class="total-value {{ $customer->remaining_balance > 0 ? 'badge-danger' : 'badge-success' }}">{{ \Illuminate\Support\Number::iqd($customer->remaining_balance) }}</td>
        </tr>
        <tr>
            <td class="total-label">الأقساط المدفوعة:</td>
            <td class="total-value">{{ $customer->payments->count() }} / {{ $customer->duration_months }}</td>
        </tr>
        <tr>
            <td class="total-label">الحالة:</td>
            <td class="total-value">{{ $customer->status->label() }}</td>
        </tr>
    </table>
</div>

{{-- ملاحظات --}}
@if($customer->internal_notes)
<div class="section" style="margin-top: 20px;">
    <div class="section-title">ملاحظات</div>
    <p style="padding: 8px 10px; background-color: #f9fafb; border-radius: 4px; line-height: 1.8;">{{ $customer->internal_notes }}</p>
</div>
@endif

<div class="footer">
    تم إنشاء هذا الكشف بتاريخ {{ now()->format('Y/m/d h:i A') }} — {{ $settings->site_name ?? 'شوي شوي' }}
</div>

</body>
</html>
