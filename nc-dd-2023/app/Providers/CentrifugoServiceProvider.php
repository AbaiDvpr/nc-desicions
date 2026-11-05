<?php declare(strict_types=1);

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use phpcent\Client;

/**
 * Провайдер для подключения к Centrifuge websocket
 */
class CentrifugoServiceProvider extends ServiceProvider
{
    /**
     * Регистрирует клиента для подключения к centrifuge
     *
     * @return void
     */
    public function register(): void
    {
        $this->app->singleton('centrifugoClient', function () {
            $client = new Client(
                sprintf('%s/api', config('websocket.host'))
            );
            $client->setApiKey(config('websocket.apiKey'));
            $client->setConnectTimeoutOption(3000);
            $client->setTimeoutOption(3000);

            return $client;
        });
    }
}
