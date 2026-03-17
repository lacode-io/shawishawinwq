<x-filament-panels::page>
    @php
        $stats = $this->getStats();
        $iqd = fn(int $v) => \Illuminate\Support\Number::iqd($v);
    @endphp

    {{-- ── المعادلة البصرية ── --}}
    <div class="rounded-xl border border-gray-200 bg-white p-6 dark:border-gray-700 dark:bg-gray-900">
        <h3 class="mb-5 text-lg font-bold text-gray-900 dark:text-white">
            <x-heroicon-o-calculator class="inline h-5 w-5" />
            معادلة رأس المال الكاش
        </h3>

        <div class="flex flex-wrap items-center justify-center gap-3 text-center text-sm">
            <div class="rounded-lg bg-blue-50 px-4 py-3 dark:bg-blue-900/20">
                <div class="text-xs text-gray-500 dark:text-gray-400">رأس المال اليدوي</div>
                <div class="text-lg font-bold text-blue-600 dark:text-blue-400">{{ $iqd($stats['manual_capital']) }}</div>
            </div>
            <span class="text-2xl font-bold text-green-500">+</span>
            <div class="rounded-lg bg-blue-50 px-4 py-3 dark:bg-blue-900/20">
                <div class="text-xs text-gray-500 dark:text-gray-400">تسديدات الزبائن</div>
                <div class="text-lg font-bold text-blue-600 dark:text-blue-400">{{ $iqd($stats['total_payments_in']) }}</div>
            </div>
            <span class="text-2xl font-bold text-green-500">+</span>
            <div class="rounded-lg bg-blue-50 px-4 py-3 dark:bg-blue-900/20">
                <div class="text-xs text-gray-500 dark:text-gray-400">استثمارات المستثمرين</div>
                <div class="text-lg font-bold text-blue-600 dark:text-blue-400">{{ $iqd($stats['total_investments']) }}</div>
            </div>
            <span class="text-2xl font-bold text-red-500">−</span>
            <div class="rounded-lg bg-red-50 px-4 py-3 dark:bg-red-900/20">
                <div class="text-xs text-gray-500 dark:text-gray-400">المصاريف</div>
                <div class="text-lg font-bold text-red-600 dark:text-red-400">{{ $iqd($stats['total_expenses']) }}</div>
            </div>
            <span class="text-2xl font-bold text-red-500">−</span>
            <div class="rounded-lg bg-red-50 px-4 py-3 dark:bg-red-900/20">
                <div class="text-xs text-gray-500 dark:text-gray-400">دفعات المستثمرين</div>
                <div class="text-lg font-bold text-red-600 dark:text-red-400">{{ $iqd($stats['total_investor_payouts']) }}</div>
            </div>
            <span class="text-2xl font-bold text-red-500">−</span>
            <div class="rounded-lg bg-red-50 px-4 py-3 dark:bg-red-900/20">
                <div class="text-xs text-gray-500 dark:text-gray-400">سعر شراء البضائع</div>
                <div class="text-lg font-bold text-red-600 dark:text-red-400">{{ $iqd($stats['total_cost_price']) }}</div>
            </div>
            <span class="text-2xl font-bold text-gray-400">=</span>
            <div class="rounded-lg border-2 {{ $stats['cash_capital'] >= 0 ? 'border-green-500 bg-green-50 dark:bg-green-900/20' : 'border-red-500 bg-red-50 dark:bg-red-900/20' }} px-6 py-3">
                <div class="text-xs text-gray-500 dark:text-gray-400">رأس المال الكاش</div>
                <div class="text-xl font-bold {{ $stats['cash_capital'] >= 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">
                    {{ $iqd($stats['cash_capital']) }}
                </div>
            </div>
        </div>
    </div>

    {{-- ══════════════════════════════════════════════ --}}
    {{-- ── المبالغ الداخلة ── --}}
    {{-- ══════════════════════════════════════════════ --}}
    <div class="rounded-xl border border-green-200 bg-white p-5 dark:border-green-800 dark:bg-gray-900">
        <div class="flex items-center gap-2 mb-5">
            <div class="rounded-lg bg-green-50 p-2 dark:bg-green-900/20">
                <x-heroicon-o-arrow-down-tray class="h-5 w-5 text-green-600 dark:text-green-400" />
            </div>
            <h3 class="text-lg font-bold text-green-700 dark:text-green-400">المبالغ الداخلة</h3>
            <span class="mr-auto text-lg font-bold text-green-600 dark:text-green-400">{{ $iqd($stats['total_in']) }}</span>
        </div>

        <div class="space-y-5">

            {{-- 1. رأس المال اليدوي --}}
            <div class="rounded-lg border border-gray-200 dark:border-gray-700 overflow-hidden">
                <div class="flex items-center justify-between bg-gray-50 px-4 py-3 dark:bg-gray-800">
                    <div class="flex items-center gap-2">
                        <x-heroicon-o-cog-6-tooth class="h-4 w-4 text-blue-500" />
                        <span class="font-semibold text-gray-700 dark:text-gray-300">رأس المال اليدوي (الإعدادات)</span>
                    </div>
                    <span class="font-bold text-green-600 dark:text-green-400">{{ $iqd($stats['manual_capital']) }}</span>
                </div>
                <div class="px-4 py-2 text-xs text-gray-500 dark:text-gray-400">
                    رقم واحد مدخل يدوياً من إعدادات النظام
                </div>
            </div>

            {{-- 2. تسديدات الزبائن --}}
            <div class="rounded-lg border border-gray-200 dark:border-gray-700 overflow-hidden">
                <div class="flex items-center justify-between bg-gray-50 px-4 py-3 dark:bg-gray-800">
                    <div class="flex items-center gap-2">
                        <x-heroicon-o-banknotes class="h-4 w-4 text-blue-500" />
                        <span class="font-semibold text-gray-700 dark:text-gray-300">تسديدات الزبائن</span>
                        <span class="text-xs text-gray-400">({{ count($stats['payments_by_customer']) }} زبون)</span>
                    </div>
                    <span class="font-bold text-green-600 dark:text-green-400">{{ $iqd($stats['total_payments_in']) }}</span>
                </div>
                @if(count($stats['payments_by_customer']) > 0)
                <div class="divide-y divide-gray-100 dark:divide-gray-800">
                    @foreach($stats['payments_by_customer'] as $item)
                    <div class="flex items-center justify-between px-4 py-2">
                        <div class="flex items-center gap-2">
                            <span class="text-sm text-gray-600 dark:text-gray-400">{{ $item['name'] }}</span>
                            <span class="text-xs text-gray-400">({{ $item['count'] }} دفعة)</span>
                        </div>
                        <span class="text-sm font-medium text-gray-900 dark:text-white">{{ $iqd($item['total']) }}</span>
                    </div>
                    @endforeach
                </div>
                @endif
            </div>

            {{-- 3. استثمارات المستثمرين --}}
            <div class="rounded-lg border border-gray-200 dark:border-gray-700 overflow-hidden">
                <div class="flex items-center justify-between bg-gray-50 px-4 py-3 dark:bg-gray-800">
                    <div class="flex items-center gap-2">
                        <x-heroicon-o-user-group class="h-4 w-4 text-blue-500" />
                        <span class="font-semibold text-gray-700 dark:text-gray-300">استثمارات المستثمرين</span>
                        <span class="text-xs text-gray-400">({{ count($stats['investors_list']) }} مستثمر)</span>
                    </div>
                    <span class="font-bold text-green-600 dark:text-green-400">{{ $iqd($stats['total_investments']) }}</span>
                </div>
                @if(count($stats['investors_list']) > 0)
                <div class="divide-y divide-gray-100 dark:divide-gray-800">
                    @foreach($stats['investors_list'] as $item)
                    <div class="flex items-center justify-between px-4 py-2">
                        <span class="text-sm text-gray-600 dark:text-gray-400">{{ $item['name'] }}</span>
                        <span class="text-sm font-medium text-gray-900 dark:text-white">{{ $iqd($item['amount']) }}</span>
                    </div>
                    @endforeach
                </div>
                @endif
            </div>

        </div>
    </div>

    {{-- ══════════════════════════════════════════════ --}}
    {{-- ── المبالغ الخارجة ── --}}
    {{-- ══════════════════════════════════════════════ --}}
    <div class="rounded-xl border border-red-200 bg-white p-5 dark:border-red-800 dark:bg-gray-900">
        <div class="flex items-center gap-2 mb-5">
            <div class="rounded-lg bg-red-50 p-2 dark:bg-red-900/20">
                <x-heroicon-o-arrow-up-tray class="h-5 w-5 text-red-600 dark:text-red-400" />
            </div>
            <h3 class="text-lg font-bold text-red-700 dark:text-red-400">المبالغ الخارجة</h3>
            <span class="mr-auto text-lg font-bold text-red-600 dark:text-red-400">{{ $iqd($stats['total_out']) }}</span>
        </div>

        <div class="space-y-5">

            {{-- 4. المصاريف --}}
            <div class="rounded-lg border border-gray-200 dark:border-gray-700 overflow-hidden">
                <div class="flex items-center justify-between bg-gray-50 px-4 py-3 dark:bg-gray-800">
                    <div class="flex items-center gap-2">
                        <x-heroicon-o-receipt-percent class="h-4 w-4 text-red-500" />
                        <span class="font-semibold text-gray-700 dark:text-gray-300">المصاريف</span>
                    </div>
                    <span class="font-bold text-red-600 dark:text-red-400">{{ $iqd($stats['total_expenses']) }}</span>
                </div>
                @if(count($stats['expenses_by_type']) > 0)
                <div class="divide-y divide-gray-100 dark:divide-gray-800">
                    @foreach($stats['expenses_by_type'] as $item)
                    <div class="flex items-center justify-between px-4 py-2">
                        <div class="flex items-center gap-2">
                            <span class="text-sm text-gray-600 dark:text-gray-400">{{ $item['type'] }}</span>
                            <span class="text-xs text-gray-400">({{ $item['count'] }} مصروف)</span>
                        </div>
                        <span class="text-sm font-medium text-gray-900 dark:text-white">{{ $iqd($item['total']) }}</span>
                    </div>
                    @endforeach
                </div>
                @endif
            </div>

            {{-- 5. مستحقات المستثمرين --}}
            <div class="rounded-lg border border-gray-200 dark:border-gray-700 overflow-hidden">
                <div class="flex items-center justify-between bg-gray-50 px-4 py-3 dark:bg-gray-800">
                    <div class="flex items-center gap-2">
                        <x-heroicon-o-arrow-trending-up class="h-4 w-4 text-red-500" />
                        <span class="font-semibold text-gray-700 dark:text-gray-300">مستحقات المستثمرين</span>
                        <span class="text-xs text-gray-400">({{ count($stats['payouts_by_investor']) }} مستثمر)</span>
                    </div>
                    <div class="text-left">
                        <div class="font-bold text-red-600 dark:text-red-400">{{ $iqd($stats['total_investor_payouts']) }}</div>
                        <div class="text-xs text-gray-400">المدفوع | المتبقي: <span class="text-orange-500 font-semibold">{{ $iqd($stats['total_remaining_investors']) }}</span></div>
                    </div>
                </div>
                @if(count($stats['payouts_by_investor']) > 0)
                <div class="divide-y divide-gray-100 dark:divide-gray-800">
                    @foreach($stats['payouts_by_investor'] as $item)
                    <div class="flex items-center justify-between px-4 py-2.5">
                        <div class="flex items-center gap-2">
                            <span class="text-sm text-gray-600 dark:text-gray-400">{{ $item['name'] }}</span>
                            <span class="text-xs text-gray-400">({{ $item['count'] }} دفعة)</span>
                        </div>
                        <div class="flex items-center gap-3 text-sm">
                            <div class="text-left">
                                <div class="text-xs text-gray-400">المستحق</div>
                                <span class="font-medium text-gray-900 dark:text-white">{{ $iqd($item['total_due']) }}</span>
                            </div>
                            <div class="text-left">
                                <div class="text-xs text-gray-400">المدفوع</div>
                                <span class="font-medium text-green-600 dark:text-green-400">{{ $iqd($item['total_paid']) }}</span>
                            </div>
                            <div class="text-left">
                                <div class="text-xs text-gray-400">المتبقي</div>
                                <span class="font-bold {{ $item['remaining'] > 0 ? 'text-orange-600 dark:text-orange-400' : 'text-green-600 dark:text-green-400' }}">{{ $iqd($item['remaining']) }}</span>
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>
                @endif
            </div>

            {{-- 6. سعر شراء البضائع --}}
            <div class="rounded-lg border border-gray-200 dark:border-gray-700 overflow-hidden">
                <div class="flex items-center justify-between bg-gray-50 px-4 py-3 dark:bg-gray-800">
                    <div class="flex items-center gap-2">
                        <x-heroicon-o-shopping-cart class="h-4 w-4 text-red-500" />
                        <span class="font-semibold text-gray-700 dark:text-gray-300">سعر شراء البضائع</span>
                        <span class="text-xs text-gray-400">({{ count($stats['cost_by_customer']) }} زبون)</span>
                    </div>
                    <span class="font-bold text-red-600 dark:text-red-400">{{ $iqd($stats['total_cost_price']) }}</span>
                </div>
                @if(count($stats['cost_by_customer']) > 0)
                <div class="divide-y divide-gray-100 dark:divide-gray-800">
                    @foreach($stats['cost_by_customer'] as $item)
                    <div class="flex items-center justify-between px-4 py-2">
                        <span class="text-sm text-gray-600 dark:text-gray-400">{{ $item['name'] }}</span>
                        <span class="text-sm font-medium text-gray-900 dark:text-white">{{ $iqd($item['cost']) }}</span>
                    </div>
                    @endforeach
                </div>
                @endif
            </div>

        </div>
    </div>

    {{-- ── النتيجة النهائية ── --}}
    <div class="rounded-xl border-2 {{ $stats['cash_capital'] >= 0 ? 'border-green-500 bg-green-50 dark:border-green-600 dark:bg-green-900/10' : 'border-red-500 bg-red-50 dark:border-red-600 dark:bg-red-900/10' }} p-6 text-center">
        <div class="text-sm text-gray-600 dark:text-gray-400 mb-1">رأس المال الكاش النهائي</div>
        <div class="text-3xl font-bold {{ $stats['cash_capital'] >= 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">
            {{ $iqd($stats['cash_capital']) }}
        </div>
        <div class="mt-2 text-xs text-gray-500 dark:text-gray-400">
            {{ $iqd($stats['total_in']) }} (داخل) − {{ $iqd($stats['total_out']) }} (خارج)
        </div>
    </div>

</x-filament-panels::page>
