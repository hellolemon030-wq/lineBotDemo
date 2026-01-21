<?php

namespace App\Providers\Filament;

use App\Services\Laravel\BotFeatureModule\ModuleManager;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\NavigationItem;
use Filament\Pages\Dashboard;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Widgets\AccountWidget;
use Filament\Widgets\FilamentInfoWidget;
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
        $panel
            ->id('admin')
            ->path('admin')
            ->login()
            ->colors([
                'primary' => Color::Amber,
            ])
            ->discoverResources(in: app_path('Filament/Admin/Resources'), for: 'App\Filament\Admin\Resources')
            ->discoverPages(in: app_path('Filament/Admin/Pages'), for: 'App\Filament\Admin\Pages')
            ->pages([
                Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Admin/Widgets'), for: 'App\Filament\Admin\Widgets')
            ->widgets([
                AccountWidget::class,
                FilamentInfoWidget::class,
            ])
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
            ])

            ->discoverResources(
                in: app_path('Services/Laravel/BotFeatureModule/BotAccountModule'), 
                for: 'App\\Services\\Laravel\\BotFeatureModule\\BotAccountModule'
            )
            
            // ->navigationItems([
            //     NavigationItem::make('数据分析中心') // 菜单项名称
            //         ->url('/admin/analytics') // 链接到的 URL
            //         ->icon('heroicon-o-chart-pie') // 使用的图标
            //         ->group('主要功能') // 可以选择分组
            //         ->sort(1), // 排序

            //     NavigationItem::make('系统设置')
            //         ->url('/admin/settings')
            //         ->icon('heroicon-o-adjustments-horizontal')
            //         ->group('主要功能')
            //         ->sort(2),

            //     NavigationItem::make('外部链接示例')
            //         ->url('https://filamentphp.com', shouldOpenInNewTab: true) // 外部链接并在新标签页打开
            //         ->icon('heroicon-o-arrow-top-right-on-square')
            //         ->group('链接')
            //         ->sort(3),
            // ])
            
            ;


            $moduleManager = app()->get(ModuleManager::class);
            $moduleManager->_initModuleFilament2Panel($panel);
            
            return $panel;
    }
}
