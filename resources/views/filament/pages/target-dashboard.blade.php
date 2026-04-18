<x-filament-panels::page>
    @php
        $data = $this->getTargetData();
        $investors = $data['investor_targets'];
        $personal = $data['personal_target'];
        $cr = $data['cash_register'];
        $timeline = $cr['timeline'];
        $summary = $cr['timeline_summary'];
        $iqd = fn(?int $v) => \Illuminate\Support\Number::iqd($v ?? 0);

        $progressBg = fn(float $pct) => $pct >= 75 ? 'bg-green-500' : ($pct >= 40 ? 'bg-yellow-500' : 'bg-red-500');
        $progressText = fn(float $pct) => $pct >= 75 ? 'text-green-600 dark:text-green-400' : ($pct >= 40 ? 'text-yellow-600 dark:text-yellow-400' : 'text-red-600 dark:text-red-400');
        $progressColor = fn(float $pct) => $pct >= 75 ? '#22c55e' : ($pct >= 40 ? '#eab308' : '#ef4444');
    @endphp

    {{-- ══════════════════════════════════════════════ --}}
    {{-- ── القسم الأول: تاركت المستثمرين (مرتبين حسب التغطية) ── --}}
    {{-- ══════════════════════════════════════════════ --}}

    <div class="space-y-4">
        <div class="rounded-xl border border-gray-200 bg-white p-6 dark:border-gray-700 dark:bg-gray-900">
            <div class="flex items-center gap-2 mb-5">
                <x-heroicon-o-user-group class="h-6 w-6 text-primary-600 dark:text-primary-400" />
                <h2 class="text-xl font-bold text-gray-900 dark:text-white">تاركت المستثمرين</h2>
                <span class="text-xs text-gray-400">(مرتبين حسب نسبة الأشهر المغطاة)</span>
            </div>

            @if(count($investors['per_investor']) > 0)
                <div class="grid grid-cols-1 gap-4 lg:grid-cols-2">
                    @foreach($investors['per_investor'] as $idx => $inv)
                    <div class="rounded-xl border border-gray-200 bg-gray-50 p-5 dark:border-gray-700 dark:bg-gray-800">
                        {{-- Header --}}
                        <div class="flex items-center justify-between mb-3">
                            <div class="flex items-center gap-2">
                                <span class="inline-flex items-center justify-center h-7 w-7 rounded-full bg-primary-600 text-xs font-bold text-white">
                                    #{{ $idx + 1 }}
                                </span>
                                <a href="{{ \App\Filament\Resources\InvestorResource::getUrl('view', ['record' => $inv['id']]) }}"
                                   class="text-lg font-bold text-gray-900 dark:text-white hover:text-primary-600 dark:hover:text-primary-400 hover:underline">
                                    {{ $inv['name'] }}
                                </a>
                            </div>
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

                        {{-- تغطية الأشهر من الأرباح --}}
                        <div class="rounded-lg border-2 {{ $inv['coverage_percent'] >= 75 ? 'border-green-300 bg-green-50 dark:border-green-700 dark:bg-green-900/20' : ($inv['coverage_percent'] >= 40 ? 'border-yellow-300 bg-yellow-50 dark:border-yellow-700 dark:bg-yellow-900/20' : 'border-red-300 bg-red-50 dark:border-red-700 dark:bg-red-900/20') }} p-3 mb-3">
                            <div class="grid grid-cols-3 gap-2 mb-2 text-center">
                                <div>
                                    <div class="text-[10px] text-gray-500 dark:text-gray-400">أشهر مغطاة</div>
                                    <div class="text-base font-bold text-green-600 dark:text-green-400">{{ $inv['months_covered'] }}</div>
                                </div>
                                <div>
                                    <div class="text-[10px] text-gray-500 dark:text-gray-400">أشهر غير مغطاة</div>
                                    <div class="text-base font-bold text-red-600 dark:text-red-400">{{ $inv['months_missed'] }}</div>
                                </div>
                                <div>
                                    <div class="text-[10px] text-gray-500 dark:text-gray-400">من أصل</div>
                                    <div class="text-base font-bold text-gray-700 dark:text-gray-300">{{ $inv['elapsed_months'] }} شهر</div>
                                </div>
                            </div>

                            @php $covPct = number_format($inv['coverage_percent'], 1, '.', ''); @endphp
                            <div class="flex items-center justify-between mb-1">
                                <span class="text-xs text-gray-600 dark:text-gray-300">نسبة تغطية الأشهر</span>
                                <span class="text-sm font-bold" style="color: {{ $progressColor($inv['coverage_percent']) }};">{{ $inv['coverage_percent'] }}%</span>
                            </div>
                            <div class="w-full rounded-full h-2.5 overflow-hidden bg-gray-200 dark:bg-gray-700">
                                <div class="h-2.5 rounded-full" style="width: {{ $covPct }}%; background-color: {{ $progressColor($inv['coverage_percent']) }};"></div>
                            </div>
                        </div>

                        {{-- إنجاز الشهر الحالي --}}
                        <div class="rounded-lg bg-white p-3 dark:bg-gray-900 mb-3">
                            <div class="flex items-center justify-between mb-1">
                                <span class="text-xs text-gray-500 dark:text-gray-400">إنجاز هذا الشهر</span>
                                <span class="text-sm font-bold {{ $progressText($inv['this_month_progress']) }}">
                                    {{ $iqd($inv['this_month_achieved']) }} / {{ $iqd($inv['monthly_target']) }}
                                </span>
                            </div>
                            @php $tmPct = number_format($inv['this_month_progress'], 1, '.', ''); @endphp
                            <div class="w-full rounded-full h-2 overflow-hidden bg-gray-200 dark:bg-gray-700">
                                <div class="h-2 rounded-full" style="width: {{ $tmPct }}%; background-color: {{ $progressColor($inv['this_month_progress']) }};"></div>
                            </div>
                        </div>

                        {{-- شريط الإنجاز الكلي --}}
                        @php $monthsPct = number_format($inv['months_progress'], 1, '.', ''); @endphp
                        <div>
                            <div class="flex items-center justify-between mb-1">
                                <span class="text-xs text-gray-500 dark:text-gray-400">إنجاز الوقت (الأشهر المنقضية)</span>
                                <span class="text-sm font-bold {{ $progressText($inv['months_progress']) }}">
                                    {{ $inv['elapsed_months'] }} / {{ $inv['investment_months'] }} شهر
                                </span>
                            </div>
                            <div class="w-full rounded-full h-2 overflow-hidden bg-gray-200 dark:bg-gray-700">
                                <div class="h-2 rounded-full" style="width: {{ $monthsPct }}%; background-color: #3b82f6;"></div>
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

                    {{-- معادلة التغطية --}}
                    <div class="rounded-lg bg-white p-4 dark:bg-gray-900">
                        <p class="text-xs text-gray-500 dark:text-gray-400 mb-3">معادلة تغطية تاركت المستثمرين من أرباح المبيعات (هذا الشهر)</p>
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
        {{-- ── القسم الثاني: القاصة (تراكمي من الأشهر) ── --}}
        {{-- ══════════════════════════════════════════════ --}}

        @php
            $finalBalance = $summary['final_balance'];
            $totalNet = $summary['total_net'];
        @endphp

        <div class="rounded-xl border-2 {{ $finalBalance >= 0 ? 'border-emerald-300 dark:border-emerald-700' : 'border-red-300 dark:border-red-700' }} bg-white p-6 dark:bg-gray-900">
            <div class="flex flex-wrap items-center justify-between gap-3 mb-4">
                <div class="flex items-center gap-2">
                    <x-heroicon-o-inbox-stack class="h-6 w-6 {{ $finalBalance >= 0 ? 'text-emerald-600 dark:text-emerald-400' : 'text-red-600 dark:text-red-400' }}" />
                    <h2 class="text-xl font-bold text-gray-900 dark:text-white">القاصة (تراكمي)</h2>
                </div>
                <div class="text-3xl font-bold {{ $finalBalance >= 0 ? 'text-emerald-600 dark:text-emerald-400' : 'text-red-600 dark:text-red-400' }}">
                    {{ $finalBalance >= 0 ? '+' : '-' }}{{ $iqd(abs($finalBalance)) }}
                    <span class="text-sm">{{ $finalBalance >= 0 ? 'فائض' : 'عجز' }}</span>
                </div>
            </div>

            {{-- ملخص الأشهر --}}
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 mb-5">
                <div class="rounded-lg bg-green-50 px-4 py-3 text-center dark:bg-green-900/20">
                    <div class="text-xs text-gray-500 dark:text-gray-400">إجمالي الأرباح</div>
                    <div class="text-base font-bold text-green-600 dark:text-green-400">{{ $iqd($summary['total_profit']) }}</div>
                </div>
                <div class="rounded-lg bg-orange-50 px-4 py-3 text-center dark:bg-orange-900/20">
                    <div class="text-xs text-gray-500 dark:text-gray-400">إجمالي تاركت المستثمرين</div>
                    <div class="text-base font-bold text-orange-600 dark:text-orange-400">{{ $iqd($summary['total_investor_target']) }}</div>
                </div>
                <div class="rounded-lg bg-emerald-50 px-4 py-3 text-center dark:bg-emerald-900/20">
                    <div class="text-xs text-gray-500 dark:text-gray-400">أشهر بفائض</div>
                    <div class="text-base font-bold text-emerald-600 dark:text-emerald-400">{{ $summary['surplus_months'] }} شهر</div>
                </div>
                <div class="rounded-lg bg-red-50 px-4 py-3 text-center dark:bg-red-900/20">
                    <div class="text-xs text-gray-500 dark:text-gray-400">أشهر بعجز</div>
                    <div class="text-base font-bold text-red-600 dark:text-red-400">{{ $summary['deficit_months'] }} شهر</div>
                </div>
            </div>

            {{-- جدول الأشهر (قابل للطي) مع الفلاتر --}}
            <div x-data="{ open: true }" class="rounded-xl border border-gray-200 bg-gray-50 dark:border-gray-700 dark:bg-gray-800 mb-5">
                <button type="button" @click="open = !open"
                    class="w-full flex items-center justify-between px-5 py-3 text-right">
                    <div class="flex items-center gap-2">
                        <x-heroicon-o-chevron-down class="h-4 w-4 text-gray-500 transition-transform" x-bind:class="open ? 'rotate-180' : ''" />
                        <span class="text-sm font-bold text-gray-900 dark:text-white">تفاصيل الأشهر (موجب / سالب / الرصيد التراكمي)</span>
                        <span class="text-xs text-gray-400">— {{ count($timeline) }} شهر</span>
                    </div>
                    <div class="flex items-center gap-2" @click.stop>
                        <select wire:model.live="timelineMonth"
                            class="rounded-lg border border-gray-300 bg-white px-3 py-1.5 text-xs text-gray-700 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-300">
                            <option value="0">كل الأشهر</option>
                            @foreach(range(1, 12) as $m)
                                <option value="{{ $m }}">{{ \Carbon\Carbon::create()->month($m)->translatedFormat('F') }}</option>
                            @endforeach
                        </select>
                        <select wire:model.live="timelineYear"
                            class="rounded-lg border border-gray-300 bg-white px-3 py-1.5 text-xs text-gray-700 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-300">
                            <option value="0">كل السنوات</option>
                            @foreach($cr['available_years'] as $y)
                                <option value="{{ $y }}">{{ $y }}</option>
                            @endforeach
                        </select>
                    </div>
                </button>

                <div x-show="open" x-collapse>
                    <div class="px-5 pb-5">
                        @if(count($timeline) > 0)
                        <div class="overflow-x-auto rounded-lg border border-gray-200 dark:border-gray-700">
                            <table class="w-full text-sm">
                                <thead class="bg-gray-100 dark:bg-gray-900">
                                    <tr>
                                        <th class="px-3 py-2 text-right text-xs font-bold text-gray-500 dark:text-gray-400">الشهر</th>
                                        <th class="px-3 py-2 text-right text-xs font-bold text-gray-500 dark:text-gray-400">الأرباح</th>
                                        <th class="px-3 py-2 text-right text-xs font-bold text-gray-500 dark:text-gray-400">تاركت المستثمرين</th>
                                        <th class="px-3 py-2 text-right text-xs font-bold text-gray-500 dark:text-gray-400">الصافي</th>
                                        <th class="px-3 py-2 text-right text-xs font-bold text-gray-500 dark:text-gray-400">الرصيد التراكمي</th>
                                        <th class="px-3 py-2 text-right text-xs font-bold text-gray-500 dark:text-gray-400">الزبائن</th>
                                        <th class="px-3 py-2 text-right text-xs font-bold text-gray-500 dark:text-gray-400">مستثمرين</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white dark:bg-gray-900">
                                    @foreach($timeline as $row)
                                    <tr class="border-t border-gray-100 dark:border-gray-800">
                                        <td class="px-3 py-2 font-bold text-gray-900 dark:text-white">{{ $row['label'] }}</td>
                                        <td class="px-3 py-2 text-green-600 dark:text-green-400">{{ $iqd($row['monthly_profit']) }}</td>
                                        <td class="px-3 py-2 text-orange-600 dark:text-orange-400">{{ $iqd($row['monthly_investor_target']) }}</td>
                                        <td class="px-3 py-2 font-bold {{ $row['is_surplus'] ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">
                                            {{ $row['is_surplus'] ? '+' : '-' }}{{ $iqd(abs($row['net'])) }}
                                        </td>
                                        <td class="px-3 py-2 font-bold {{ $row['running_balance'] >= 0 ? 'text-emerald-600 dark:text-emerald-400' : 'text-red-600 dark:text-red-400' }}">
                                            {{ $row['running_balance'] >= 0 ? '+' : '-' }}{{ $iqd(abs($row['running_balance'])) }}
                                        </td>
                                        <td class="px-3 py-2 text-gray-500 dark:text-gray-400">{{ $row['customers_count'] }}</td>
                                        <td class="px-3 py-2 text-gray-500 dark:text-gray-400">{{ $row['active_investors_count'] }}</td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        @else
                        <p class="text-sm text-gray-500 dark:text-gray-400 text-center py-4">لا توجد بيانات</p>
                        @endif
                    </div>
                </div>
            </div>

            {{-- معاينة تصفية شهر محدد --}}
            @php $preview = $cr['preview']; @endphp
            <div class="rounded-xl border border-gray-200 bg-gray-50 p-5 mb-5 dark:border-gray-700 dark:bg-gray-800">
                <div class="flex flex-wrap items-center justify-between gap-3 mb-3">
                    <h3 class="text-sm font-bold text-gray-900 dark:text-white">
                        تصفية شهر {{ $preview['month'] }}/{{ $preview['year'] }}
                        <span class="text-xs text-gray-400">({{ $preview['customers_count'] }} زبون)</span>
                    </h3>
                    <div class="flex items-center gap-2">
                        <select wire:model.live="settlementMonth"
                            class="rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-700 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-300">
                            @foreach(range(1, 12) as $m)
                                <option value="{{ $m }}">{{ \Carbon\Carbon::create()->month($m)->translatedFormat('F') }}</option>
                            @endforeach
                        </select>
                        <select wire:model.live="settlementYear"
                            class="rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-700 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-300">
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
            <div x-data="{ open: false }">
                <button type="button" @click="open = !open"
                    class="w-full flex items-center justify-between px-3 py-2 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-800">
                    <span class="text-sm font-bold text-gray-500 dark:text-gray-400">
                        <x-heroicon-o-clipboard-document-list class="inline h-4 w-4" />
                        سجل حركات القاصة ({{ count($cr['transactions']) }})
                    </span>
                    <x-heroicon-o-chevron-down class="h-4 w-4 text-gray-500 transition-transform" x-bind:class="open ? 'rotate-180' : ''" />
                </button>

                <div x-show="open" x-collapse>
                    <div class="overflow-x-auto mt-2">
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

                    @php $yearlyPct = number_format($personal['yearly_progress'], 1, '.', ''); @endphp
                    <div>
                        <div class="flex items-center justify-between mb-1">
                            <span class="text-sm text-gray-600 dark:text-gray-400">نسبة الإنجاز</span>
                            <span class="text-lg font-bold" style="color: {{ $progressColor($personal['yearly_progress']) }};">
                                {{ $personal['yearly_progress'] }}%
                            </span>
                        </div>
                        <div class="w-full rounded-full h-4 overflow-hidden bg-gray-200 dark:bg-gray-700">
                            <div class="h-4 rounded-full" style="width: {{ $yearlyPct }}%; background-color: {{ $progressColor($personal['yearly_progress']) }};"></div>
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

                    @php $monthlyPct = number_format(min($personal['monthly_progress'], 100), 1, '.', ''); @endphp
                    <div>
                        <div class="flex items-center justify-between mb-1">
                            <span class="text-sm text-gray-600 dark:text-gray-400">نسبة الإنجاز</span>
                            <span class="text-lg font-bold" style="color: {{ $progressColor($personal['monthly_progress']) }};">
                                {{ $personal['monthly_progress'] }}%
                            </span>
                        </div>
                        <div class="w-full rounded-full h-4 overflow-hidden bg-gray-200 dark:bg-gray-700">
                            <div class="h-4 rounded-full" style="width: {{ $monthlyPct }}%; background-color: {{ $progressColor($personal['monthly_progress']) }};"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-filament-panels::page>
