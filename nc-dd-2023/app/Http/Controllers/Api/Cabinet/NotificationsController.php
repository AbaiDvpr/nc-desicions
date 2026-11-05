<?php declare(strict_types=1);

namespace App\Http\Controllers\Api\Cabinet;

use App\DI;
use App\Http\Controllers\Api\BaseController;
use Illuminate\Http\JsonResponse;

/**
 * Контроллер для работы с уведомлениями пользователя
 */
class NotificationsController extends BaseController
{
    /**
     * Возвращает список непрочитанных уведомлений
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getUnreadNotifications(): JsonResponse
    {
        return response()->json(
            DI::getRepository()->getUser()->getUnreadNotifications($this->client->getAuthIdentifier())
        );
    }

    /**
     * Помечает прочитанным уведомление пользователя
     *
     * @param  string                        $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function markAsReadNotificationAction(string $id): JsonResponse
    {
        $isMark = DI::getRepository()->getUser()->markAsReadNotification(
            $this->client->getAuthIdentifier(),
            $id
        );

        return response()->json([
            'mark'          => $isMark,
            'notifications' => DI::getRepository()->getUser()
                ->getUnreadNotifications($this->client->getAuthIdentifier()),
        ]);
    }
}
