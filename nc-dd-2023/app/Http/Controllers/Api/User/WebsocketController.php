<?php declare(strict_types=1);

namespace App\Http\Controllers\Api\User;

use App\DI;
use App\Http\Controllers\Api\BaseController;
use App\Models\User;
use Illuminate\Http\JsonResponse;

/**
 * Контроллер для данных подключения и работы с Centrifugo
 */
class WebsocketController extends BaseController
{
    /**
     * Возвращает конфиги для фронта
     *
     * @return \Illuminate\Http\JsonResponse
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    public function getConfig(): JsonResponse
    {
        $centrifugo = DI::getCentrifugoService();

        return response()->json([
            'baseUrl'  => config('websocket.apiUrl'),
            'token'    => $centrifugo->getConnectionToken($this->client->getAuthIdentifier()),
            'channels' => [
                'notification' => $this->getNotificationConfig($this->client),
            ],
        ]);
    }

    /**
     * Возвращает конфиги пользователя для подключения к каналу уведомлений
     *
     * @param  \App\Models\User                           $user
     * @return array
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    private function getNotificationConfig(User $user)
    {
        $centrifugo = DI::getCentrifugoService();
        $chanelName = $centrifugo->getNotificationName($user->getAuthIdentifier());

        return [
            'name'  => $chanelName,
            'token' => $centrifugo->getSubscriptionToken(
                $user->getAuthIdentifier(),
                $chanelName
            ),
        ];
    }
}
