<?php

namespace App\Providers\Filament;

use App\Filament\Pages\CompanyDashboard;
use App\Filament\Pages\FieldOfficerDashboard;
use App\Filament\Pages\ZoneDashboard;
use App\Http\Middleware\ResolveActingPermissions;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Support\Facades\FilamentColor;
use Filament\Widgets\AccountWidget;
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
            ->brandName('Chabrin Lease System')
            ->brandLogo(fn () => view('components.brand-logo'))
            ->darkModeBrandLogo(fn () => view('components.brand-logo'))
            ->favicon(asset('images/Chabrin-Logo-background.png'))
            ->colors([
                // Chabrin Company Colors
                'primary' => [
                    50 => '250, 245, 230',   // Lightest gold tint
                    100 => '253, 240, 200',
                    200 => '250, 225, 150',
                    300 => '240, 200, 100',
                    400 => '230, 180, 60',
                    500 => '218, 165, 32',   // #DAA520 - Main Gold
                    600 => '190, 140, 25',
                    700 => '160, 115, 20',
                    800 => '130, 90, 15',
                    900 => '100, 70, 10',
                    950 => '70, 50, 5',
                ],
                'gray' => Color::Slate,
                'danger' => Color::Rose,
                'info' => [
                    50 => '240, 245, 255',
                    100 => '225, 235, 250',
                    200 => '195, 215, 240',
                    300 => '150, 180, 220',
                    400 => '100, 140, 190',
                    500 => '26, 54, 93',     // #1a365d - Navy Blue
                    600 => '22, 45, 78',
                    700 => '18, 36, 62',
                    800 => '14, 27, 47',
                    900 => '10, 18, 31',
                    950 => '5, 9, 16',
                ],
                'success' => Color::Emerald,
                'warning' => Color::Amber,
            ])
            ->font('Inter')
            ->sidebarCollapsibleOnDesktop()
            ->sidebarWidth('16rem')
            ->collapsedSidebarWidth('4.5rem')
            ->darkMode(false)
            ->viteTheme('resources/css/filament/admin/theme.css')
            ->globalSearchKeyBindings(['command+k', 'ctrl+k'])
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\Filament\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\Filament\Pages')
            ->pages([
                CompanyDashboard::class,
                ZoneDashboard::class,
                FieldOfficerDashboard::class,
            ])
            ->homeUrl('/admin/company-dashboard')
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\Filament\Widgets')
            ->widgets([
                AccountWidget::class,
            ])
            ->databaseNotifications()
            ->databaseNotificationsPolling('30s')
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
                ResolveActingPermissions::class,
                \App\Http\Middleware\RoleBasedDashboardRedirect::class,
            ]);
    }
}
