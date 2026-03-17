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

    {{-- ── الداخل والخارج ── --}}
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">

        {{-- الداخل --}}
        <div class="rounded-xl border border-green-200 bg-white p-5 dark:border-green-800 dark:bg-gray-900">
            <div class="flex items-center gap-2 mb-4">
                <div class="rounded-lg bg-green-50 p-2 dark:bg-green-900/20">
                    <x-heroicon-o-arrow-down-tray class="h-5 w-5 text-green-600 dark:text-green-400" />
                </div>
                <h3 class="text-base font-bold text-green-700 dark:text-green-400">المبالغ الداخلة</h3>
                <span class="mr-auto text-lg font-bold text-green-600 dark:text-green-400">{{ $iqd($stats['total_in']) }}</span>
            </div>

            <div class="space-y-3">
                <div class="flex items-center justify-between rounded-lg bg-gray-50 px-4 py-3 dark:bg-gray-800">
                    <div class="flex items-center gap-2">
                        <x-heroicon-o-cog-6-tooth class="h-4 w-4 text-gray-400" />
                        <span class="text-sm text-gray-700 dark:text-gray-300">رأس المال اليدوي (الإعدادات)</span>
                    </div>
                    <span class="font-semibold text-gray-900 dark:text-white">{{ $iqd($stats['manual_capital']) }}</span>
                </div>

                <div class="flex items-center justify-between rounded-lg bg-gray-50 px-4 py-3 dark:bg-gray-800">
                    <div class="flex items-center gap-2">
                        <x-heroicon-o-banknotes class="h-4 w-4 text-gray-400" />
                        <span class="text-sm text-gray-700 dark:text-gray-300">تسديدات الزبائن</span>
                    </div>
                    <span class="font-semibold text-gray-900 dark:text-white">{{ $iqd($stats['total_payments_in']) }}</span>
                </div>

                <div class="flex items-center justify-between rounded-lg bg-gray-50 px-4 py-3 dark:bg-gray-800">
                    <div class="flex items-center gap-2">
                        <x-heroicon-o-user-group class="h-4 w-4 text-gray-400" />
                        <span class="text-sm text-gray-700 dark:text-gray-300">استثمارات المستثمرين</span>
                    </div>
                    <span class="font-semibold text-gray-900 dark:text-white">{{ $iqd($stats['total_investments']) }}</span>
                </div>
            </div>
        </div>

        {{-- الخارج --}}
        <div class="rounded-xl border border-red-200 bg-white p-5 dark:border-red-800 dark:bg-gray-900">
            <div class="flex items-center gap-2 mb-4">
                <div class="rounded-lg bg-red-50 p-2 dark:bg-red-900/20">
                    <x-heroicon-o-arrow-up-tray class="h-5 w-5 text-red-600 dark:text-red-400" />
                </div>
                <h3 class="text-base font-bold text-red-700 dark:text-red-400">المبالغ الخارجة</h3>
                <span class="mr-auto text-lg font-bold text-red-600 dark:text-red-400">{{ $iqd($stats['total_out']) }}</span>
            </div>

            <div class="space-y-3">
                <div class="flex items-center justify-between rounded-lg bg-gray-50 px-4 py-3 dark:bg-gray-800">
                    <div class="flex items-center gap-2">
                        <x-heroicon-o-receipt-percent class="h-4 w-4 text-gray-400" />
                        <span class="text-sm text-gray-700 dark:text-gray-300">المصاريف (شخصية + تجارية + رواتب)</span>
                    </div>
                    <span class="font-semibold text-gray-900 dark:text-white">{{ $iqd($stats['total_expenses']) }}</span>
                </div>

                <div class="flex items-center justify-between rounded-lg bg-gray-50 px-4 py-3 dark:bg-gray-800">
                    <div class="flex items-center gap-2">
                        <x-heroicon-o-arrow-trending-up class="h-4 w-4 text-gray-400" />
                        <span class="text-sm text-gray-700 dark:text-gray-300">دفعات المستثمرين</span>
                    </div>
                    <span class="font-semibold text-gray-900 dark:text-white">{{ $iqd($stats['total_investor_payouts']) }}</span>
                </div>

                <div class="flex items-center justify-between rounded-lg bg-gray-50 px-4 py-3 dark:bg-gray-800">
                    <div class="flex items-center gap-2">
                        <x-heroicon-o-shopping-cart class="h-4 w-4 text-gray-400" />
                        <span class="text-sm text-gray-700 dark:text-gray-300">سعر شراء البضائع (رأس المال المستخدم)</span>
                    </div>
                    <span class="font-semibold text-gray-900 dark:text-white">{{ $iqd($stats['total_cost_price']) }}</span>
                </div>
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
