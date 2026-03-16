<?php

namespace App\Providers;

use App\Services\WhatsApp\WhatsAppManager;
use Carbon\Carbon;
use Filament\Support\Facades\FilamentView;
use Filament\View\PanelsRenderHook;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Number;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(WhatsAppManager::class);
    }

    public function boot(): void
    {
        Carbon::setLocale('ar');

        // super_admin bypasses all policies
        Gate::before(fn ($user, $ability) => $user->hasRole('super_admin') ? true : null);

        Number::macro('iqd', function (float|int $number): string {
            return number_format($number, 0, '.', ',').' د.ع';
        });

        FilamentView::registerRenderHook(
            PanelsRenderHook::AUTH_LOGIN_FORM_BEFORE,
            fn (): string => Blade::render(
                '<p class="text-center text-sm text-gray-500 dark:text-gray-400 mb-4">مرحباً بك في نظام إدارة شوي شوي</p>'
            ),
        );
    }
}
