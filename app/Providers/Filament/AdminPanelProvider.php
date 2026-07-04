<?php

namespace App\Providers\Filament;

use App\Filament\Widgets\PatientSearchWidget;
use App\Filament\Widgets\TodayOncallWidget;
use App\Filament\Widgets\UpcomingMdtWidget;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\NavigationGroup;
use Filament\Pages;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Support\Enums\MaxWidth;
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
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->login()

            // Branding
            ->brandName('PCCEKAU')
            ->colors([
                'primary' => Color::Blue,
            ])

            // Layout
            ->maxContentWidth(MaxWidth::Full)
            ->sidebarCollapsibleOnDesktop()

            // Resources, pages, widgets
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\\Filament\\Pages')
            ->pages([
                Pages\Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\\Filament\\Widgets')
            ->widgets([
                PatientSearchWidget::class,
                TodayOncallWidget::class,
                UpcomingMdtWidget::class,
            ])

            // Explicit navigation group order
            ->navigationGroups([
                NavigationGroup::make('Patients')
                    ->icon('heroicon-o-users'),
                NavigationGroup::make('Clinical')
                    ->icon('heroicon-o-heart'),
                NavigationGroup::make('Schedules')
                    ->icon('heroicon-o-calendar'),
                NavigationGroup::make('Admin')
                    ->icon('heroicon-o-shield-check'),
            ])

            // In-app notifications (daily digest, etc.)
            ->databaseNotifications()
            ->databaseNotificationsPolling('60s')

            // Global search
            ->globalSearchKeyBindings(['command+k', 'ctrl+k'])
            ->globalSearchDebounce('300ms')

            // SPA mode
            ->spa()

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
