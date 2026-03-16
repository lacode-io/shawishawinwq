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
        .header .date-range {
            font-size: 12px;
            color: #374151;
            margin-top: 5px;
            font-weight: bold;
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
        table.data {
            width: 100%;
            border-collapse: collapse;
        }
        table.data td {
            padding: 8px 12px;
            border-bottom: 1px solid #f3f4f6;
        }
        table.data .label {
            font-weight: bold;
            color: #374151;
            width: 50%;
        }
        table.data .value {
            text-align: left;
            font-weight: bold;
        }
        .badge-success { color: #059669; }
        .badge-danger { color: #dc2626; }
        .badge-warning { color: #d97706; }
        .badge-info { color: #0284c7; }
        .equation-box {
            margin: 20px 0;
            border: 2px solid {{ $settings->primary_color ?? '#0ea5e9' }};
            border-radius: 8px;
            padding: 15px;
            text-align: center;
        }
        .equation-box .title {
            font-size: 13px;
            font-weight: bold;
            color: #374151;
            margin-bottom: 8px;
        }
        .equation-box .formula {
            font-size: 11px;
            color: #6b7280;
            margin-bottom: 5px;
        }
        .equation-box .result {
            font-size: 18px;
            font-weight: bold;
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
            padding: 6px 10px;
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
    </style>
</head>
<body>

@php $iqd = fn(int $v) => \Illuminate\Support\Number::iqd($v); @endphp

<div class="header">
    <h1>{{ $settings->site_name ?? 'شوي شوي' }}</h1>
    <p>التقرير المالي</p>
    <div class="date-range">من {{ $from->format('Y/m/d') }} إلى {{ $to->format('Y/m/d') }}</div>
</div>

{{-- المبيعات --}}
<div class="section">
    <div class="section-title">المبيعات</div>
    <table class="data">
        <tr>
            <td class="label">عدد المبيعات:</td>
            <td class="value">{{ $summary['sales_count'] }} صفقة</td>
        </tr>
        <tr>
            <td class="label">إجمالي المبيعات:</td>
            <td class="value badge-info">{{ $iqd($summary['total_sales']) }}</td>
        </tr>
        <tr>
            <td class="label">إجمالي رأس المال:</td>
            <td class="value">{{ $iqd($summary['total_cost']) }}</td>
        </tr>
        <tr>
            <td class="label">الربح الإجمالي:</td>
            <td class="value badge-success">{{ $iqd($summary['gross_profit']) }}</td>
        </tr>
    </table>
</div>

{{-- التسديدات المستلمة --}}
<div class="section">
    <div class="section-title">التسديدات المستلمة</div>
    <table class="data">
        <tr>
            <td class="label">إجمالي التسديدات:</td>
            <td class="value badge-success">{{ $iqd($summary['payments_received']) }}</td>
        </tr>
        <tr>
            <td class="label">تسديدات نقدية:</td>
            <td class="value">{{ $iqd($summary['cash_payments']) }}</td>
        </tr>
        <tr>
            <td class="label">تسديدات حوالة:</td>
            <td class="value">{{ $iqd($summary['transfer_payments']) }}</td>
        </tr>
    </table>
</div>

{{-- المصاريف --}}
<div class="section">
    <div class="section-title">المصاريف</div>
    <table class="data">
        <tr>
            <td class="label">مصاريف العمل:</td>
            <td class="value badge-warning">{{ $iqd($summary['business_expenses']) }}</td>
        </tr>
        <tr>
            <td class="label">مصاريف شخصية:</td>
            <td class="value badge-warning">{{ $iqd($summary['personal_expenses']) }}</td>
        </tr>
        <tr>
            <td class="label">إجمالي المصاريف:</td>
            <td class="value badge-danger">{{ $iqd($summary['total_expenses']) }}</td>
        </tr>
    </table>
</div>

{{-- دفعات المستثمرين --}}
<div class="section">
    <div class="section-title">دفعات المستثمرين</div>
    <table class="data">
        <tr>
            <td class="label">إجمالي دفعات المستثمرين:</td>
            <td class="value badge-danger">{{ $iqd($summary['investor_payouts']) }}</td>
        </tr>
    </table>
</div>

{{-- معادلة رأس المال الفعلي --}}
<div class="equation-box">
    <div class="title">معادلة رأس المال الفعلي (حتى الآن)</div>
    <div class="formula">(رأس المال بالأقساط + رأس المال الكاش) − مستحقات المستثمرين</div>
    <div class="formula">({{ $iqd($finance->capitalInInstallments()) }} + {{ $iqd($finance->cashCapital()) }}) − {{ $iqd($finance->investorsDueTotal()) }}</div>
    <div class="result {{ $finance->effectiveCapital() >= 0 ? 'badge-success' : 'badge-danger' }}">
        = {{ $iqd($finance->effectiveCapital()) }}
    </div>
</div>

{{-- صافي الربح --}}
<div class="summary-box">
    <table>
        <tr>
            <td class="total-label">الربح الإجمالي للفترة:</td>
            <td class="total-value badge-success">{{ $iqd($summary['gross_profit']) }}</td>
        </tr>
        <tr>
            <td class="total-label">مصاريف العمل:</td>
            <td class="total-value badge-danger">- {{ $iqd($summary['business_expenses']) }}</td>
        </tr>
        <tr>
            <td class="total-label" style="font-size: 15px;">صافي الربح للفترة:</td>
            <td class="total-value {{ $summary['net_profit'] >= 0 ? 'badge-success' : 'badge-danger' }}" style="font-size: 15px;">{{ $iqd($summary['net_profit']) }}</td>
        </tr>
    </table>
</div>

<div class="footer">
    تم إنشاء هذا التقرير بتاريخ {{ now()->format('Y/m/d h:i A') }} — {{ $settings->site_name ?? 'شوي شوي' }}
</div>

</body>
</html>
