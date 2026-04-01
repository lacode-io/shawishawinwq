<x-filament-panels::page>
    @php
        $data = $this->getTargetData();
        $investors = $data['investor_targets'];
        $personal = $data['personal_target'];
        $cr = $data['cash_register'];
        $iqd = fn(?int $v) => \Illuminate\Support\Number::iqd($v ?? 0);

        $progressColor = fn(float $pct) => $pct >= 75 ? 'green' : ($pct >= 40 ? 'yellow' : 'red');
        $progressBg = fn(float $pct) => $pct >= 75 ? 'bg-green-500' : ($pct >= 40 ? 'bg-yellow-500' : 'bg-red-500');
        $progressText = fn(float $pct) => $pct >= 75 ? 'text-green-600 dark:text-green-400' : ($pct >= 40 ? 'text-yellow-600 dark:text-yellow-400' : 'text-red-600 dark:text-red-400');
    @endphp

    {{-- ══════════════════════════════════════════════ --}}
    {{-- ── القسم الأول: تاركت المستثمرين ── --}}
    {{-- ══════════════════════════════════════════════ --}}

    <div class="space-y-4">
        <div class="rounded-xl border border-gray-200 bg-white p-6 dark:border-gray-700 dark:bg-gray-900">
            <div class="flex items-center gap-2 mb-5">
                <x-heroicon-o-user-group class="h-6 w-6 text-primary-600 dark:text-primary-400" />
                <h2 class="text-xl font-bold text-gray-900 dark:text-white">تاركت المستثمرين</h2>
            </div>

            @if(count($investors['per_investor']) > 0)
                {{-- ── بطاقات المستثمرين ── --}}
                <div class="grid grid-cols-1 gap-4 lg:grid-cols-2">
                    @foreach($investors['per_investor'] as $inv)
                    <div class="rounded-xl border border-gray-200 bg-gray-50 p-5 dark:border-gray-700 dark:bg-gray-800">
                        {{-- Header --}}
                        <div class="flex items-center justify-between mb-3">
                            <a href="{{ \App\Filament\Resources\InvestorResource::getUrl('view', ['record' => $inv['id']]) }}"
                               class="text-lg font-bold text-gray-900 dark:text-white hover:text-primary-600 dark:hover:text-primary-400 hover:underline">
                                {{ $inv['name'] }}
                            </a>
                            <span class="inline-flex items-center rounded-full bg-primary-100 px-2.5 py-0.5 text-xs font-bold text-primary-700 dark:bg-primary-900/40 dark:text-primary-400">
                                {{ $inv['profit_percent'] }}%
                            </span>
                        </div>

                        {{-- معلومات الاستثمار --}}
                        <div class="grid grid-cols-2 gap-3 mb-4 text-sm">
                            <div>
                                <span class="text-gray-500 dark:text-gray-400">مبلغ الاستثمار:</span>
                                <span class="font-bold text-gray-900 dark:text-white">{{ $iqd($inv['amount_invested']) }}</span>
                            </div>
                            <div>
                                <span class="text-gray-500 dark:text-gray-400">الإجمالي مع الربح:</span>
                                <span class="font-bold text-primary-600 dark:text-primary-400">{{ $iqd($inv['total_due']) }}</span>
                            </div>
                            <div>
                                <span class="text-gray-500 dark:text-gray-400">المدة:</span>
                                <span class="font-bold text-gray-900 dark:text-white">{{ $inv['investment_months'] }} شهر</span>
                                <span class="text-xs text-gray-400">({{ $inv['elapsed_months'] }} منقضي)</span>
                            </div>
                            <div>
                                <span class="text-gray-500 dark:text-gray-400">الربح الكلي:</span>
                                <span class="font-bold text-green-600 dark:text-green-400">{{ $iqd($inv['total_profit_amount']) }}</span>
                            </div>
                        </div>

                        {{-- التاركت --}}
                        <div class="grid grid-cols-3 gap-2 mb-4">
                            <div class="rounded-lg bg-white px-3 py-2 text-center dark:bg-gray-900">
                                <div class="text-xs text-gray-500 dark:text-gray-400">التاركت الشهري</div>
                                <div class="text-sm font-bold text-gray-900 dark:text-white">{{ $iqd($inv['monthly_target']) }}</div>
                            </div>
                            <div class="rounded-lg bg-white px-3 py-2 text-center dark:bg-gray-900">
                                <div class="text-xs text-gray-500 dark:text-gray-400">التاركت السنوي</div>
                                <div class="text-sm font-bold text-gray-900 dark:text-white">{{ $iqd($inv['yearly_target']) }}</div>
                            </div>
                            <div class="rounded-lg bg-white px-3 py-2 text-center dark:bg-gray-900">
                                <div class="text-xs text-gray-500 dark:text-gray-400">المدفوع</div>
                                <div class="text-sm font-bold text-green-600 dark:text-green-400">{{ $iqd($inv['total_paid']) }}</div>
                            </div>
                        </div>

                        {{-- شريط تاركت الربح الشهري (حسب الأشهر) --}}
                        @php $monthsPct = number_format($inv['months_progress'], 1, '.', ''); @endphp
                        <div class="mb-3">
                            <div class="flex items-center justify-between mb-1">
                                <span class="text-xs text-gray-500 dark:text-gray-400">
                                    تاركت الربح الشهري
                                </span>
                                <span class="text-sm font-bold {{ $progressText($inv['months_progress']) }}">
                                    {{ $inv['elapsed_months'] }} / {{ $inv['investment_months'] }} شهر
                                </span>
                            </div>
                            <div class="w-full rounded-full h-3 overflow-hidden bg-gray-200 dark:bg-gray-700">
                                <div class="h-3 rounded-full" style="width: {{ $monthsPct }}%; min-width: {{ $monthsPct > 0 ? '0.5rem' : '0' }}; background-color: #3b82f6;"></div>
                            </div>
                            <div class="flex items-center justify-between mt-1">
                                <span class="text-xs text-gray-400">{{ $iqd($inv['monthly_target']) }} / شهر</span>
                                <span class="text-xs text-gray-400">الربح الكلي: {{ $iqd($inv['total_profit_amount']) }}</span>
                            </div>
                        </div>

                        {{-- شريط الإنجاز الكلي (حسب الأشهر) --}}
                        @php $totalPct = number_format($inv['progress_percent'], 1, '.', ''); @endphp
                        <div>
                            <div class="flex items-center justify-between mb-1">
                                <span class="text-xs text-gray-500 dark:text-gray-400">الإنجاز الكلي (استثمار + ربح)</span>
                                <span class="text-sm font-bold {{ $progressText($inv['months_progress']) }}">
                                    {{ $inv['months_progress'] }}%
                                </span>
                            </div>
                            <div class="w-full rounded-full h-3 overflow-hidden bg-gray-200 dark:bg-gray-700">
                                <div class="h-3 rounded-full" style="width: {{ $monthsPct }}%; min-width: {{ $monthsPct > 0 ? '0.5rem' : '0' }}; background-color: {{ $inv['months_progress'] >= 75 ? '#22c55e' : ($inv['months_progress'] >= 40 ? '#eab308' : '#ef4444') }};"></div>
                            </div>
                            <div class="flex items-center justify-between mt-1">
                                <span class="text-xs text-gray-400">{{ $iqd($inv['total_paid']) }}</span>
                                <span class="text-xs text-gray-400">{{ $iqd($inv['total_due']) }}</span>
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>

                {{-- ── التاركت الكلي للمستثمرين ── --}}
                <div class="mt-6 rounded-xl border-2 border-primary-300 bg-primary-50 p-5 dark:border-primary-700 dark:bg-primary-900/20">
                    <div class="flex items-center gap-2 mb-4">
                        <x-heroicon-o-chart-bar class="h-5 w-5 text-primary-600 dark:text-primary-400" />
                        <h3 class="text-lg font-bold text-gray-900 dark:text-white">التاركت الكلي (جميع المستثمرين)</h3>
                    </div>

                    <div class="grid grid-cols-2 gap-3 sm:grid-cols-4 mb-4">
                        <div class="rounded-lg bg-white px-4 py-3 text-center dark:bg-gray-900">
                            <div class="text-xs text-gray-500 dark:text-gray-400">إجمالي الاستثمارات</div>
                            <div class="text-lg font-bold text-gray-900 dark:text-white">{{ $iqd($investors['combined']['total_invested']) }}</div>
                        </div>
                        <div class="rounded-lg bg-white px-4 py-3 text-center dark:bg-gray-900">
                            <div class="text-xs text-gray-500 dark:text-gray-400">الإجمالي المستحق</div>
                            <div class="text-lg font-bold text-primary-600 dark:text-primary-400">{{ $iqd($investors['combined']['total_due']) }}</div>
                        </div>
                        <div class="rounded-lg bg-white px-4 py-3 text-center dark:bg-gray-900">
                            <div class="text-xs text-gray-500 dark:text-gray-400">التاركت الشهري الكلي</div>
                            <div class="text-lg font-bold text-gray-900 dark:text-white">{{ $iqd($investors['combined']['monthly_target']) }}</div>
                        </div>
                        <div class="rounded-lg bg-white px-4 py-3 text-center dark:bg-gray-900">
                            <div class="text-xs text-gray-500 dark:text-gray-400">المدفوع الكلي</div>
                            <div class="text-lg font-bold text-green-600 dark:text-green-400">{{ $iqd($investors['combined']['total_paid']) }}</div>
                        </div>
                    </div>

                    {{-- شريط الإنجاز الكلي --}}
                    <div class="mb-4">
                        <div class="flex items-center justify-between mb-1">
                            <span class="text-sm text-gray-600 dark:text-gray-400">نسبة الإنجاز الكلية</span>
                            <span class="text-lg font-bold {{ $progressText($investors['combined']['progress_percent']) }}">
                                {{ $investors['combined']['progress_percent'] }}%
                            </span>
                        </div>
                        <div class="w-full bg-gray-200 rounded-full h-4 dark:bg-gray-700">
                            <div class="h-4 rounded-full transition-all {{ $progressBg($investors['combined']['progress_percent']) }}"
                                 style="width: {{ $investors['combined']['progress_percent'] }}%"></div>
                        </div>
                        <div class="flex items-center justify-between mt-1">
                            <span class="text-sm text-gray-500">{{ $iqd($investors['combined']['total_paid']) }}</span>
                            <span class="text-sm text-gray-500">{{ $iqd($investors['combined']['total_due']) }}</span>
                        </div>
                    </div>

                    {{-- معادلة التغطية --}}
                    <div class="rounded-lg bg-white p-4 dark:bg-gray-900">
                        <p class="text-xs text-gray-500 dark:text-gray-400 mb-3">معادلة تغطية تاركت المستثمرين من أرباح المبيعات</p>
                        <div class="flex flex-wrap items-center justify-center gap-3 text-center text-sm">
                            <div class="rounded-lg bg-green-50 px-4 py-2 dark:bg-green-900/20">
                                <div class="text-xs text-gray-500 dark:text-gray-400">أرباح المبيعات الشهرية</div>
                                <div class="text-lg font-bold text-green-600 dark:text-green-400">{{ $iqd($investors['coverage']['monthly_profit']) }}</div>
                            </div>
                            <span class="text-2xl font-bold text-gray-400">−</span>
                            <div class="rounded-lg bg-orange-50 px-4 py-2 dark:bg-orange-900/20">
                                <div class="text-xs text-gray-500 dark:text-gray-400">تاركت المستثمرين الشهري</div>
                                <div class="text-lg font-bold text-orange-600 dark:text-orange-400">{{ $iqd($investors['coverage']['monthly_target']) }}</div>
                            </div>
                            <span class="text-2xl font-bold text-gray-400">=</span>
                            <div class="rounded-lg border-2 {{ $investors['coverage']['is_covered'] ? 'border-green-500 bg-green-50 dark:bg-green-900/20' : 'border-red-500 bg-red-50 dark:bg-red-900/20' }} px-5 py-2">
                                <div class="text-xs text-gray-500 dark:text-gray-400">{{ $investors['coverage']['is_covered'] ? 'فائض' : 'عجز' }}</div>
                                <div class="text-lg font-bold {{ $investors['coverage']['is_covered'] ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">
                                    {{ $iqd(abs($investors['coverage']['surplus'])) }}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            @else
                <div class="rounded-lg border border-gray-200 bg-gray-50 p-8 text-center dark:border-gray-700 dark:bg-gray-800">
                    <x-heroicon-o-user-group class="mx-auto h-12 w-12 text-gray-400" />
                    <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">لا يوجد مستثمرين فعالين حالياً</p>
                </div>
            @endif
        </div>

        {{-- ══════════════════════════════════════════════ --}}
        {{-- ── القسم الثاني: القاصة ── --}}
        {{-- ══════════════════════════════════════════════ --}}

        <div class="rounded-xl border-2 {{ $cr['balance'] >= 0 ? 'border-emerald-300 dark:border-emerald-700' : 'border-red-300 dark:border-red-700' }} bg-white p-6 dark:bg-gray-900">
            <div class="flex items-center justify-between mb-4">
                <div class="flex items-center gap-2">
                    <x-heroicon-o-inbox-stack class="h-6 w-6 {{ $cr['balance'] >= 0 ? 'text-emerald-600 dark:text-emerald-400' : 'text-red-600 dark:text-red-400' }}" />
                    <h2 class="text-xl font-bold text-gray-900 dark:text-white">القاصة</h2>
                </div>
                <div class="text-3xl font-bold {{ $cr['balance'] >= 0 ? 'text-emerald-600 dark:text-emerald-400' : 'text-red-600 dark:text-red-400' }}">
                    {{ $iqd(abs($cr['balance'])) }}
                    <span class="text-sm">{{ $cr['balance'] >= 0 ? 'فائض' : 'عجز' }}</span>
                </div>
            </div>

            {{-- معاينة تصفية الشهر --}}
            @php $preview = $cr['preview']; @endphp
            <div class="rounded-xl border border-gray-200 bg-gray-50 p-5 mb-5 dark:border-gray-700 dark:bg-gray-800">
                <div class="flex items-center justify-between mb-3">
                    <h3 class="text-sm font-bold text-gray-900 dark:text-white">
                        تغطية المستثمرين - شهر {{ $preview['month'] }}/{{ $preview['year'] }}
                        <span class="text-xs text-gray-400">({{ $preview['customers_count'] }} زبون)</span>
                    </h3>
                    <div class="flex items-center gap-2">
                        <select wire:model.live="settlementMonth"
                            class="rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-700 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-300">
                            @foreach(range(1, 12) as $m)
                                <option value="{{ $m }}">{{ \Carbon\Carbon::create()->month($m)->translatedFormat('F') }}</option>
                            @endforeach
                        </select>
                        <select wire:model.live="settlementYear"
                            class="rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-700 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-300">
                            @foreach(range(now()->year, 2024, -1) as $y)
                                <option value="{{ $y }}">{{ $y }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                @if($preview['settle_count'] > 0)
                <div class="flex items-center justify-end mb-3">
                    <span class="inline-flex items-center gap-1 rounded-full bg-blue-100 px-3 py-1 text-xs font-bold text-blue-700 dark:bg-blue-900/40 dark:text-blue-400">
                        <x-heroicon-o-check-circle class="h-3.5 w-3.5" />
                        تمت التصفية {{ $preview['settle_count'] }} مرة
                    </span>
                </div>
                @endif

                <div class="flex flex-wrap items-center justify-center gap-3 text-center text-sm mb-4">
                    <div class="rounded-lg bg-white px-4 py-2 dark:bg-gray-900">
                        <div class="text-xs text-gray-500 dark:text-gray-400">أرباح الزبائن</div>
                        <div class="text-lg font-bold text-green-600 dark:text-green-400">{{ $iqd($preview['monthly_profit']) }}</div>
                    </div>
                    <span class="text-2xl font-bold text-gray-400">−</span>
                    <div class="rounded-lg bg-white px-4 py-2 dark:bg-gray-900">
                        <div class="text-xs text-gray-500 dark:text-gray-400">تاركت المستثمرين</div>
                        <div class="text-lg font-bold text-orange-600 dark:text-orange-400">{{ $iqd($preview['monthly_investor_target']) }}</div>
                    </div>
                    <span class="text-2xl font-bold text-gray-400">=</span>
                    <div class="rounded-lg border-2 {{ $preview['is_surplus'] ? 'border-green-500 bg-green-50 dark:bg-green-900/20' : 'border-red-500 bg-red-50 dark:bg-red-900/20' }} px-5 py-2">
                        <div class="text-xs text-gray-500 dark:text-gray-400">{{ $preview['is_surplus'] ? 'فائض → يُضاف للقاصة' : 'عجز → يُخصم من القاصة' }}</div>
                        <div class="text-lg font-bold {{ $preview['is_surplus'] ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">
                            {{ $iqd(abs($preview['difference'])) }}
                        </div>
                    </div>
                </div>

                <div class="rounded-lg bg-white p-3 dark:bg-gray-900">
                    <div class="flex items-center justify-between text-sm">
                        <span class="text-gray-500 dark:text-gray-400">رصيد القاصة بعد التصفية:</span>
                        <span class="font-bold {{ $preview['projected_balance'] >= 0 ? 'text-emerald-600 dark:text-emerald-400' : 'text-red-600 dark:text-red-400' }}">
                            {{ $iqd($preview['projected_balance']) }}
                        </span>
                    </div>
                </div>
            </div>

            {{-- سجل الحركات --}}
            @if(count($cr['transactions']) > 0)
            <div>
                <h3 class="text-sm font-bold text-gray-500 dark:text-gray-400 mb-3">
                    <x-heroicon-o-clipboard-document-list class="inline h-4 w-4" />
                    سجل حركات القاصة
                </h3>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b border-gray-200 dark:border-gray-700">
                                <th class="px-3 py-2 text-right text-xs font-bold text-gray-500 dark:text-gray-400">التاريخ</th>
                                <th class="px-3 py-2 text-right text-xs font-bold text-gray-500 dark:text-gray-400">الشهر</th>
                                <th class="px-3 py-2 text-right text-xs font-bold text-gray-500 dark:text-gray-400">النوع</th>
                                <th class="px-3 py-2 text-right text-xs font-bold text-gray-500 dark:text-gray-400">المبلغ</th>
                                <th class="px-3 py-2 text-right text-xs font-bold text-gray-500 dark:text-gray-400">الرصيد بعدها</th>
                                <th class="px-3 py-2 text-right text-xs font-bold text-gray-500 dark:text-gray-400">الوصف</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($cr['transactions'] as $tx)
                            <tr class="border-b border-gray-100 dark:border-gray-800">
                                <td class="px-3 py-2 text-gray-500 dark:text-gray-400">{{ $tx['date'] }}</td>
                                <td class="px-3 py-2 text-gray-700 dark:text-gray-300">{{ $tx['month'] }}/{{ $tx['year'] }}</td>
                                <td class="px-3 py-2">
                                    @if($tx['type'] === 'deposit')
                                        <span class="inline-flex items-center rounded-full bg-green-100 px-2 py-0.5 text-xs font-bold text-green-700 dark:bg-green-900/40 dark:text-green-400">
                                            <x-heroicon-o-arrow-up class="h-3 w-3 ml-1" />
                                            إيداع
                                        </span>
                                    @else
                                        <span class="inline-flex items-center rounded-full bg-red-100 px-2 py-0.5 text-xs font-bold text-red-700 dark:bg-red-900/40 dark:text-red-400">
                                            <x-heroicon-o-arrow-down class="h-3 w-3 ml-1" />
                                            سحب
                                        </span>
                                    @endif
                                </td>
                                <td class="px-3 py-2 font-bold {{ $tx['type'] === 'deposit' ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">
                                    {{ $tx['type'] === 'deposit' ? '+' : '-' }}{{ $iqd($tx['amount']) }}
                                </td>
                                <td class="px-3 py-2 font-bold {{ $tx['balance_after'] >= 0 ? 'text-gray-900 dark:text-white' : 'text-red-600 dark:text-red-400' }}">
                                    {{ $iqd($tx['balance_after']) }}
                                </td>
                                <td class="px-3 py-2 text-gray-500 dark:text-gray-400">{{ $tx['description'] }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            @else
            <div class="rounded-lg bg-gray-50 p-4 text-center dark:bg-gray-800">
                <p class="text-sm text-gray-500 dark:text-gray-400">لا توجد حركات مسجلة بعد - اضغط "تصفية الحسابات" لبدء أول تصفية</p>
            </div>
            @endif
        </div>

        {{-- ══════════════════════════════════════════════ --}}
        {{-- ── القسم الثالث: التاركت الشخصي ── --}}
        {{-- ══════════════════════════════════════════════ --}}

        <div class="rounded-xl border border-gray-200 bg-white p-6 dark:border-gray-700 dark:bg-gray-900">
            <div class="flex items-center justify-between mb-5">
                <div class="flex items-center gap-2">
                    <x-heroicon-o-flag class="h-6 w-6 text-purple-600 dark:text-purple-400" />
                    <h2 class="text-xl font-bold text-gray-900 dark:text-white">التاركت الشخصي</h2>
                </div>
                <a href="{{ \App\Filament\Pages\ManageSettings::getUrl() }}"
                   class="inline-flex items-center gap-1.5 rounded-lg bg-gray-50 px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-gray-700">
                    <x-heroicon-o-cog-6-tooth class="h-4 w-4" />
                    تعديل التاركت
                </a>
            </div>

            {{-- بطاقات التاركت --}}
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 mb-6">
                    {{-- التاركت السنوي --}}
                    <div class="rounded-xl border-2 border-purple-300 bg-purple-50 p-5 dark:border-purple-700 dark:bg-purple-900/20">
                        <div class="flex items-center gap-2 mb-3">
                            <x-heroicon-o-calendar class="h-5 w-5 text-purple-600 dark:text-purple-400" />
                            <h3 class="text-lg font-bold text-gray-900 dark:text-white">التاركت السنوي</h3>
                        </div>

                        <div class="text-center mb-4">
                            <div class="text-3xl font-bold text-purple-600 dark:text-purple-400">{{ $iqd($personal['yearly_target']) }}</div>
                        </div>

                        {{-- تفصيل التاركت السنوي --}}
                        <div class="rounded-lg bg-white p-3 dark:bg-gray-900 mb-4">
                            <div class="space-y-2 text-xs">
                                @if(($personal['settings_yearly_target'] ?? 0) > 0)
                                <div class="flex items-center justify-between">
                                    <span class="text-gray-500 dark:text-gray-400">التاركت الأساسي (الإعدادات):</span>
                                    <span class="font-bold text-purple-600 dark:text-purple-400">{{ $iqd($personal['settings_yearly_target'] ?? 0) }}</span>
                                </div>
                                @endif
                                <div class="flex items-center justify-between">
                                    <span class="text-gray-500 dark:text-gray-400">مستحقات المستثمرين (12 شهر):</span>
                                    <span class="font-bold text-orange-600 dark:text-orange-400">{{ $iqd($personal['yearly_investor_dues'] ?? 0) }}</span>
                                </div>
                                <div class="flex items-center justify-between">
                                    <span class="text-gray-500 dark:text-gray-400">الرواتب المدفوعة هذه السنة:</span>
                                    <span class="font-bold text-red-600 dark:text-red-400">{{ $iqd($personal['yearly_salaries'] ?? 0) }}</span>
                                </div>
                                <div class="border-t border-gray-200 dark:border-gray-700 pt-2 flex items-center justify-between">
                                    <span class="font-bold text-gray-700 dark:text-gray-300">المجموع:</span>
                                    <span class="font-bold text-purple-600 dark:text-purple-400">{{ $iqd($personal['yearly_target']) }}</span>
                                </div>
                            </div>
                        </div>

                        {{-- رصيد القاصة / التاركت --}}
                        <div class="rounded-lg bg-white p-3 dark:bg-gray-900 mb-4">
                            <div class="flex flex-wrap items-center justify-center gap-2 text-center text-xs">
                                <div class="rounded {{ $personal['balance'] >= 0 ? 'bg-emerald-50 dark:bg-emerald-900/20' : 'bg-red-50 dark:bg-red-900/20' }} px-4 py-2">
                                    <div class="text-[10px] text-gray-400">رصيد القاصة</div>
                                    <div class="font-bold {{ $personal['balance'] >= 0 ? 'text-emerald-600 dark:text-emerald-400' : 'text-red-600 dark:text-red-400' }}">{{ $iqd($personal['balance']) }}</div>
                                </div>
                                <span class="text-lg font-bold text-gray-400">من</span>
                                <div class="rounded bg-purple-50 px-4 py-2 dark:bg-purple-900/20">
                                    <div class="text-[10px] text-gray-400">التاركت السنوي</div>
                                    <div class="font-bold text-purple-600 dark:text-purple-400">{{ $iqd($personal['yearly_target']) }}</div>
                                </div>
                            </div>
                        </div>

                        {{-- شريط الإنجاز السنوي --}}
                        @php $yearlyPct = number_format($personal['yearly_progress'], 1, '.', ''); @endphp
                        <div>
                            <div class="flex items-center justify-between mb-1">
                                <span class="text-sm text-gray-600 dark:text-gray-400">نسبة الإنجاز</span>
                                <span class="text-lg font-bold" style="color: {{ $personal['yearly_progress'] >= 75 ? '#22c55e' : ($personal['yearly_progress'] >= 40 ? '#eab308' : '#ef4444') }};">
                                    {{ $personal['yearly_progress'] }}%
                                </span>
                            </div>
                            <div class="w-full rounded-full h-4 overflow-hidden bg-gray-200 dark:bg-gray-700">
                                <div class="h-4 rounded-full" style="width: {{ $yearlyPct }}%; min-width: {{ $yearlyPct > 0 ? '0.5rem' : '0' }}; background-color: {{ $personal['yearly_progress'] >= 75 ? '#22c55e' : ($personal['yearly_progress'] >= 40 ? '#eab308' : '#ef4444') }};"></div>
                            </div>
                            <div class="flex items-center justify-between mt-1">
                                <span class="text-xs text-gray-400">{{ $iqd(max(0, $personal['balance'])) }}</span>
                                <span class="text-xs text-gray-400">{{ $iqd($personal['yearly_target']) }}</span>
                            </div>
                        </div>
                    </div>

                    {{-- التاركت الشهري --}}
                    <div class="rounded-xl border-2 border-blue-300 bg-blue-50 p-5 dark:border-blue-700 dark:bg-blue-900/20">
                        <div class="flex items-center gap-2 mb-3">
                            <x-heroicon-o-clock class="h-5 w-5 text-blue-600 dark:text-blue-400" />
                            <h3 class="text-lg font-bold text-gray-900 dark:text-white">التاركت الشهري</h3>
                        </div>

                        <div class="text-center mb-4">
                            <div class="text-3xl font-bold text-blue-600 dark:text-blue-400">{{ $iqd($personal['monthly_target']) }}</div>
                            <div class="text-xs text-gray-500 dark:text-gray-400 mt-1">رواتب + مستحقات المستثمرين</div>
                        </div>

                        {{-- تفصيل التاركت الشهري --}}
                        <div class="rounded-lg bg-white p-3 dark:bg-gray-900 mb-4">
                            <div class="space-y-2 text-xs">
                                <div class="flex items-center justify-between">
                                    <span class="text-gray-500 dark:text-gray-400">مستحقات المستثمرين الشهرية:</span>
                                    <span class="font-bold text-orange-600 dark:text-orange-400">{{ $iqd($personal['monthly_investor_dues'] ?? 0) }}</span>
                                </div>
                                <div class="flex items-center justify-between">
                                    <span class="text-gray-500 dark:text-gray-400">رواتب هذا الشهر:</span>
                                    <span class="font-bold text-red-600 dark:text-red-400">{{ $iqd($personal['monthly_salaries'] ?? 0) }}</span>
                                </div>
                                <div class="border-t border-gray-200 dark:border-gray-700 pt-2 flex items-center justify-between">
                                    <span class="font-bold text-gray-700 dark:text-gray-300">= التاركت:</span>
                                    <span class="font-bold text-blue-600 dark:text-blue-400">{{ $iqd($personal['monthly_target']) }}</span>
                                </div>
                            </div>
                        </div>

                        {{-- أرباح الزبائن مقابل التاركت --}}
                        <div class="rounded-lg bg-white p-3 dark:bg-gray-900 mb-4">
                            <div class="flex flex-wrap items-center justify-center gap-2 text-center text-xs">
                                <div class="rounded bg-green-50 px-4 py-2 dark:bg-green-900/20">
                                    <div class="text-[10px] text-gray-400">أرباح الزبائن</div>
                                    <div class="font-bold text-green-600 dark:text-green-400">{{ $iqd($personal['monthly_customer_profit'] ?? 0) }}</div>
                                </div>
                                <span class="text-lg font-bold text-gray-400">→</span>
                                <div class="rounded bg-blue-50 px-4 py-2 dark:bg-blue-900/20">
                                    <div class="text-[10px] text-gray-400">التاركت</div>
                                    <div class="font-bold text-blue-600 dark:text-blue-400">{{ $iqd($personal['monthly_target']) }}</div>
                                </div>
                                <span class="text-lg font-bold text-gray-400">=</span>
                                @php $surplus = $personal['monthly_surplus'] ?? 0; @endphp
                                <div class="rounded {{ $surplus >= 0 ? 'bg-green-50 dark:bg-green-900/20' : 'bg-red-50 dark:bg-red-900/20' }} px-4 py-2">
                                    <div class="text-[10px] text-gray-400">{{ $surplus >= 0 ? 'فائض يُضاف للقاصة' : 'عجز' }}</div>
                                    <div class="font-bold {{ $surplus >= 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">{{ $iqd(abs($surplus)) }}</div>
                                </div>
                            </div>
                        </div>

                        {{-- شريط الإنجاز الشهري --}}
                        @php $monthlyPct = number_format(min($personal['monthly_progress'], 100), 1, '.', ''); @endphp
                        <div>
                            <div class="flex items-center justify-between mb-1">
                                <span class="text-sm text-gray-600 dark:text-gray-400">نسبة الإنجاز</span>
                                <span class="text-lg font-bold" style="color: {{ $personal['monthly_progress'] >= 75 ? '#22c55e' : ($personal['monthly_progress'] >= 40 ? '#eab308' : '#ef4444') }};">
                                    {{ $personal['monthly_progress'] }}%
                                </span>
                            </div>
                            <div class="w-full rounded-full h-4 overflow-hidden bg-gray-200 dark:bg-gray-700">
                                <div class="h-4 rounded-full" style="width: {{ $monthlyPct }}%; min-width: {{ $monthlyPct > 0 ? '0.5rem' : '0' }}; background-color: {{ $personal['monthly_progress'] >= 75 ? '#22c55e' : ($personal['monthly_progress'] >= 40 ? '#eab308' : '#ef4444') }};"></div>
                            </div>
                            <div class="flex items-center justify-between mt-1">
                                <span class="text-xs text-gray-400">{{ $iqd($personal['monthly_customer_profit'] ?? 0) }}</span>
                                <span class="text-xs text-gray-400">{{ $iqd($personal['monthly_target']) }}</span>
                            </div>
                        </div>
                    </div>
                </div>
        </div>
    </div>
</x-filament-panels::page>
