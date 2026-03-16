<?php

namespace App\Filament\Pages;

use App\Models\Setting;
use App\Services\WhatsApp\WhatsAppManager;
use Filament\Forms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;

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
                    ]),

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
}
