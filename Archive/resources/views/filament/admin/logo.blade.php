<div class="flex items-center gap-2">
    @php
        $settings = \App\Models\Setting::query()->find(1);
    @endphp
    @if($settings?->logo_path)
        <img
            src="{{ Storage::disk('public')->url($settings->logo_path) }}"
            alt="{{ $settings->site_name }}"
            class="h-8"
        >
    @endif
    <span class="text-xl font-bold text-primary-600 dark:text-primary-400">
        {{ $settings?->site_name ?? 'شوي شوي' }}
    </span>
</div>
