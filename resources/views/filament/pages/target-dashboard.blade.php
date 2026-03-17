<x-filament-panels::page>
    @php
        $data = $this->getTargetData();
        $investors = $data['investor_targets'];
        $personal = $data['personal_target'];
        $iqd = fn(int $v) => \Illuminate\Support\Number::iqd($v);

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

                        {{-- شريط التاركت الشهري (الربح فقط حسب الأشهر) --}}
                        <div class="mb-3">
                            <div class="flex items-center justify-between mb-1">
                                <span class="text-xs text-gray-500 dark:text-gray-400">
                                    تاركت الربح الشهري
                                    <span class="text-[10px] text-gray-400">({{ $inv['elapsed_months'] }} من {{ $inv['investment_months'] }} شهر)</span>
                                </span>
                                <span class="text-sm font-bold {{ $progressText($inv['monthly_profit_progress']) }}">
                                    {{ $inv['monthly_profit_progress'] }}%
                                </span>
                            </div>
                            {{-- شريط المدة --}}
                            <div class="relative w-full bg-gray-200 rounded-full h-3 dark:bg-gray-700">
                                <div class="h-3 rounded-full transition-all {{ $progressBg($inv['monthly_profit_progress']) }}"
                                     style="width: {{ $inv['monthly_profit_progress'] }}%"></div>
                                {{-- مؤشر المدة المنقضية --}}
                                @if($inv['months_progress'] > 0 && $inv['months_progress'] < 100)
                                <div class="absolute top-0 h-3 w-0.5 bg-gray-900 dark:bg-white rounded"
                                     style="left: {{ $inv['months_progress'] }}%"
                                     title="المدة المنقضية {{ $inv['months_progress'] }}%"></div>
                                @endif
                            </div>
                            <div class="flex items-center justify-between mt-1">
                                <span class="text-xs text-gray-400">المدفوع من الربح: {{ $iqd($inv['paid_profit']) }}</span>
                                <span class="text-xs text-gray-400">المتوقع: {{ $iqd($inv['expected_profit']) }}</span>
                            </div>
                        </div>

                        {{-- شريط الإنجاز الكلي --}}
                        <div>
                            <div class="flex items-center justify-between mb-1">
                                <span class="text-xs text-gray-500 dark:text-gray-400">الإنجاز الكلي (استثمار + ربح)</span>
                                <span class="text-sm font-bold {{ $progressText($inv['progress_percent']) }}">
                                    {{ $inv['progress_percent'] }}%
                                </span>
                            </div>
                            <div class="w-full bg-gray-200 rounded-full h-3 dark:bg-gray-700">
                                <div class="h-3 rounded-full transition-all {{ $progressBg($inv['progress_percent']) }}"
                                     style="width: {{ $inv['progress_percent'] }}%"></div>
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
        {{-- ── القسم الثاني: التاركت الشخصي ── --}}
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

            @if($personal['yearly_target'] > 0)
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

                        {{-- المعادلة --}}
                        <div class="rounded-lg bg-white p-3 dark:bg-gray-900 mb-4">
                            <p class="text-xs text-gray-500 dark:text-gray-400 mb-2">الفائض = الأرباح − مستحقات المستثمرين − المصاريف</p>
                            <div class="flex flex-wrap items-center justify-center gap-2 text-center text-xs">
                                <div class="rounded bg-green-50 px-3 py-1.5 dark:bg-green-900/20">
                                    <div class="text-[10px] text-gray-400">الأرباح المحققة</div>
                                    <div class="font-bold text-green-600 dark:text-green-400">{{ $iqd($personal['total_profit_earned']) }}</div>
                                </div>
                                <span class="text-lg font-bold text-gray-400">−</span>
                                <div class="rounded bg-orange-50 px-3 py-1.5 dark:bg-orange-900/20">
                                    <div class="text-[10px] text-gray-400">مستحقات المستثمرين</div>
                                    <div class="font-bold text-orange-600 dark:text-orange-400">{{ $iqd($personal['total_investor_dues']) }}</div>
                                </div>
                                <span class="text-lg font-bold text-gray-400">−</span>
                                <div class="rounded bg-red-50 px-3 py-1.5 dark:bg-red-900/20">
                                    <div class="text-[10px] text-gray-400">المصاريف الكلية</div>
                                    <div class="font-bold text-red-600 dark:text-red-400">{{ $iqd($personal['total_expenses']) }}</div>
                                </div>
                                <span class="text-lg font-bold text-gray-400">=</span>
                                <div class="rounded border {{ $personal['surplus'] >= 0 ? 'border-green-400 bg-green-50 dark:bg-green-900/20' : 'border-red-400 bg-red-50 dark:bg-red-900/20' }} px-3 py-1.5">
                                    <div class="text-[10px] text-gray-400">الفائض</div>
                                    <div class="font-bold {{ $personal['surplus'] >= 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">
                                        {{ $iqd(abs($personal['surplus'])) }}
                                        {{ $personal['surplus'] < 0 ? '(عجز)' : '' }}
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- شريط الإنجاز السنوي --}}
                        <div>
                            <div class="flex items-center justify-between mb-1">
                                <span class="text-sm text-gray-600 dark:text-gray-400">نسبة الإنجاز</span>
                                <span class="text-lg font-bold {{ $progressText($personal['yearly_progress']) }}">
                                    {{ $personal['yearly_progress'] }}%
                                </span>
                            </div>
                            <div class="w-full bg-gray-200 rounded-full h-4 dark:bg-gray-700">
                                <div class="h-4 rounded-full transition-all {{ $progressBg($personal['yearly_progress']) }}"
                                     style="width: {{ $personal['yearly_progress'] }}%"></div>
                            </div>
                            <div class="flex items-center justify-between mt-1">
                                <span class="text-xs text-gray-400">{{ $iqd(max(0, $personal['surplus'])) }}</span>
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
                            <div class="text-xs text-gray-500 dark:text-gray-400 mt-1">من التاركت السنوي ÷ 12</div>
                        </div>

                        {{-- المعادلة الشهرية --}}
                        <div class="rounded-lg bg-white p-3 dark:bg-gray-900 mb-4">
                            <p class="text-xs text-gray-500 dark:text-gray-400 mb-2">الفائض الشهري = أرباح الشهر − تاركت المستثمرين − المصاريف</p>
                            <div class="flex flex-wrap items-center justify-center gap-2 text-center text-xs">
                                <div class="rounded bg-green-50 px-3 py-1.5 dark:bg-green-900/20">
                                    <div class="text-[10px] text-gray-400">أرباح الشهر</div>
                                    <div class="font-bold text-green-600 dark:text-green-400">{{ $iqd($personal['current_month_profit']) }}</div>
                                </div>
                                <span class="text-lg font-bold text-gray-400">−</span>
                                <div class="rounded bg-orange-50 px-3 py-1.5 dark:bg-orange-900/20">
                                    <div class="text-[10px] text-gray-400">تاركت المستثمرين</div>
                                    <div class="font-bold text-orange-600 dark:text-orange-400">{{ $iqd($personal['current_month_investor_target']) }}</div>
                                </div>
                                <span class="text-lg font-bold text-gray-400">−</span>
                                <div class="rounded bg-red-50 px-3 py-1.5 dark:bg-red-900/20">
                                    <div class="text-[10px] text-gray-400">مصاريف الشهر</div>
                                    <div class="font-bold text-red-600 dark:text-red-400">{{ $iqd($personal['current_month_expenses']) }}</div>
                                </div>
                                <span class="text-lg font-bold text-gray-400">=</span>
                                <div class="rounded border {{ $personal['monthly_surplus'] >= 0 ? 'border-green-400 bg-green-50 dark:bg-green-900/20' : 'border-red-400 bg-red-50 dark:bg-red-900/20' }} px-3 py-1.5">
                                    <div class="text-[10px] text-gray-400">الفائض</div>
                                    <div class="font-bold {{ $personal['monthly_surplus'] >= 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">
                                        {{ $iqd(abs($personal['monthly_surplus'])) }}
                                        {{ $personal['monthly_surplus'] < 0 ? '(عجز)' : '' }}
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- شريط الإنجاز الشهري --}}
                        <div>
                            <div class="flex items-center justify-between mb-1">
                                <span class="text-sm text-gray-600 dark:text-gray-400">نسبة الإنجاز</span>
                                <span class="text-lg font-bold {{ $progressText($personal['monthly_progress']) }}">
                                    {{ $personal['monthly_progress'] }}%
                                </span>
                            </div>
                            <div class="w-full bg-gray-200 rounded-full h-4 dark:bg-gray-700">
                                <div class="h-4 rounded-full transition-all {{ $progressBg($personal['monthly_progress']) }}"
                                     style="width: {{ $personal['monthly_progress'] }}%"></div>
                            </div>
                            <div class="flex items-center justify-between mt-1">
                                <span class="text-xs text-gray-400">{{ $iqd(max(0, $personal['monthly_surplus'])) }}</span>
                                <span class="text-xs text-gray-400">{{ $iqd($personal['monthly_target']) }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            @else
                <div class="rounded-lg border border-yellow-200 bg-yellow-50 p-6 text-center dark:border-yellow-800 dark:bg-yellow-900/20">
                    <x-heroicon-o-exclamation-triangle class="mx-auto h-10 w-10 text-yellow-500" />
                    <p class="mt-2 text-sm font-medium text-yellow-700 dark:text-yellow-400">لم يتم تحديد التاركت السنوي بعد</p>
                    <a href="{{ \App\Filament\Pages\ManageSettings::getUrl() }}"
                       class="mt-3 inline-flex items-center gap-1.5 rounded-lg bg-yellow-100 px-4 py-2 text-sm font-medium text-yellow-800 hover:bg-yellow-200 dark:bg-yellow-900/40 dark:text-yellow-300 dark:hover:bg-yellow-900/60">
                        <x-heroicon-o-cog-6-tooth class="h-4 w-4" />
                        تحديد التاركت من الإعدادات
                    </a>
                </div>
            @endif
        </div>
    </div>
</x-filament-panels::page>
