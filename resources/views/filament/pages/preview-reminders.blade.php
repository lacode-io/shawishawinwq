@php
    /** @var array<int, array{date: \Carbon\Carbon, customer: \App\Models\Customer, type: string, type_label: string, type_color: string}> $events */
    $today = now()->startOfDay();
@endphp

<div class="space-y-3">
    <p class="text-sm text-gray-600 dark:text-gray-400">
        إجمالي الاشعارات: <span class="font-semibold">{{ count($events) }}</span> —
        الفترة: <span class="font-semibold">{{ $today->format('Y/m/d') }}</span>
        إلى <span class="font-semibold">{{ $today->copy()->addDays(30)->format('Y/m/d') }}</span>
    </p>

    @if (empty($events))
        <div class="rounded-lg border border-dashed border-gray-300 bg-gray-50 p-6 text-center text-sm text-gray-500 dark:border-gray-700 dark:bg-gray-800/50 dark:text-gray-400">
            لا توجد اشعارات مجدولة في الـ 30 يوم القادمة.
        </div>
    @else
        <div class="overflow-x-auto rounded-lg border border-gray-200 dark:border-gray-700">
            <table class="min-w-full divide-y divide-gray-200 text-sm dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-800">
                    <tr>
                        <th class="px-4 py-2 text-right font-semibold text-gray-700 dark:text-gray-200">#</th>
                        <th class="px-4 py-2 text-right font-semibold text-gray-700 dark:text-gray-200">التاريخ</th>
                        <th class="px-4 py-2 text-right font-semibold text-gray-700 dark:text-gray-200">المتبقي</th>
                        <th class="px-4 py-2 text-right font-semibold text-gray-700 dark:text-gray-200">الزبون</th>
                        <th class="px-4 py-2 text-right font-semibold text-gray-700 dark:text-gray-200">الهاتف</th>
                        <th class="px-4 py-2 text-right font-semibold text-gray-700 dark:text-gray-200">نوع الاشعار</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 bg-white dark:divide-gray-700 dark:bg-gray-900">
                    @foreach ($events as $i => $event)
                        @php
                            $diff = (int) $today->diffInDays($event['date']->copy()->startOfDay(), false);
                            $diffLabel = match (true) {
                                $diff === 0 => 'اليوم',
                                $diff === 1 => 'غداً',
                                $diff > 1 => 'بعد ' . $diff . ' يوم',
                                default => 'منذ ' . abs($diff) . ' يوم',
                            };
                            $colorClass = match ($event['type_color']) {
                                'warning' => 'bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-200',
                                'primary' => 'bg-blue-100 text-blue-800 dark:bg-blue-900/40 dark:text-blue-200',
                                'danger' => 'bg-red-100 text-red-800 dark:bg-red-900/40 dark:text-red-200',
                                default => 'bg-gray-100 text-gray-800 dark:bg-gray-800 dark:text-gray-200',
                            };
                        @endphp
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/50">
                            <td class="px-4 py-2 text-gray-500 dark:text-gray-400">{{ $i + 1 }}</td>
                            <td class="px-4 py-2 font-medium text-gray-900 dark:text-gray-100">
                                {{ $event['date']->format('Y/m/d') }}
                            </td>
                            <td class="px-4 py-2 text-gray-500 dark:text-gray-400">{{ $diffLabel }}</td>
                            <td class="px-4 py-2 text-gray-900 dark:text-gray-100">{{ $event['customer']->full_name }}</td>
                            <td class="px-4 py-2 font-mono text-xs text-gray-600 dark:text-gray-400">{{ $event['customer']->phone }}</td>
                            <td class="px-4 py-2">
                                <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium {{ $colorClass }}">
                                    {{ $event['type_label'] }}
                                </span>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>
