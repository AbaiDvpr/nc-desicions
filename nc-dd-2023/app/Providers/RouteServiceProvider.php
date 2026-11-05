<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;

class RouteServiceProvider extends ServiceProvider
{
    /**
     * The path to the "home" route for your application.
     *
     * This is used by Laravel authentication to redirect users after login.
     *
     * @var string
     */
    public const HOME = '/';

    /**
     * The controller namespace for the application.
     *
     * When present, controller route declarations will automatically be prefixed with this namespace.
     *
     * @var string|null
     */
    // protected $namespace = 'App\\Http\\Controllers';

    /**
     * Define your route model bindings, pattern filters, etc.
     *
     * @return void
     */
    public function boot()
    {
        $this->configureRateLimiting();

        $this->routes(function () {
            $this->setAuthRoutes();

            $this->setApiRoutes();

            $this->setBackendRoutes();

            $this->setWebRoutes();

            $this->setCabinetRoutes();
        });
    }

    /**
     * Set auth routes
     *
     * @return void
     */
    protected function setAuthRoutes(): void
    {
        Route::prefix('auth')
            ->middleware('web')
            ->namespace($this->namespace)
            ->group(base_path('routes/auth.php'));
    }

    /**
     * Set Api routes
     *
     * @return void
     */
    protected function setApiRoutes(): void
    {
        Route::domain(config('app.api'))
            ->middleware('api')
            ->namespace($this->namespace)
            ->group(base_path('routes/api.php'));
    }

    /**
     * Set Admin routes
     *
     * @return void
     */
    protected function setBackendRoutes(): void
    {
        Route::middleware(['web'])
            ->domain(config('backend.domain'))
            ->namespace($this->namespace)
            ->group(base_path('routes/backend/backend.php'));
    }

    /**
     * Set Admin routes
     *
     * @return void
     */
    protected function setWebRoutes(): void
    {
        Route::middleware('web')
            ->namespace($this->namespace)
            ->domain(config('app.domain'))
            ->group(base_path('routes/web.php'));
    }

    /**
     * Set Web routes for Personal cabinet
     *
     * @return void
     */
    protected function setCabinetRoutes(): void
    {
        Route::middleware(['web', 'cabinet'])
            ->namespace($this->namespace)
            ->group(base_path('routes/cabinet/cabinet.php'));
    }

    /**
     * Configure the rate limiters for the application.
     *
     * @return void
     */
    protected function configureRateLimiting()
    {
        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(60)->by(optional($request->user())->id ?: $request->ip());
        });
    }
}
