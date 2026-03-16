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
        table.payouts {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }
        table.payouts th {
            background-color: {{ $settings->primary_color ?? '#0ea5e9' }};
            color: #ffffff;
            padding: 8px 10px;
            font-size: 11px;
            text-align: right;
        }
        table.payouts td {
            padding: 7px 10px;
            border-bottom: 1px solid #e5e7eb;
            font-size: 11px;
            text-align: right;
        }
        table.payouts tr:nth-child(even) {
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
        .badge-warning { color: #d97706; font-weight: bold; }
        .progress-section {
            margin-top: 15px;
            padding: 12px;
            background-color: #f9fafb;
            border-radius: 6px;
            border: 1px solid #e5e7eb;
        }
    </style>
</head>
<body>

<div class="header">
    <h1>{{ $settings->site_name ?? 'شوي شوي' }}</h1>
    <p>كشف حساب المستثمر</p>
</div>

{{-- معلومات المستثمر --}}
<div class="section">
    <div class="section-title">معلومات المستثمر</div>
    <table class="info-grid">
        <tr>
            <td class="label">الاسم:</td>
            <td class="value">{{ $investor->full_name }}</td>
            <td class="label">رقم الهاتف:</td>
            <td class="value">{{ $investor->phone ?? '-' }}</td>
        </tr>
    </table>
</div>

{{-- تفاصيل الاستثمار --}}
<div class="section">
    <div class="section-title">تفاصيل الاستثمار</div>
    <table class="info-grid">
        <tr>
            <td class="label">مبلغ الاستثمار:</td>
            <td class="value">{{ \Illuminate\Support\Number::iqd($investor->amount_invested) }}</td>
            <td class="label">نسبة الربح:</td>
            <td class="value">{{ $investor->profit_percent_total }}%</td>
        </tr>
        <tr>
            <td class="label">مبلغ الربح:</td>
            <td class="value">{{ \Illuminate\Support\Number::iqd($investor->total_profit_amount) }}</td>
            <td class="label">المبلغ المستحق:</td>
            <td class="value" style="font-weight: bold;">{{ \Illuminate\Support\Number::iqd($investor->total_due) }}</td>
        </tr>
        <tr>
            <td class="label">مدة الاستثمار:</td>
            <td class="value">{{ $investor->investment_months }} شهر</td>
            <td class="label">الهدف الشهري:</td>
            <td class="value">{{ $investor->monthly_target_amount ? \Illuminate\Support\Number::iqd($investor->monthly_target_amount) : '-' }}</td>
        </tr>
        <tr>
            <td class="label">تاريخ البدء:</td>
            <td class="value">{{ $investor->start_date->format('Y/m/d') }}</td>
            <td class="label">تاريخ التسديد:</td>
            <td class="value">{{ $investor->payout_due_date->format('Y/m/d') }}</td>
        </tr>
    </table>
</div>

{{-- تقدم السداد --}}
<div class="section">
    <div class="section-title">تقدم السداد</div>
    <div class="progress-section">
        <table class="info-grid">
            <tr>
                <td class="label">الأشهر المنقضية:</td>
                <td class="value">{{ $investor->elapsed_months }} / {{ $investor->investment_months }} شهر</td>
                <td class="label">نسبة التقدم:</td>
                <td class="value {{ $investor->progress_percent >= 100 ? 'badge-success' : ($investor->is_behind_target ? 'badge-danger' : 'badge-warning') }}">{{ $investor->progress_percent }}%</td>
            </tr>
            <tr>
                <td class="label">المتوقع دفعه حتى الآن:</td>
                <td class="value">{{ \Illuminate\Support\Number::iqd($investor->expected_payout_so_far) }}</td>
                <td class="label">الحالة:</td>
                <td class="value {{ $investor->is_behind_target ? 'badge-danger' : 'badge-success' }}">{{ $investor->is_behind_target ? 'متأخر عن الهدف' : 'ضمن الهدف' }}</td>
            </tr>
            @if($investor->target_gap > 0)
            <tr>
                <td class="label">الفجوة عن الهدف:</td>
                <td class="value badge-danger">{{ \Illuminate\Support\Number::iqd($investor->target_gap) }}</td>
                <td></td>
                <td></td>
            </tr>
            @endif
        </table>
    </div>
</div>

{{-- سجل الدفعات --}}
<div class="section">
    <div class="section-title">سجل الدفعات ({{ $investor->payouts->count() }} دفعة)</div>
    @if($investor->payouts->count() > 0)
        <table class="payouts">
            <thead>
                <tr>
                    <th>#</th>
                    <th>تاريخ الدفع</th>
                    <th>المبلغ</th>
                    <th>ملاحظات</th>
                </tr>
            </thead>
            <tbody>
                @php $runningTotal = 0; @endphp
                @foreach($investor->payouts->sortBy('paid_at') as $index => $payout)
                    @php $runningTotal += $payout->amount; @endphp
                    <tr>
                        <td>{{ $index + 1 }}</td>
                        <td>{{ $payout->paid_at->format('Y/m/d') }}</td>
                        <td>{{ \Illuminate\Support\Number::iqd($payout->amount) }}</td>
                        <td>{{ $payout->notes ?? '-' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @else
        <p style="text-align: center; color: #9ca3af;">لا توجد دفعات حتى الآن</p>
    @endif
</div>

{{-- ملخص الحساب --}}
<div class="summary-box">
    <table>
        <tr>
            <td class="total-label">مبلغ الاستثمار:</td>
            <td class="total-value">{{ \Illuminate\Support\Number::iqd($investor->amount_invested) }}</td>
        </tr>
        <tr>
            <td class="total-label">مبلغ الربح:</td>
            <td class="total-value badge-success">{{ \Illuminate\Support\Number::iqd($investor->total_profit_amount) }}</td>
        </tr>
        <tr>
            <td class="total-label">المبلغ المستحق الكلي:</td>
            <td class="total-value" style="font-weight: bold;">{{ \Illuminate\Support\Number::iqd($investor->total_due) }}</td>
        </tr>
        <tr>
            <td class="total-label">المبلغ المدفوع:</td>
            <td class="total-value badge-success">{{ \Illuminate\Support\Number::iqd($investor->total_paid_out) }}</td>
        </tr>
        <tr>
            <td class="total-label">المبلغ المتبقي:</td>
            <td class="total-value {{ $investor->remaining_balance > 0 ? 'badge-danger' : 'badge-success' }}">{{ \Illuminate\Support\Number::iqd($investor->remaining_balance) }}</td>
        </tr>
        <tr>
            <td class="total-label">الحالة:</td>
            <td class="total-value">{{ $investor->status->label() }}</td>
        </tr>
    </table>
</div>

<div class="footer">
    تم إنشاء هذا الكشف بتاريخ {{ now()->format('Y/m/d h:i A') }} — {{ $settings->site_name ?? 'شوي شوي' }}
</div>

</body>
</html>
