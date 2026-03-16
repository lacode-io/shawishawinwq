<?php

namespace App\Providers\Filament;

use App\Models\Setting;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\NavigationGroup;
use Filament\Pages;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        $primaryColor = rescue(
            fn () => Color::hex(Setting::query()->find(1)?->primary_color ?? '#0ea5e9'),
            Color::Sky,
            false,
        );

        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->login()
            ->brandName('شوي شوي')
            ->brandLogo(fn () => view('filament.admin.logo'))
            ->brandLogoHeight('2.5rem')
            ->font('IBM Plex Sans Arabic')
            ->colors([
                'primary' => $primaryColor,
                'gray' => Color::Slate,
            ])
            ->darkMode(true)
            ->sidebarCollapsibleOnDesktop()
            ->globalSearchKeyBindings(['command+k', 'ctrl+k'])
            ->navigationGroups([
                NavigationGroup::make('الإدارة المالية'),
                NavigationGroup::make('الملاحظات والجرد'),
                NavigationGroup::make('التقارير')
                    ->collapsible(false),
                NavigationGroup::make('الإعدادات')
                    ->collapsed(),
            ])
            ->viteTheme(
                'resources/css/filament/admin/theme.css',
                'build/filament',
            )
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\\Filament\\Pages')
            ->pages([])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\\Filament\\Widgets')
            ->widgets([])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ]);
    }
}
