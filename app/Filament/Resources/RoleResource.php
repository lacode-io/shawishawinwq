<?php

namespace App\Filament\Resources;

use App\Filament\Resources\RoleResource\Pages;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Spatie\Permission\Models\Role;

class RoleResource extends Resource
{
    protected static ?string $model = Role::class;

    protected static ?string $navigationIcon = 'heroicon-o-shield-check';

    protected static ?int $navigationSort = 2;

    public static function getModelLabel(): string
    {
        return __('Role');
    }

    public static function getPluralModelLabel(): string
    {
        return __('Roles');
    }

    public static function getNavigationLabel(): string
    {
        return __('Roles');
    }

    public static function getNavigationGroup(): ?string
    {
        return 'الإعدادات';
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make()
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label(__('Name'))
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(255),

                        Forms\Components\Select::make('guard_name')
                            ->label(__('Guard Name'))
                            ->options([
                                'web' => 'Web',
                            ])
                            ->default('web')
                            ->required(),
                    ])->columns(2),

                Forms\Components\Section::make(__('Permissions'))
                    ->schema([
                        Forms\Components\CheckboxList::make('permissions')
                            ->label('')
                            ->relationship('permissions', 'name')
                            ->getOptionLabelFromRecordUsing(fn ($record) => self::translatePermission($record->name))
                            ->columns(3)
                            ->gridDirection('row')
                            ->searchable(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label(__('Name'))
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('guard_name')
                    ->label(__('Guard Name'))
                    ->badge()
                    ->color('gray'),

                Tables\Columns\TextColumn::make('permissions_count')
                    ->label(__('Permissions'))
                    ->counts('permissions')
                    ->badge()
                    ->color('primary'),

                Tables\Columns\TextColumn::make('created_at')
                    ->label(__('Created At'))
                    ->dateTime('Y/m/d h:i A')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function translatePermission(string $name): string
    {
        $translations = [
            'view_customers' => 'عرض الزبائن',
            'create_customers' => 'إنشاء الزبائن',
            'update_customers' => 'تعديل الزبائن',
            'delete_customers' => 'حذف الزبائن',
            'view_customer_payments' => 'عرض دفعات الزبائن',
            'create_customer_payments' => 'إنشاء دفعات الزبائن',
            'update_customer_payments' => 'تعديل دفعات الزبائن',
            'delete_customer_payments' => 'حذف دفعات الزبائن',
            'view_investors' => 'عرض المستثمرين',
            'create_investors' => 'إنشاء المستثمرين',
            'update_investors' => 'تعديل المستثمرين',
            'delete_investors' => 'حذف المستثمرين',
            'view_investor_payouts' => 'عرض دفعات المستثمرين',
            'create_investor_payouts' => 'إنشاء دفعات المستثمرين',
            'update_investor_payouts' => 'تعديل دفعات المستثمرين',
            'delete_investor_payouts' => 'حذف دفعات المستثمرين',
            'view_expenses' => 'عرض المصاريف',
            'create_expenses' => 'إنشاء المصاريف',
            'update_expenses' => 'تعديل المصاريف',
            'delete_expenses' => 'حذف المصاريف',
            'view_finance_closings' => 'عرض الإغلاقات المالية',
            'create_finance_closings' => 'إنشاء الإغلاقات المالية',
            'update_finance_closings' => 'تعديل الإغلاقات المالية',
            'delete_finance_closings' => 'حذف الإغلاقات المالية',
            'view_users' => 'عرض المستخدمين',
            'create_users' => 'إنشاء المستخدمين',
            'update_users' => 'تعديل المستخدمين',
            'delete_users' => 'حذف المستخدمين',
            'view_roles' => 'عرض الأدوار',
            'create_roles' => 'إنشاء الأدوار',
            'update_roles' => 'تعديل الأدوار',
            'delete_roles' => 'حذف الأدوار',
            'view_app_notes' => 'عرض الملاحظات',
            'create_app_notes' => 'إنشاء الملاحظات',
            'update_app_notes' => 'تعديل الملاحظات',
            'delete_app_notes' => 'حذف الملاحظات',
            'view_activity_log' => 'عرض سجل النشاطات',
            'manage_settings' => 'إدارة الإعدادات',
            'export_pdf' => 'تصدير PDF',
            'view_finance_dashboard' => 'عرض لوحة المالية',
            'mark_completed' => 'تحديد كمكتمل',
        ];

        return $translations[$name] ?? $name;
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListRoles::route('/'),
            'create' => Pages\CreateRole::route('/create'),
            'edit' => Pages\EditRole::route('/{record}/edit'),
        ];
    }
}
