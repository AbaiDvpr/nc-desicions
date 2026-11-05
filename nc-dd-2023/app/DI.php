<?php declare(strict_types=1);

namespace App;

use App\Components\ComponentLoader;
use App\DataHandlers\DataHandlerLoader as DHLoader;
use App\Repositories\RepositoryLoader;
use App\Services\Centrifugo\Client;
use App\Services\Google\Recaptcha;
use Illuminate\Support\Facades\Log;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use App\Modules\Http\Api\Client as ApiClient;

/**
 * Самопальный DI
 */
class DI
{
    /**
     * Получение Обработчиков данных
     *
     * @return \App\DataHandlers\DataHandlerLoader
     */
    public static function getDH(): DHLoader
    {
        return new DHLoader();
    }

    /**
     * Загрузка репозиториев
     *
     * @return \App\Repositories\RepositoryLoader
     */
    public static function getRepository(): RepositoryLoader
    {
        return new RepositoryLoader();
    }

    /**
     * Загрузчик компонентов
     *
     * @return \App\Components\ComponentLoader
     */
    public static function getComponent(): ComponentLoader
    {
        return new ComponentLoader();
    }

    /**
     * Возвращает клиента для запросов в наш АПИ
     *
     * @return \App\Modules\Http\Api\Client|null
     */
    public static function getApiClient(): ?ApiClient
    {
        try {
            /** @var $apiClient \App\Modules\Http\Api\Client */
            $apiClient = app()->get('apiClient');

            return $apiClient;
        } catch (NotFoundExceptionInterface|ContainerExceptionInterface $exception) {
            Log::error("API клиент загрузить не удалось", [
                'context' => $exception
            ]);
        }

        return null;
    }

    /**
     * Клиент для проведения платежей через Wooppay
     *
     * @return \App\Modules\Payment\Wooppay\Client|null
     * @throws \App\Modules\Payment\Wooppay\Objects\Exceptions\BadCredentials
     * @throws \App\Modules\Payment\Wooppay\Objects\Exceptions\LowBalance
     * @throws \App\Modules\Payment\Wooppay\Objects\Exceptions\UnsuccessfulResponse
     */
    public static function getWooppayClient(): ?Modules\Payment\Wooppay\Client
    {
        try {
            /** @var $wooppayClient \App\Modules\Payment\Wooppay\Client */
            $wooppayClient = app()->get('wooppayClient');

            $wooppayClient->login(
                config('wooppay.username'),
                config('wooppay.password')
            );

            return $wooppayClient;
        } catch (NotFoundExceptionInterface|ContainerExceptionInterface $exception) {
            Log::error("Клиент WoopPay загрузить не удалось", [
                'context' => $exception
            ]);
        }

        return null;
    }

    /**
     * Клиент для отправки сообщений в телеграм канал для админов
     *
     * @return \Telegram\Bot\Api|null
     */
    public static function getAdminTelegram(): ?\Telegram\Bot\Api
    {
        try {
            /** @var $telegramBot \Telegram\Bot\Api */
            $telegramBot = app()->get('adminTelegram');

            $telegramBot->setAccessToken(config('telegram.bots.admin.token'));

            return $telegramBot;
        } catch (\Throwable $throwable) {
            Log::error("Клиент Telegram бота загрузить не удалось", [
                'context' => $throwable
            ]);
        }

        return null;
    }

    /**
     * Возвращает клиента для работы с centrifugo
     *
     * @return \App\Services\Centrifugo\Client
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    public static function getCentrifugoService(): Client
    {
        return new Client(app()->get('centrifugoClient'));
    }

    /**
     * Возвращает клиента для работы с recaptcha
     *
     * @return \App\Services\Google\Recaptcha
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    public static function getRecaptcha(): Recaptcha
    {
        return app()->get('recaptchaClient');
    }
}
