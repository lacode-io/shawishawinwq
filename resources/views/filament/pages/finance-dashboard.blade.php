<x-filament-panels::page>
    @php
        $stats = $this->getStats();
        $iqd = fn(int $v) => \Illuminate\Support\Number::iqd($v);
        $hasAlerts = $stats['late_customers_count'] > 0 || $stats['behind_target_investors_count'] > 0;
        $coverage = $stats['investor_coverage'];
        $cashRegister = $stats['cash_register'];
        $investorDetails = $stats['investor_auto_payments'];
    @endphp

    {{-- ── تنبيهات ── --}}
    @if($hasAlerts)
    <div class="rounded-xl border border-amber-300 bg-amber-50 p-4 dark:border-amber-700 dark:bg-amber-900/20">
        <div class="flex items-center gap-2 mb-3">
            <x-heroicon-o-exclamation-triangle class="h-5 w-5 text-amber-600 dark:text-amber-400" />
            <h3 class="text-sm font-bold text-amber-800 dark:text-amber-300">تنبيهات</h3>
        </div>
        <div class="flex flex-wrap gap-4">
            @if($stats['late_customers_count'] > 0)
            <a href="{{ \App\Filament\Resources\CustomerResource::getUrl('index') }}"
               class="flex items-center gap-2 text-sm text-amber-700 dark:text-amber-400 hover:underline">
                <span class="inline-flex h-7 min-w-[1.75rem] items-center justify-center rounded-full bg-red-100 px-1.5 text-xs font-bold text-red-700 dark:bg-red-900/40 dark:text-red-400">
                    {{ $stats['late_customers_count'] }}
                </span>
                زبائن متأخرين عن الدفع
            </a>
            @endif
            @if($stats['behind_target_investors_count'] > 0)
            <a href="{{ \App\Filament\Resources\InvestorResource::getUrl('index') }}"
               class="flex items-center gap-2 text-sm text-amber-700 dark:text-amber-400 hover:underline">
                <span class="inline-flex h-7 min-w-[1.75rem] items-center justify-center rounded-full bg-orange-100 px-1.5 text-xs font-bold text-orange-700 dark:bg-orange-900/40 dark:text-orange-400">
                    {{ $stats['behind_target_investors_count'] }}
                </span>
                مستثمرين متأخرين عن الهدف
            </a>
            @endif
        </div>
    </div>
    @endif

    {{-- ══════════════════════════════════════════════ --}}
    {{-- ── القسم الأول: رأس المال ── --}}
    {{-- ══════════════════════════════════════════════ --}}

    {{-- ── معادلة رأس المال الفعلي ── --}}
    <div class="rounded-xl border border-gray-200 bg-white p-6 dark:border-gray-700 dark:bg-gray-900">
        <h3 class="mb-4 text-lg font-bold text-gray-900 dark:text-white">
            <x-heroicon-o-building-library class="inline h-5 w-5" />
            معادلة رأس المال الفعلي
        </h3>
        <div class="flex flex-wrap items-center justify-center gap-3 text-center text-sm">
            <div class="rounded-lg bg-blue-50 px-4 py-3 dark:bg-blue-900/20">
                <div class="text-xs text-gray-500 dark:text-gray-400">الاقساط الغير مدفوعة</div>
                <div class="text-lg font-bold text-blue-600 dark:text-blue-400">{{ $iqd($stats['capital_installments']) }}</div>
            </div>
            <span class="text-2xl font-bold text-gray-400">+</span>
            <div class="rounded-lg bg-green-50 px-4 py-3 dark:bg-green-900/20">
                <div class="text-xs text-gray-500 dark:text-gray-400">رأس المال الكاش</div>
                <div class="text-lg font-bold text-green-600 dark:text-green-400">{{ $iqd($stats['cash_capital']) }}</div>
            </div>
            <span class="text-2xl font-bold text-gray-400">−</span>
            <div class="rounded-lg bg-red-50 px-4 py-3 dark:bg-red-900/20">
                <div class="text-xs text-gray-500 dark:text-gray-400">مستحقات المستثمرين (الافتراضيه)</div>
                <div class="text-lg font-bold text-red-600 dark:text-red-400">{{ $iqd($stats['investors_due']) }}</div>
            </div>
            <span class="text-2xl font-bold text-gray-400">=</span>
            <div class="rounded-lg border-2 {{ $stats['effective_capital'] >= 0 ? 'border-green-500 bg-green-50 dark:bg-green-900/20' : 'border-red-500 bg-red-50 dark:bg-red-900/20' }} px-6 py-3">
                <div class="text-xs text-gray-500 dark:text-gray-400">صافي الربح الاجمالي</div>
                <div class="text-xl font-bold {{ $stats['effective_capital'] >= 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">{{ $iqd($stats['effective_capital']) }}</div>
            </div>
        </div>
    </div>

    {{-- ── بطاقات رأس المال ── --}}
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <x-filament::section>
            <div class="flex items-center gap-3">
                <div class="rounded-lg bg-primary-50 p-2 dark:bg-primary-900/20">
                    <x-heroicon-o-building-library class="h-6 w-6 text-primary-600 dark:text-primary-400" />
                </div>
                <div>
                    <div class="text-sm text-gray-500 dark:text-gray-400">مبالغ مشتريات الزبائن الكلية</div>
                    <div class="text-xl font-bold text-gray-900 dark:text-white">{{ $iqd($stats['total_capital']) }}</div>
                </div>
            </div>
        </x-filament::section>

        <x-filament::section>
            <div class="flex items-center gap-3">
                <div class="rounded-lg bg-blue-50 p-2 dark:bg-blue-900/20">
                    <x-heroicon-o-clock class="h-6 w-6 text-blue-600 dark:text-blue-400" />
                </div>
                <div>
                    <div class="text-sm text-gray-500 dark:text-gray-400">الاقساط الغير مدفوعة</div>
                    <div class="text-xl font-bold text-blue-600 dark:text-blue-400">{{ $iqd($stats['capital_installments']) }}</div>
                    <div class="text-xs text-gray-400 dark:text-gray-500">المتبقي غير المدفوع</div>
                </div>
            </div>
        </x-filament::section>

        <x-filament::section>
            <div class="flex items-center gap-3">
                <div class="rounded-lg bg-green-50 p-2 dark:bg-green-900/20">
                    <x-heroicon-o-banknotes class="h-6 w-6 text-green-600 dark:text-green-400" />
                </div>
                <div>
                    <div class="text-sm text-gray-500 dark:text-gray-400">رأس المال الكاش</div>
                    <div class="text-xl font-bold {{ $stats['cash_capital'] >= 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">{{ $iqd($stats['cash_capital']) }}</div>
                </div>
            </div>
        </x-filament::section>

        <x-filament::section>
            <div class="flex items-center gap-3">
                <div class="rounded-lg bg-red-50 p-2 dark:bg-red-900/20">
                    <x-heroicon-o-user-group class="h-6 w-6 text-red-600 dark:text-red-400" />
                </div>
                <div>
                    <div class="text-sm text-gray-500 dark:text-gray-400">مستحقات المستثمرين (الافتراضيه)</div>
                    <div class="text-xl font-bold text-red-600 dark:text-red-400">{{ $iqd($stats['investors_due']) }}</div>
                </div>
            </div>
        </x-filament::section>
    </div>

    {{-- ── تفاصيل رأس المال الكاش (كيف يتحسب) ── --}}
    <div class="rounded-xl border border-gray-200 bg-white p-6 dark:border-gray-700 dark:bg-gray-900">
        <h3 class="mb-4 text-lg font-bold text-gray-900 dark:text-white">
            <x-heroicon-o-banknotes class="inline h-5 w-5" />
            تفاصيل رأس المال الكاش
        </h3>
        <p class="text-xs text-gray-500 dark:text-gray-400 mb-4">رأس المال الكاش = المبلغ المدخل + تسديدات الزبائن − المصاريف − دفعات المستثمرين (الفعليه)</p>
        <div class="flex flex-wrap items-center justify-center gap-3 text-center text-sm">
            <div class="rounded-lg bg-gray-50 px-4 py-3 dark:bg-gray-800">
                <div class="text-xs text-gray-500 dark:text-gray-400">المبلغ المدخل</div>
                <div class="text-lg font-bold text-gray-700 dark:text-gray-300">{{ $iqd($stats['manual_cash_capital']) }}</div>
            </div>
            <span class="text-2xl font-bold text-gray-400">+</span>
            <div class="rounded-lg bg-green-50 px-4 py-3 dark:bg-green-900/20">
                <div class="text-xs text-gray-500 dark:text-gray-400">تسديدات الزبائن</div>
                <div class="text-lg font-bold text-green-600 dark:text-green-400">{{ $iqd($stats['total_payments_in']) }}</div>
            </div>
            <span class="text-2xl font-bold text-gray-400">−</span>
            <div class="rounded-lg bg-orange-50 px-4 py-3 dark:bg-orange-900/20">
                <div class="text-xs text-gray-500 dark:text-gray-400">المصاريف الكلية</div>
                <div class="text-lg font-bold text-orange-600 dark:text-orange-400">{{ $iqd($stats['total_expenses_all']) }}</div>
            </div>
            <span class="text-2xl font-bold text-gray-400">−</span>
            <div class="rounded-lg bg-red-50 px-4 py-3 dark:bg-red-900/20">
                <div class="text-xs text-gray-500 dark:text-gray-400">دفعات المستثمرين (الفعليه)</div>
                <div class="text-lg font-bold text-red-600 dark:text-red-400">{{ $iqd($stats['total_investor_payouts_all']) }}</div>
            </div>
            <span class="text-2xl font-bold text-gray-400">=</span>
            <div class="rounded-lg border-2 {{ $stats['cash_capital'] >= 0 ? 'border-green-500 bg-green-50 dark:bg-green-900/20' : 'border-red-500 bg-red-50 dark:bg-red-900/20' }} px-6 py-3">
                <div class="text-xs text-gray-500 dark:text-gray-400">رأس المال الكاش</div>
                <div class="text-xl font-bold {{ $stats['cash_capital'] >= 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">{{ $iqd($stats['cash_capital']) }}</div>
            </div>
        </div>
    </div>

    {{-- ══════════════════════════════════════════════ --}}
    {{-- ── القسم الثاني: القاصة ── --}}
    {{-- ══════════════════════════════════════════════ --}}

    <div class="rounded-xl border-2 {{ $cashRegister >= 0 ? 'border-emerald-300 dark:border-emerald-700' : 'border-red-300 dark:border-red-700' }} bg-white p-6 dark:bg-gray-900">
        <h3 class="mb-1 text-lg font-bold text-gray-900 dark:text-white">
            <x-heroicon-o-inbox-stack class="inline h-5 w-5" />
            القاصة
        </h3>
        <p class="text-xs text-gray-500 dark:text-gray-400 mb-4">الفرق بين الأرباح المحققة من المبيعات ومستحقات المستثمرين (الافتراضيه) المتراكمة - الفائض يُحفظ والعجز يُسحب تلقائياً</p>

        {{-- المعادلة --}}
        <div class="flex flex-wrap items-center justify-center gap-3 text-center text-sm mb-5">
            <div class="rounded-lg bg-green-50 px-5 py-3 dark:bg-green-900/20">
                <div class="text-xs text-gray-500 dark:text-gray-400">أرباح محققة من المبيعات</div>
                <div class="text-xl font-bold text-green-600 dark:text-green-400">{{ $iqd($stats['total_profit_earned']) }}</div>
            </div>
            <span class="text-2xl font-bold text-gray-400">−</span>
            <div class="rounded-lg bg-orange-50 px-5 py-3 dark:bg-orange-900/20">
                <div class="text-xs text-gray-500 dark:text-gray-400">مستحقات المستثمرين (الافتراضيه) المتراكمة</div>
                <div class="text-xl font-bold text-orange-600 dark:text-orange-400">{{ $iqd($stats['total_investor_dues_so_far']) }}</div>
            </div>
            <span class="text-2xl font-bold text-gray-400">=</span>
            <div class="rounded-lg border-2 {{ $cashRegister >= 0 ? 'border-emerald-500 bg-emerald-50 dark:bg-emerald-900/20' : 'border-red-500 bg-red-50 dark:bg-red-900/20' }} px-6 py-3">
                <div class="text-xs text-gray-500 dark:text-gray-400">رصيد القاصة</div>
                <div class="text-2xl font-bold {{ $cashRegister >= 0 ? 'text-emerald-600 dark:text-emerald-400' : 'text-red-600 dark:text-red-400' }}">
                    {{ $iqd(abs($cashRegister)) }}
                    <span class="text-sm">{{ $cashRegister >= 0 ? 'فائض' : 'عجز' }}</span>
                </div>
            </div>
        </div>

        @if($cashRegister < 0)
        <div class="rounded-lg border border-red-200 bg-red-50 p-3 dark:border-red-800 dark:bg-red-900/20">
            <div class="flex items-center gap-2">
                <x-heroicon-o-exclamation-triangle class="h-5 w-5 text-red-600 dark:text-red-400" />
                <span class="text-sm font-medium text-red-700 dark:text-red-400">
                    القاصة بالسالب! يوجد عجز بمقدار {{ $iqd(abs($cashRegister)) }} - المبيعات لا تغطي مستحقات المستثمرين (الافتراضيه)
                </span>
            </div>
        </div>
        @else
        <div class="rounded-lg border border-emerald-200 bg-emerald-50 p-3 dark:border-emerald-800 dark:bg-emerald-900/20">
            <div class="flex items-center gap-2">
                <x-heroicon-o-check-circle class="h-5 w-5 text-emerald-600 dark:text-emerald-400" />
                <span class="text-sm font-medium text-emerald-700 dark:text-emerald-400">
                    القاصة بحالة جيدة - يوجد فائض {{ $iqd($cashRegister) }}
                </span>
            </div>
        </div>
        @endif
    </div>

    {{-- ══════════════════════════════════════════════ --}}
    {{-- ── القسم الثالث: تفاصيل المستثمرين والتسديد التلقائي ── --}}
    {{-- ══════════════════════════════════════════════ --}}

    @if(count($investorDetails) > 0)
    <div class="rounded-xl border border-gray-200 bg-white p-6 dark:border-gray-700 dark:bg-gray-900">
        <h3 class="mb-1 text-lg font-bold text-gray-900 dark:text-white">
            <x-heroicon-o-users class="inline h-5 w-5" />
            المستثمرين - التسديد التلقائي من المبيعات
        </h3>
        <p class="text-xs text-gray-500 dark:text-gray-400 mb-4">
            التسديد يتم تلقائياً من فرق الربح (سعر البيع - سعر الشراء) لكل زبون
        </p>

        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-gray-200 dark:border-gray-700">
                        <th class="px-3 py-2 text-right text-xs font-bold text-gray-500 dark:text-gray-400">المستثمر</th>
                        <th class="px-3 py-2 text-right text-xs font-bold text-gray-500 dark:text-gray-400">مبلغ الاستثمار</th>
                        <th class="px-3 py-2 text-right text-xs font-bold text-gray-500 dark:text-gray-400">النسبة</th>
                        <th class="px-3 py-2 text-right text-xs font-bold text-gray-500 dark:text-gray-400">الأشهر</th>
                        <th class="px-3 py-2 text-right text-xs font-bold text-gray-500 dark:text-gray-400">القسط الشهري</th>
                        <th class="px-3 py-2 text-right text-xs font-bold text-gray-500 dark:text-gray-400">المستحق حتى الآن</th>
                        <th class="px-3 py-2 text-right text-xs font-bold text-gray-500 dark:text-gray-400">المدفوع فعلياً</th>
                        <th class="px-3 py-2 text-right text-xs font-bold text-gray-500 dark:text-gray-400">حصته من الربح/شهر</th>
                        <th class="px-3 py-2 text-right text-xs font-bold text-gray-500 dark:text-gray-400">الحالة</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($investorDetails as $inv)
                    <tr class="border-b border-gray-100 dark:border-gray-800 hover:bg-gray-50 dark:hover:bg-gray-800/50">
                        <td class="px-3 py-3 font-medium text-gray-900 dark:text-white">
                            <a href="{{ \App\Filament\Resources\InvestorResource::getUrl('view', ['record' => $inv['id']]) }}" class="hover:underline hover:text-primary-600">
                                {{ $inv['name'] }}
                            </a>
                        </td>
                        <td class="px-3 py-3 text-gray-700 dark:text-gray-300">{{ $iqd($inv['amount_invested']) }}</td>
                        <td class="px-3 py-3 text-gray-700 dark:text-gray-300">{{ $inv['profit_percent'] }}%</td>
                        <td class="px-3 py-3 text-gray-700 dark:text-gray-300">
                            <span class="text-primary-600 dark:text-primary-400 font-bold">{{ $inv['elapsed_months'] }}</span>
                            / {{ $inv['investment_months'] }}
                        </td>
                        <td class="px-3 py-3 font-medium text-gray-700 dark:text-gray-300">{{ $iqd($inv['monthly_target']) }}</td>
                        <td class="px-3 py-3 font-medium text-orange-600 dark:text-orange-400">{{ $iqd($inv['total_due_so_far']) }}</td>
                        <td class="px-3 py-3 font-medium text-green-600 dark:text-green-400">{{ $iqd($inv['total_paid']) }}</td>
                        <td class="px-3 py-3 font-medium text-blue-600 dark:text-blue-400">{{ $iqd($inv['monthly_from_profit']) }}</td>
                        <td class="px-3 py-3">
                            @if($inv['is_behind'])
                                <span class="inline-flex items-center gap-1 rounded-full bg-red-100 px-2.5 py-0.5 text-xs font-bold text-red-700 dark:bg-red-900/40 dark:text-red-400">
                                    <x-heroicon-o-exclamation-triangle class="h-3.5 w-3.5" />
                                    عجز {{ $iqd($inv['gap']) }}
                                </span>
                            @else
                                <span class="inline-flex items-center gap-1 rounded-full bg-green-100 px-2.5 py-0.5 text-xs font-bold text-green-700 dark:bg-green-900/40 dark:text-green-400">
                                    <x-heroicon-o-check-circle class="h-3.5 w-3.5" />
                                    مغطى
                                </span>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endif

    {{-- ── تغطية المستثمرين من أرباح الزبائن ── --}}
    @if($coverage['active_investor_count'] > 0)
    <div class="rounded-xl border-2 {{ $coverage['is_covered'] ? 'border-green-300 dark:border-green-700' : 'border-red-300 dark:border-red-700' }} bg-white p-6 dark:bg-gray-900">
        <h3 class="mb-1 text-lg font-bold text-gray-900 dark:text-white">
            <x-heroicon-o-scale class="inline h-5 w-5" />
            تغطية المستثمرين من أرباح الزبائن الشهرية
        </h3>
        <p class="text-xs text-gray-500 dark:text-gray-400 mb-4">ربح كل زبون (سعر البيع - سعر الشراء) ÷ عدد أشهره = ربحه الشهري</p>

        {{-- المعادلة --}}
        <div class="flex flex-wrap items-center justify-center gap-3 text-center text-sm mb-5">
            <div class="rounded-lg bg-green-50 px-5 py-3 dark:bg-green-900/20">
                <div class="text-xs text-gray-500 dark:text-gray-400">ربحي الشهري من {{ $coverage['active_customer_count'] }} زبون</div>
                <div class="text-xl font-bold text-green-600 dark:text-green-400">{{ $iqd($coverage['monthly_customer_profit']) }}</div>
            </div>
            <span class="text-2xl font-bold text-gray-400">−</span>
            <div class="rounded-lg bg-orange-50 px-5 py-3 dark:bg-orange-900/20">
                <div class="text-xs text-gray-500 dark:text-gray-400">أقساط {{ $coverage['active_investor_count'] }} مستثمر</div>
                <div class="text-xl font-bold text-orange-600 dark:text-orange-400">{{ $iqd($coverage['monthly_investor_target']) }}</div>
            </div>
            <span class="text-2xl font-bold text-gray-400">=</span>
            <div class="rounded-lg border-2 {{ $coverage['is_covered'] ? 'border-green-500 bg-green-50 dark:bg-green-900/20' : 'border-red-500 bg-red-50 dark:bg-red-900/20' }} px-6 py-3">
                <div class="text-xs text-gray-500 dark:text-gray-400">{{ $coverage['is_covered'] ? 'فائض → القاصة' : 'عجز → يُسحب من القاصة' }}</div>
                <div class="text-xl font-bold {{ $coverage['is_covered'] ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">
                    {{ $iqd(abs($coverage['gap'])) }}
                </div>
            </div>
        </div>

        {{-- نسبة التغطية --}}
        <div class="mb-4">
            <div class="flex items-center justify-between mb-1">
                <span class="text-sm text-gray-600 dark:text-gray-400">نسبة التغطية</span>
                <span class="text-sm font-bold {{ $coverage['coverage_percent'] >= 100 ? 'text-green-600 dark:text-green-400' : ($coverage['coverage_percent'] >= 50 ? 'text-yellow-600 dark:text-yellow-400' : 'text-red-600 dark:text-red-400') }}">
                    {{ $coverage['coverage_percent'] }}%
                </span>
            </div>
            <div class="w-full bg-gray-200 rounded-full h-3 dark:bg-gray-700">
                <div class="h-3 rounded-full transition-all {{ $coverage['coverage_percent'] >= 100 ? 'bg-green-500' : ($coverage['coverage_percent'] >= 50 ? 'bg-yellow-500' : 'bg-red-500') }}"
                     style="width: {{ min($coverage['coverage_percent'], 100) }}%"></div>
            </div>
        </div>

        @if(!$coverage['is_covered'])
        <div class="rounded-lg border border-red-200 bg-red-50 p-3 dark:border-red-800 dark:bg-red-900/20">
            <div class="flex items-center gap-2">
                <x-heroicon-o-exclamation-triangle class="h-5 w-5 text-red-600 dark:text-red-400" />
                <span class="text-sm font-medium text-red-700 dark:text-red-400">
                    تحتاج مبيعات إضافية بربح {{ $iqd(abs($coverage['gap'])) }} شهرياً لتغطية المستثمرين - العجز يُسحب من القاصة
                </span>
            </div>
        </div>
        @endif
    </div>
    @endif

    {{-- ══════════════════════════════════════════════ --}}
    {{-- ── القسم الرابع: المصاريف ── --}}
    {{-- ══════════════════════════════════════════════ --}}

    <x-filament::section>
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-3">
                <div class="rounded-lg bg-red-50 p-2 dark:bg-red-900/20">
                    <x-heroicon-o-receipt-percent class="h-6 w-6 text-red-600 dark:text-red-400" />
                </div>
                <div>
                    <div class="text-sm text-gray-500 dark:text-gray-400">إجمالي المصاريف (هذا الشهر)</div>
                    <div class="text-2xl font-bold text-red-600 dark:text-red-400">{{ $iqd($stats['monthly_total_expenses']) }}</div>
                    <div class="text-xs text-gray-400 dark:text-gray-500">تُطرح تلقائياً من رأس المال الكاش</div>
                </div>
            </div>
            <a href="{{ \App\Filament\Resources\ExpenseResource::getUrl('index') }}"
               class="inline-flex items-center gap-1.5 rounded-lg bg-gray-50 px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-gray-700">
                <x-heroicon-o-eye class="h-4 w-4" />
                التفاصيل حسب الشخص
            </a>
        </div>
    </x-filament::section>

    {{-- ══════════════════════════════════════════════ --}}
    {{-- ── القسم الخامس: السنوي ── --}}
    {{-- ══════════════════════════════════════════════ --}}

    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
        <x-filament::section>
            <div class="flex items-center gap-3">
                <div class="rounded-lg bg-green-50 p-2 dark:bg-green-900/20">
                    <x-heroicon-o-chart-bar class="h-6 w-6 text-green-600 dark:text-green-400" />
                </div>
                <div>
                    <div class="text-sm text-gray-500 dark:text-gray-400">الربح السنوي الإجمالي ({{ now()->year }})</div>
                    <div class="text-xl font-bold text-green-600 dark:text-green-400">{{ $iqd($stats['annual_profit']) }}</div>
                </div>
            </div>
        </x-filament::section>

        <x-filament::section>
            <div class="flex items-center gap-3">
                <div class="rounded-lg {{ $stats['annual_net_profit'] >= 0 ? 'bg-green-50 dark:bg-green-900/20' : 'bg-red-50 dark:bg-red-900/20' }} p-2">
                    <x-heroicon-o-calculator class="h-6 w-6 {{ $stats['annual_net_profit'] >= 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}" />
                </div>
                <div>
                    <div class="text-sm text-gray-500 dark:text-gray-400">صافي الربح السنوي ({{ now()->year }})</div>
                    <div class="text-xl font-bold {{ $stats['annual_net_profit'] >= 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">{{ $iqd($stats['annual_net_profit']) }}</div>
                </div>
            </div>
        </x-filament::section>
    </div>

    {{-- ══════════════════════════════════════════════ --}}
    {{-- ── القسم السادس: إجراءات سريعة ── --}}
    {{-- ══════════════════════════════════════════════ --}}

    <div class="rounded-xl border border-gray-200 bg-white p-4 dark:border-gray-700 dark:bg-gray-900">
        <h3 class="mb-3 text-sm font-bold text-gray-500 dark:text-gray-400">إجراءات سريعة</h3>
        <div class="flex flex-wrap gap-2">
            <a href="{{ \App\Filament\Resources\CustomerResource::getUrl('create') }}"
               class="inline-flex items-center gap-1.5 rounded-lg bg-primary-50 px-3 py-2 text-sm font-medium text-primary-700 hover:bg-primary-100 dark:bg-primary-900/20 dark:text-primary-400 dark:hover:bg-primary-900/30">
                <x-heroicon-o-plus class="h-4 w-4" />
                إضافة زبون
            </a>
            <a href="{{ \App\Filament\Resources\InvestorResource::getUrl('create') }}"
               class="inline-flex items-center gap-1.5 rounded-lg bg-primary-50 px-3 py-2 text-sm font-medium text-primary-700 hover:bg-primary-100 dark:bg-primary-900/20 dark:text-primary-400 dark:hover:bg-primary-900/30">
                <x-heroicon-o-plus class="h-4 w-4" />
                إضافة مستثمر
            </a>
            <a href="{{ \App\Filament\Resources\ExpenseResource::getUrl('create') }}"
               class="inline-flex items-center gap-1.5 rounded-lg bg-primary-50 px-3 py-2 text-sm font-medium text-primary-700 hover:bg-primary-100 dark:bg-primary-900/20 dark:text-primary-400 dark:hover:bg-primary-900/30">
                <x-heroicon-o-plus class="h-4 w-4" />
                إضافة مصروف
            </a>
            <a href="{{ \App\Filament\Pages\ManageSettings::getUrl() }}"
               class="inline-flex items-center gap-1.5 rounded-lg bg-gray-50 px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-gray-700">
                <x-heroicon-o-cog-6-tooth class="h-4 w-4" />
                تعديل رأس المال الكاش
            </a>
        </div>
    </div>
</x-filament-panels::page>
