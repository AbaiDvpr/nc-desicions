<?php declare(strict_types=1);

namespace App\Providers;

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;

class ComponentsServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        Blade::component('paginate', \App\View\Backend\Components\Paginate::class);
        Blade::component('notify', \App\View\Backend\Components\Notify::class);

        Blade::componentNamespace('App\\View\\Inputs', 'input');

        $this->registerFrontendComponents();
    }

    /**
     * Реситрации компонентов для фронтенда
     *
     * @return void
     */
    private function registerFrontendComponents(): void
    {
        Blade::component('nav-header', \App\View\Frontend\Components\NavHeader::class);
        Blade::component('sidebar-news', \App\View\Frontend\Modules\SidebarNews::class);
    }
}
