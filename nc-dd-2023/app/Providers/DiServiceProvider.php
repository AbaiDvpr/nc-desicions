<?php declare(strict_types=1);

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class DiServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register(): void
    {
        $this->app->singleton('apiClient', function () {
            $client = new \App\Modules\Http\Api\Client([
                'base_uri'        => config('app.api'),
                'connect_timeout' => 0.000001,
                'allow_redirects' => false,
                //'proxy'           => '192.168.16.1:10'

                'defaultHeaders' => [
                    'x-auth-token' => "asdasd"
                ]
            ]);

            return $client;
        });

        /**
         * Клиент для проведения платежей через Wooppay
         */
        $this->app->singleton('wooppayClient', function () {
            return new \App\Modules\Payment\Wooppay\Client(config('wooppay.endpoint'));
        });

        /**
         * Клиент для отправки сообщений в телеграм
         */
        $this->app->singleton('adminTelegram', function () {
            return new \Telegram\Bot\Api();
        });

        $this->app->singleton('recaptchaClient', function () {
            return new \App\Services\Google\Recaptcha(config('google.recaptcha'));
        });
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        //
    }
}
