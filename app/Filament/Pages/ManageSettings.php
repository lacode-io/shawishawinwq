<?php

namespace App\Filament\Pages;

use App\Enums\CustomerStatus;
use App\Enums\PaymentType;
use App\Jobs\SendOverduePaymentReminder;
use App\Jobs\SendPaymentDueTodayReminder;
use App\Jobs\SendPaymentDueTomorrowReminder;
use App\Models\Customer;
use App\Models\Setting;
use App\Services\WhatsApp\WhatsAppManager;
use Carbon\Carbon;
use Filament\Actions;
use Filament\Forms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Http;

class ManageSettings extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-cog-6-tooth';

    protected static ?int $navigationSort = 100;

    protected static string $view = 'filament.pages.manage-settings';

    public static function canAccess(): bool
    {
        return auth()->user()->hasPermissionTo('manage_settings');
    }

    /** @var array<string, mixed> */
    public ?array $data = [];

    public static function getNavigationLabel(): string
    {
        return __('Settings');
    }

    public static function getNavigationGroup(): ?string
    {
        return 'الإعدادات';
    }

    public function getTitle(): string
    {
        return __('General Settings');
    }

    protected const TEST_PHONE = '+9647712699961';

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('previewUpcomingReminders')
                ->label('عرض الاشعارات القادمة')
                ->icon('heroicon-o-list-bullet')
                ->color('info')
                ->modalHeading('الاشعارات الحالية والقادمة (الـ 30 يوم القادمة)')
                ->modalSubmitAction(false)
                ->modalCancelActionLabel('إغلاق')
                ->modalWidth('5xl')
                ->modalContent(fn () => view('filament.pages.preview-reminders', [
                    'events' => $this->buildUpcomingReminderEvents(30),
                ])),

            Actions\Action::make('runDailyRemindersNow')
                ->label('تخطيط اشعارات اليوم الآن')
                ->icon('heroicon-o-bolt')
                ->color('warning')
                ->requiresConfirmation()
                ->modalHeading('تخطيط اشعارات اليوم يدوياً')
                ->modalDescription('سيتم تشغيل whatsapp:plan-day فوراً وحساب الاشعارات المستحقة اليوم وإدراجها بانتظار الإرسال. الاشعارات تنرسل تدريجياً عبر المهمة المجدولة كل دقيقة.')
                ->modalSubmitActionLabel('تخطيط الآن')
                ->action(function (): void {
                    $this->runDailyRemindersNow();
                }),

            Actions\Action::make('simulateAllReminders')
                ->label('محاكاة اشعارات كل الزبائن')
                ->icon('heroicon-o-paper-airplane')
                ->color('success')
                ->requiresConfirmation()
                ->modalHeading('محاكاة اشعارات الزبائن')
                ->modalDescription('سيتم إرسال اشعار تذكير لكل زبون فعّال (قبل يوم + بيوم التسديد + متأخر) — الكل يُوجَّه إلى '.self::TEST_PHONE.' عبر قائمة الانتظار.')
                ->modalSubmitActionLabel('بدء المحاكاة')
                ->action(function (): void {
                    $this->simulateAllReminders();
                }),
        ];
    }

    protected function runDailyRemindersNow(): void
    {
        $exitCode = \Illuminate\Support\Facades\Artisan::call('whatsapp:plan-day');
        $output = trim(\Illuminate\Support\Facades\Artisan::output());

        if ($exitCode !== 0) {
            Notification::make()
                ->title('فشل تخطيط الاشعارات')
                ->body($output ?: 'حدث خطأ غير معروف.')
                ->danger()
                ->send();

            return;
        }

        Notification::make()
            ->title('تم تخطيط اشعارات اليوم')
            ->body('سيتم إرسالها تدريجياً عبر المهمة المجدولة كل دقيقة. يمكنك متابعتها من صفحة "الاشعارات".')
            ->success()
            ->send();
    }

    /**
     * Build the list of notification events for the next $days days.
     * Returns events sorted by date, each containing customer, type, date.
     *
     * @return array<int, array{date: \Carbon\Carbon, customer: Customer, type: string, type_label: string, type_color: string}>
     */
    protected function buildUpcomingReminderEvents(int $days = 30): array
    {
        $today = now()->startOfDay();
        $horizon = $today->copy()->addDays($days);

        $customers = Customer::query()
            ->where('status', CustomerStatus::Active)
            ->where('is_platform', false)
            ->whereNotNull('phone')
            ->orderBy('full_name')
            ->get();

        $events = [];

        foreach ($customers as $customer) {
            $due = $customer->next_due_date;

            if (! $due) {
                continue;
            }

            $dueDay = $due->copy()->startOfDay();

            // التواريخ الفعلية المتوقعة ضمن نطاق الـ horizon (تكرار شهري للأقساط)
            $occurrences = $this->buildDueOccurrences($customer, $dueDay, $today, $horizon);

            foreach ($occurrences as $occurrence) {
                $tomorrowReminderAt = $occurrence->copy()->subDay();
                if ($tomorrowReminderAt->betweenIncluded($today, $horizon)) {
                    $events[] = [
                        'date' => $tomorrowReminderAt,
                        'customer' => $customer,
                        'type' => 'tomorrow',
                        'type_label' => 'تذكير قبل يوم',
                        'type_color' => 'warning',
                    ];
                }

                if ($occurrence->betweenIncluded($today, $horizon)) {
                    $events[] = [
                        'date' => $occurrence->copy(),
                        'customer' => $customer,
                        'type' => 'today',
                        'type_label' => 'تذكير بيوم التسديد',
                        'type_color' => 'primary',
                    ];
                }
            }

            if ($dueDay->lt($today) && $customer->is_late) {
                $events[] = [
                    'date' => $today->copy(),
                    'customer' => $customer,
                    'type' => 'overdue',
                    'type_label' => 'تنبيه متأخر (يومي)',
                    'type_color' => 'danger',
                ];
            }
        }

        usort($events, fn (array $a, array $b): int => $a['date']->timestamp <=> $b['date']->timestamp);

        return $events;
    }

    /**
     * @return array<int, Carbon>
     */
    private function buildDueOccurrences(Customer $customer, Carbon $dueDay, Carbon $today, Carbon $horizon): array
    {
        $occurrences = [];

        if ($customer->payment_type === PaymentType::LumpSum) {
            return [$dueDay->copy()];
        }

        $cursor = $dueDay->copy();
        $maxIterations = 24;

        while ($cursor->lt($today) && $maxIterations-- > 0) {
            if ($customer->payment_type === PaymentType::DurationBased) {
                $cursor->addDays($customer->lump_sum_days ?? 30);
            } else {
                $cursor->addMonth();
            }
        }

        $maxIterations = 24;
        while ($cursor->lte($horizon) && $maxIterations-- > 0) {
            $occurrences[] = $cursor->copy();

            if ($customer->payment_type === PaymentType::DurationBased) {
                $cursor->addDays($customer->lump_sum_days ?? 30);
            } else {
                $cursor->addMonth();
            }
        }

        return $occurrences;
    }

    protected function simulateAllReminders(): void
    {
        $manager = app(WhatsAppManager::class);
        $manager->reset();

        if (! $manager->isEnabled()) {
            Notification::make()
                ->title('إشعارات واتساب معطلة')
                ->body('فعّل الإشعارات أولاً من الإعدادات.')
                ->warning()
                ->send();

            return;
        }

        $customers = Customer::query()
            ->where('status', CustomerStatus::Active)
            ->where('is_platform', false)
            ->get();

        if ($customers->isEmpty()) {
            Notification::make()
                ->title('لا يوجد زبائن فعّالون')
                ->warning()
                ->send();

            return;
        }

        $dispatched = 0;

        foreach ($customers as $customer) {
            SendPaymentDueTomorrowReminder::dispatch($customer, self::TEST_PHONE);
            SendPaymentDueTodayReminder::dispatch($customer, self::TEST_PHONE);
            SendOverduePaymentReminder::dispatch($customer, self::TEST_PHONE);
            $dispatched += 3;
        }

        Notification::make()
            ->title('تم جدولة '.$dispatched.' اشعار تجريبي')
            ->body('لـ '.$customers->count().' زبون — كلها تذهب إلى '.self::TEST_PHONE)
            ->success()
            ->send();
    }

    public function mount(): void
    {
        $settings = Setting::instance();

        $formData = $settings->toArray();

        // Flatten whatsapp_provider_config for the form
        $config = $settings->whatsapp_provider_config ?? [];
        $formData['wa_provider'] = $config['provider'] ?? 'fake';
        $formData['wa_enabled'] = ($config['enabled'] ?? false) === true || ($config['enabled'] ?? '') === '1';
        $formData['wa_admin_phone'] = $config['admin_phone'] ?? '';
        $formData['wa_twilio_account_sid'] = $config['account_sid'] ?? '';
        $formData['wa_twilio_auth_token'] = $config['auth_token'] ?? '';
        $formData['wa_twilio_from_number'] = $config['from_number'] ?? '';
        $formData['wa_cloud_phone_number_id'] = $config['phone_number_id'] ?? '';
        $formData['wa_cloud_access_token'] = $config['access_token'] ?? '';
        $formData['wa_silsila_api_key'] = $config['silsila_api_key'] ?? '';
        $formData['wa_silsila_base_url'] = $config['silsila_base_url'] ?? 'https://silsila.lacode.io';
        $formData['wa_silsila_session_id'] = $config['silsila_session_id'] ?? '';
        $formData['wa_silsila_channel_id'] = $config['silsila_channel_id'] ?? '';

        $this->form->fill($formData);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make(__('General Settings'))
                    ->schema([
                        Forms\Components\TextInput::make('site_name')
                            ->label(__('Site Name'))
                            ->required(),

                        Forms\Components\FileUpload::make('logo_path')
                            ->label(__('Logo'))
                            ->image()
                            ->directory('settings')
                            ->disk('public'),
                    ])->columns(2),

                Forms\Components\Section::make('رأس المال')
                    ->icon('heroicon-o-banknotes')
                    ->schema([
                        Forms\Components\TextInput::make('cash_capital')
                            ->label('رأس المال الكاش')
                            ->helperText('المبلغ الأساسي لرأس المال النقدي (يدوي)')
                            ->required()
                            ->numeric()
                            ->default(0)
                            ->suffix('د.ع'),

                        Forms\Components\TextInput::make('extra_capital')
                            ->label('مبلغ إضافي على رأس المال الكاش')
                            ->helperText('رقم يُضاف على رأس المال الكاش ويظهر في اللوحة المالية')
                            ->numeric()
                            ->default(0)
                            ->suffix('د.ع'),

                        Forms\Components\TextInput::make('yearly_target_amount')
                            ->label('التاركت السنوي')
                            ->helperText('المبلغ المستهدف سنوياً (الفائض بعد المصاريف والمستثمرين)')
                            ->required()
                            ->numeric()
                            ->default(0)
                            ->suffix('د.ع'),
                    ])->columns(3),

                Forms\Components\Section::make(__('Colors'))
                    ->schema([
                        Forms\Components\ColorPicker::make('primary_color')
                            ->label(__('Primary Color'))
                            ->required(),

                        Forms\Components\ColorPicker::make('secondary_color')
                            ->label(__('Secondary Color'))
                            ->required(),
                    ])->columns(2),

                Forms\Components\Section::make('إعدادات واتساب')
                    ->icon('heroicon-o-chat-bubble-left-ellipsis')
                    ->schema([
                        Forms\Components\Toggle::make('wa_enabled')
                            ->label('تفعيل إشعارات واتساب')
                            ->helperText('عند التفعيل، سيتم إرسال التذكيرات والإشعارات عبر واتساب')
                            ->columnSpanFull(),

                        Forms\Components\Select::make('wa_provider')
                            ->label('المزود')
                            ->options([
                                'twilio' => 'Twilio WhatsApp',
                                'cloud_api' => 'WhatsApp Cloud API (Meta)',
                                'silsila' => 'Silsila',
                                'fake' => 'وضع الاختبار (لا إرسال فعلي)',
                            ])
                            ->default('fake')
                            ->required()
                            ->live(),

                        Forms\Components\TextInput::make('wa_admin_phone')
                            ->label('رقم هاتف المدير (للتنبيهات)')
                            ->helperText('مثال: 07701234567 أو 9647701234567')
                            ->tel(),

                        // ── Twilio ──
                        Forms\Components\Fieldset::make('Twilio')
                            ->schema([
                                Forms\Components\TextInput::make('wa_twilio_account_sid')
                                    ->label('Account SID')
                                    ->password()
                                    ->revealable(),

                                Forms\Components\TextInput::make('wa_twilio_auth_token')
                                    ->label('Auth Token')
                                    ->password()
                                    ->revealable(),

                                Forms\Components\TextInput::make('wa_twilio_from_number')
                                    ->label('From Number')
                                    ->helperText('+14155238886')
                                    ->tel(),
                            ])
                            ->visible(fn (Forms\Get $get): bool => $get('wa_provider') === 'twilio')
                            ->columns(3),

                        // ── Cloud API ──
                        Forms\Components\Fieldset::make('WhatsApp Cloud API')
                            ->schema([
                                Forms\Components\TextInput::make('wa_cloud_phone_number_id')
                                    ->label('Phone Number ID')
                                    ->password()
                                    ->revealable(),

                                Forms\Components\TextInput::make('wa_cloud_access_token')
                                    ->label('Access Token')
                                    ->password()
                                    ->revealable(),
                            ])
                            ->visible(fn (Forms\Get $get): bool => $get('wa_provider') === 'cloud_api')
                            ->columns(2),

                        // ── Silsila ──
                        Forms\Components\Fieldset::make('Silsila')
                            ->schema([
                                Forms\Components\TextInput::make('wa_silsila_api_key')
                                    ->label('API Key')
                                    ->helperText('مفتاح X-Api-Key من لوحة Silsila')
                                    ->password()
                                    ->revealable()
                                    ->live(onBlur: true)
                                    ->columnSpan(2),

                                Forms\Components\TextInput::make('wa_silsila_base_url')
                                    ->label('Base URL')
                                    ->default('https://silsila.lacode.io')
                                    ->url()
                                    ->live(onBlur: true)
                                    ->columnSpan(2),

                                Forms\Components\Select::make('wa_silsila_session_id')
                                    ->label('الجلسة')
                                    ->helperText('اختر الجلسة من Silsila (اضغط تحديث لإعادة الجلب)')
                                    ->options(fn (Forms\Get $get): array => $this->fetchSilsilaSessions(
                                        (string) $get('wa_silsila_api_key'),
                                        (string) ($get('wa_silsila_base_url') ?: 'https://silsila.lacode.io'),
                                    ))
                                    ->searchable()
                                    ->suffixAction(
                                        Forms\Components\Actions\Action::make('refreshSilsilaSessions')
                                            ->icon('heroicon-o-arrow-path')
                                            ->tooltip('تحديث قائمة الجلسات')
                                            ->action(function (Forms\Get $get): void {
                                                $this->fetchSilsilaSessions(
                                                    (string) $get('wa_silsila_api_key'),
                                                    (string) ($get('wa_silsila_base_url') ?: 'https://silsila.lacode.io'),
                                                    forceRefresh: true,
                                                );

                                                Notification::make()
                                                    ->title('تم تحديث قائمة الجلسات')
                                                    ->success()
                                                    ->send();
                                            })
                                    ),

                                Forms\Components\TextInput::make('wa_silsila_channel_id')
                                    ->label('Channel ID')
                                    ->helperText('رقم القناة (اختياري إذا تم تحديد الجلسة)')
                                    ->numeric(),
                            ])
                            ->visible(fn (Forms\Get $get): bool => $get('wa_provider') === 'silsila')
                            ->columns(2),
                    ])->columns(2),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        $data = $this->form->getState();

        $whatsappConfig = [
            'provider' => $data['wa_provider'] ?? 'fake',
            'enabled' => $data['wa_enabled'] ?? false,
            'admin_phone' => $data['wa_admin_phone'] ?? '',
            'account_sid' => $data['wa_twilio_account_sid'] ?? '',
            'auth_token' => $data['wa_twilio_auth_token'] ?? '',
            'from_number' => $data['wa_twilio_from_number'] ?? '',
            'phone_number_id' => $data['wa_cloud_phone_number_id'] ?? '',
            'access_token' => $data['wa_cloud_access_token'] ?? '',
            'silsila_api_key' => $data['wa_silsila_api_key'] ?? '',
            'silsila_base_url' => $data['wa_silsila_base_url'] ?? 'https://silsila.lacode.io',
            'silsila_session_id' => $data['wa_silsila_session_id'] ?? '',
            'silsila_channel_id' => $data['wa_silsila_channel_id'] ?? '',
        ];

        $settings = Setting::instance();
        $oldCashCapital = (int) $settings->cash_capital;
        $newCashCapital = (int) ($data['cash_capital'] ?? 0);

        $settings->update([
            'site_name' => $data['site_name'],
            'logo_path' => $data['logo_path'],
            'primary_color' => $data['primary_color'],
            'secondary_color' => $data['secondary_color'],
            'cash_capital' => $newCashCapital,
            'extra_capital' => (int) ($data['extra_capital'] ?? 0),
            'yearly_target_amount' => (int) ($data['yearly_target_amount'] ?? 0),
            'whatsapp_provider_config' => $whatsappConfig,
        ]);

        // Log cash capital change
        if ($oldCashCapital !== $newCashCapital) {
            activity()
                ->performedOn($settings)
                ->causedBy(auth()->user())
                ->withProperties([
                    'old' => ['cash_capital' => $oldCashCapital],
                    'attributes' => ['cash_capital' => $newCashCapital],
                    'difference' => $newCashCapital - $oldCashCapital,
                ])
                ->event('updated')
                ->log('تم تعديل رأس المال الكاش من ' . number_format($oldCashCapital) . ' إلى ' . number_format($newCashCapital));

            // Flush finance cache
            app(\App\Services\FinanceService::class)->flush();
        }

        app(WhatsAppManager::class)->reset();

        Notification::make()
            ->title(__('Saved successfully.'))
            ->success()
            ->send();
    }

    /**
     * Fetch WhatsApp sessions from the Silsila API for the session dropdown.
     *
     * @return array<string, string>
     */
    protected function fetchSilsilaSessions(string $apiKey, string $baseUrl, bool $forceRefresh = false): array
    {
        if ($apiKey === '') {
            return [];
        }

        static $cache = [];
        $cacheKey = md5($apiKey.'|'.$baseUrl);

        if (! $forceRefresh && isset($cache[$cacheKey])) {
            return $cache[$cacheKey];
        }

        try {
            $response = Http::withHeaders([
                'X-Api-Key' => $apiKey,
                'Accept' => 'application/json',
            ])->timeout(10)->get(rtrim($baseUrl, '/').'/api/v1/whatsapp/sessions');

            if (! $response->successful()) {
                return $cache[$cacheKey] = [];
            }

            $sessions = collect($response->json('data') ?? [])
                ->mapWithKeys(function (array $session): array {
                    $sessionId = $session['session_id'] ?? null;
                    if (! $sessionId) {
                        return [];
                    }

                    $label = collect([
                        $session['name'] ?? null,
                        $session['phone'] ?? null,
                        '['.($session['status'] ?? 'unknown').']',
                    ])->filter()->implode(' — ');

                    return [$sessionId => $label !== '' ? $label : $sessionId];
                })
                ->all();

            return $cache[$cacheKey] = $sessions;
        } catch (\Throwable $e) {
            return $cache[$cacheKey] = [];
        }
    }
}
