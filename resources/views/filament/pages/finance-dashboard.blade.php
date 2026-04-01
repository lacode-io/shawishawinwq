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

    {{-- ── الأقساط المدفوعة ── --}}
    <x-filament::section>
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-3">
                <div class="rounded-lg bg-emerald-50 p-2 dark:bg-emerald-900/20">
                    <x-heroicon-o-check-badge class="h-6 w-6 text-emerald-600 dark:text-emerald-400" />
                </div>
                <div>
                    <div class="text-sm text-gray-500 dark:text-gray-400">الأقساط المدفوعة</div>
                    <div class="text-2xl font-bold text-emerald-600 dark:text-emerald-400">{{ $iqd($stats['monthly_payments_in']) }}</div>
                    <div class="text-xs text-gray-400 dark:text-gray-500">{{ \Carbon\Carbon::create()->month($this->paymentsMonth)->translatedFormat('F') }} {{ $this->paymentsYear }}</div>
                </div>
            </div>
            <div class="flex items-center gap-2">
                <select wire:model.live="paymentsMonth"
                    class="rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-700 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-300">
                    @foreach(range(1, 12) as $m)
                        <option value="{{ $m }}">{{ \Carbon\Carbon::create()->month($m)->translatedFormat('F') }}</option>
                    @endforeach
                </select>
                <select wire:model.live="paymentsYear"
                    class="rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-700 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-300">
                    @foreach(range(now()->year, 2024, -1) as $y)
                        <option value="{{ $y }}">{{ $y }}</option>
                    @endforeach
                </select>
            </div>
        </div>
    </x-filament::section>

    {{-- ── تفاصيل رأس المال الكاش (كيف يتحسب) ── --}}
    <div class="rounded-xl border border-gray-200 bg-white p-6 dark:border-gray-700 dark:bg-gray-900">
        <h3 class="mb-4 text-lg font-bold text-gray-900 dark:text-white">
            <x-heroicon-o-banknotes class="inline h-5 w-5" />
            تفاصيل رأس المال الكاش
        </h3>
        <p class="text-xs text-gray-500 dark:text-gray-400 mb-4">رأس المال الكاش = المبلغ المدخل + تسديدات الزبائن + استثمارات المستثمرين − المصاريف − دفعات المستثمرين − سعر شراء البضائع</p>

        {{-- المعادلة البصرية --}}
        <div class="flex flex-wrap items-center justify-center gap-3 text-center text-sm mb-6">
            <div class="rounded-lg bg-gray-50 px-4 py-3 dark:bg-gray-800">
                <div class="text-xs text-gray-500 dark:text-gray-400">المبلغ المدخل</div>
                <div class="text-lg font-bold text-gray-700 dark:text-gray-300">{{ $iqd($stats['manual_cash_capital']) }}</div>
            </div>
            @if($stats['extra_capital'] > 0)
            <span class="text-2xl font-bold text-green-500">+</span>
            <div class="rounded-lg bg-green-50 px-4 py-3 dark:bg-green-900/20">
                <div class="text-xs text-gray-500 dark:text-gray-400">مبلغ إضافي</div>
                <div class="text-lg font-bold text-green-600 dark:text-green-400">{{ $iqd($stats['extra_capital']) }}</div>
            </div>
            @endif
            <span class="text-2xl font-bold text-green-500">+</span>
            <div class="rounded-lg bg-green-50 px-4 py-3 dark:bg-green-900/20">
                <div class="text-xs text-gray-500 dark:text-gray-400">تسديدات الزبائن</div>
                <div class="text-lg font-bold text-green-600 dark:text-green-400">{{ $iqd($stats['total_payments_in']) }}</div>
            </div>
            <span class="text-2xl font-bold text-green-500">+</span>
            <div class="rounded-lg bg-green-50 px-4 py-3 dark:bg-green-900/20">
                <div class="text-xs text-gray-500 dark:text-gray-400">استثمارات المستثمرين</div>
                <div class="text-lg font-bold text-green-600 dark:text-green-400">{{ $iqd($stats['total_investments']) }}</div>
            </div>
            <span class="text-2xl font-bold text-red-500">−</span>
            <div class="rounded-lg bg-orange-50 px-4 py-3 dark:bg-orange-900/20">
                <div class="text-xs text-gray-500 dark:text-gray-400">المصاريف الكلية</div>
                <div class="text-lg font-bold text-orange-600 dark:text-orange-400">{{ $iqd($stats['total_expenses_all']) }}</div>
            </div>
            <span class="text-2xl font-bold text-red-500">−</span>
            <div class="rounded-lg bg-red-50 px-4 py-3 dark:bg-red-900/20">
                <div class="text-xs text-gray-500 dark:text-gray-400">دفعات المستثمرين</div>
                <div class="text-lg font-bold text-red-600 dark:text-red-400">{{ $iqd($stats['total_investor_payouts_all']) }}</div>
            </div>
            <span class="text-2xl font-bold text-red-500">−</span>
            <div class="rounded-lg bg-red-50 px-4 py-3 dark:bg-red-900/20">
                <div class="text-xs text-gray-500 dark:text-gray-400">سعر شراء البضائع</div>
                <div class="text-lg font-bold text-red-600 dark:text-red-400">{{ $iqd($stats['total_cost_price']) }}</div>
            </div>
            <span class="text-2xl font-bold text-gray-400">=</span>
            <div class="rounded-lg border-2 {{ $stats['cash_capital'] >= 0 ? 'border-green-500 bg-green-50 dark:bg-green-900/20' : 'border-red-500 bg-red-50 dark:bg-red-900/20' }} px-6 py-3">
                <div class="text-xs text-gray-500 dark:text-gray-400">رأس المال الكاش</div>
                <div class="text-xl font-bold {{ $stats['cash_capital'] >= 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">{{ $iqd($stats['cash_capital']) }}</div>
            </div>
        </div>

        {{-- التفاصيل - دروب داون --}}
        <details class="group mt-2">
            <summary class="flex cursor-pointer items-center gap-2 text-sm font-medium text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-white">
                <x-heroicon-o-chevron-left class="h-4 w-4 transition-transform duration-200 group-open:-rotate-90" />
                عرض التفاصيل
            </summary>

            <div class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-2">

                {{-- الداخل --}}
                <div class="rounded-lg border border-green-200 p-4 dark:border-green-800">
                    <div class="flex items-center gap-2 mb-3">
                        <x-heroicon-o-arrow-down-tray class="h-4 w-4 text-green-600 dark:text-green-400" />
                        <span class="text-sm font-bold text-green-700 dark:text-green-400">المبالغ الداخلة</span>
                        <span class="mr-auto text-sm font-bold text-green-600 dark:text-green-400">{{ $iqd($stats['manual_cash_capital'] + $stats['total_payments_in'] + $stats['total_investments']) }}</span>
                    </div>

                    {{-- رأس المال اليدوي --}}
                    <div class="rounded-lg border border-gray-100 dark:border-gray-800 mb-2 overflow-hidden">
                        <div class="flex items-center justify-between bg-gray-50 px-3 py-2 dark:bg-gray-800">
                            <span class="text-xs font-semibold text-gray-600 dark:text-gray-400"><x-heroicon-o-cog-6-tooth class="inline h-3.5 w-3.5" /> رأس المال اليدوي</span>
                            <span class="text-xs font-bold text-green-600 dark:text-green-400">{{ $iqd($stats['manual_cash_capital']) }}</span>
                        </div>
                    </div>

                    {{-- المبلغ الإضافي --}}
                    @if($stats['extra_capital'] > 0)
                    <div class="rounded-lg border border-gray-100 dark:border-gray-800 mb-2 overflow-hidden">
                        <div class="flex items-center justify-between bg-gray-50 px-3 py-2 dark:bg-gray-800">
                            <span class="text-xs font-semibold text-gray-600 dark:text-gray-400"><x-heroicon-o-plus-circle class="inline h-3.5 w-3.5" /> مبلغ إضافي على رأس المال</span>
                            <span class="text-xs font-bold text-green-600 dark:text-green-400">{{ $iqd($stats['extra_capital']) }}</span>
                        </div>
                    </div>
                    @endif

                    {{-- تسديدات الزبائن --}}
                    <div class="rounded-lg border border-gray-100 dark:border-gray-800 mb-2 overflow-hidden">
                        <div class="flex items-center justify-between bg-gray-50 px-3 py-2 dark:bg-gray-800">
                            <span class="text-xs font-semibold text-gray-600 dark:text-gray-400"><x-heroicon-o-banknotes class="inline h-3.5 w-3.5" /> تسديدات الزبائن ({{ count($stats['payments_by_customer']) }})</span>
                            <span class="text-xs font-bold text-green-600 dark:text-green-400">{{ $iqd($stats['total_payments_in']) }}</span>
                        </div>
                        @foreach($stats['payments_by_customer'] as $item)
                        <div class="flex items-center justify-between px-3 py-1.5 border-t border-gray-50 dark:border-gray-800">
                            <span class="text-xs text-gray-500 dark:text-gray-400">{{ $item['name'] }} <span class="text-gray-400">({{ $item['count'] }} دفعة)</span></span>
                            <span class="text-xs text-gray-700 dark:text-gray-300">{{ $iqd($item['total']) }}</span>
                        </div>
                        @endforeach
                    </div>

                    {{-- استثمارات المستثمرين --}}
                    <div class="rounded-lg border border-gray-100 dark:border-gray-800 overflow-hidden">
                        <div class="flex items-center justify-between bg-gray-50 px-3 py-2 dark:bg-gray-800">
                            <span class="text-xs font-semibold text-gray-600 dark:text-gray-400"><x-heroicon-o-user-group class="inline h-3.5 w-3.5" /> استثمارات المستثمرين ({{ count($stats['investors_list']) }})</span>
                            <span class="text-xs font-bold text-green-600 dark:text-green-400">{{ $iqd($stats['total_investments']) }}</span>
                        </div>
                        @foreach($stats['investors_list'] as $item)
                        <div class="flex items-center justify-between px-3 py-1.5 border-t border-gray-50 dark:border-gray-800">
                            <span class="text-xs text-gray-500 dark:text-gray-400">{{ $item['name'] }}</span>
                            <span class="text-xs text-gray-700 dark:text-gray-300">{{ $iqd($item['amount']) }}</span>
                        </div>
                        @endforeach
                    </div>
                </div>

                {{-- الخارج --}}
                <div class="rounded-lg border border-red-200 p-4 dark:border-red-800">
                    <div class="flex items-center gap-2 mb-3">
                        <x-heroicon-o-arrow-up-tray class="h-4 w-4 text-red-600 dark:text-red-400" />
                        <span class="text-sm font-bold text-red-700 dark:text-red-400">المبالغ الخارجة</span>
                        <span class="mr-auto text-sm font-bold text-red-600 dark:text-red-400">{{ $iqd($stats['total_expenses_all'] + $stats['total_investor_payouts_all'] + $stats['total_cost_price']) }}</span>
                    </div>

                    {{-- المصاريف --}}
                    <div class="rounded-lg border border-gray-100 dark:border-gray-800 mb-2 overflow-hidden">
                        <div class="flex items-center justify-between bg-gray-50 px-3 py-2 dark:bg-gray-800">
                            <span class="text-xs font-semibold text-gray-600 dark:text-gray-400"><x-heroicon-o-receipt-percent class="inline h-3.5 w-3.5" /> المصاريف</span>
                            <span class="text-xs font-bold text-red-600 dark:text-red-400">{{ $iqd($stats['total_expenses_all']) }}</span>
                        </div>
                        @foreach($stats['expenses_by_type'] as $item)
                        <div class="flex items-center justify-between px-3 py-1.5 border-t border-gray-50 dark:border-gray-800">
                            <span class="text-xs text-gray-500 dark:text-gray-400">{{ $item['type'] }} <span class="text-gray-400">({{ $item['count'] }})</span></span>
                            <span class="text-xs text-gray-700 dark:text-gray-300">{{ $iqd($item['total']) }}</span>
                        </div>
                        @endforeach
                    </div>

                    {{-- مستحقات المستثمرين --}}
                    <div class="rounded-lg border border-gray-100 dark:border-gray-800 mb-2 overflow-hidden">
                        <div class="flex items-center justify-between bg-gray-50 px-3 py-2 dark:bg-gray-800">
                            <span class="text-xs font-semibold text-gray-600 dark:text-gray-400"><x-heroicon-o-arrow-trending-up class="inline h-3.5 w-3.5" /> دفعات المستثمرين</span>
                            <div class="text-left">
                                <span class="text-xs font-bold text-red-600 dark:text-red-400">{{ $iqd($stats['total_investor_payouts_all']) }}</span>
                                <div class="text-[10px] text-gray-400">المتبقي: <span class="text-orange-500 font-semibold">{{ $iqd($stats['total_remaining_investors']) }}</span></div>
                            </div>
                        </div>
                        @foreach($stats['payouts_by_investor'] as $item)
                        <div class="flex items-center justify-between px-3 py-1.5 border-t border-gray-50 dark:border-gray-800">
                            <span class="text-xs text-gray-500 dark:text-gray-400">{{ $item['name'] }} <span class="text-gray-400">({{ $item['count'] }} دفعة)</span></span>
                            <div class="flex items-center gap-2 text-xs">
                                <span class="text-gray-400">مستحق: {{ $iqd($item['total_due']) }}</span>
                                <span class="text-green-600 dark:text-green-400">مدفوع: {{ $iqd($item['total_paid']) }}</span>
                                <span class="font-bold {{ $item['remaining'] > 0 ? 'text-orange-600 dark:text-orange-400' : 'text-green-600 dark:text-green-400' }}">متبقي: {{ $iqd($item['remaining']) }}</span>
                            </div>
                        </div>
                        @endforeach
                    </div>

                    {{-- سعر شراء البضائع --}}
                    <div class="rounded-lg border border-gray-100 dark:border-gray-800 overflow-hidden">
                        <div class="flex items-center justify-between bg-gray-50 px-3 py-2 dark:bg-gray-800">
                            <span class="text-xs font-semibold text-gray-600 dark:text-gray-400"><x-heroicon-o-shopping-cart class="inline h-3.5 w-3.5" /> سعر شراء البضائع ({{ count($stats['cost_by_customer']) }})</span>
                            <span class="text-xs font-bold text-red-600 dark:text-red-400">{{ $iqd($stats['total_cost_price']) }}</span>
                        </div>
                        @foreach($stats['cost_by_customer'] as $item)
                        <div class="flex items-center justify-between px-3 py-1.5 border-t border-gray-50 dark:border-gray-800">
                            <span class="text-xs text-gray-500 dark:text-gray-400">{{ $item['name'] }}</span>
                            <span class="text-xs text-gray-700 dark:text-gray-300">{{ $iqd($item['cost']) }}</span>
                        </div>
                        @endforeach
                    </div>
                </div>

            </div>
        </details>
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
