<x-filament-panels::page>
    @php
        $stats = $this->getStats();
        $iqd = fn(int $v) => \Illuminate\Support\Number::iqd($v);
        $hasAlerts = $stats['late_customers_count'] > 0 || $stats['behind_target_investors_count'] > 0;
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
    {{-- ── القسم الثاني: رصيد القاصة ── --}}
    {{-- ══════════════════════════════════════════════ --}}

    @php $crBalance = (int) \App\Models\Setting::instance()->cash_register_balance; @endphp
    <div class="rounded-xl border-2 {{ $crBalance >= 0 ? 'border-emerald-300 dark:border-emerald-700' : 'border-red-300 dark:border-red-700' }} bg-white p-6 dark:bg-gray-900">
        <div class="flex items-center gap-3">
            <div class="rounded-lg {{ $crBalance >= 0 ? 'bg-emerald-50 dark:bg-emerald-900/20' : 'bg-red-50 dark:bg-red-900/20' }} p-2">
                <x-heroicon-o-inbox-stack class="h-6 w-6 {{ $crBalance >= 0 ? 'text-emerald-600 dark:text-emerald-400' : 'text-red-600 dark:text-red-400' }}" />
            </div>
            <div>
                <div class="text-sm text-gray-500 dark:text-gray-400">رصيد القاصة</div>
                <div class="text-2xl font-bold {{ $crBalance >= 0 ? 'text-emerald-600 dark:text-emerald-400' : 'text-red-600 dark:text-red-400' }}">
                    {{ $iqd(abs($crBalance)) }}
                    <span class="text-sm">{{ $crBalance >= 0 ? 'فائض' : 'عجز' }}</span>
                </div>
                <div class="text-xs text-gray-400 dark:text-gray-500">
                    <a href="{{ \App\Filament\Pages\TargetDashboard::getUrl() }}" class="hover:underline hover:text-primary-600">
                        عرض التفاصيل في صفحة التاركت
                    </a>
                </div>
            </div>
        </div>
    </div>

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
