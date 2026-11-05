<?php declare(strict_types=1);

namespace App\Providers;

use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        $this->loadHelpers();

        $this->app->singleton('report', function() {
            return new \App\Components\Report\Report;
        });
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        if ($this->app->environment('production')) {
            \URL::forceScheme('https');
        }
    }

    /**
     * Load helpers.
     */
    protected function loadHelpers()
    {
        foreach (glob(app_path('/Helpers/*.php')) as $filename) {
            require_once $filename;
        }
    }
}
