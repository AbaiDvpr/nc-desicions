<?php declare(strict_types=1);

namespace App\Listeners\Cabinet;

use App\DI;
use App\Models\User;
use Illuminate\Notifications\Events\NotificationSent;
use Illuminate\Support\Facades\Log;

/**
 * Обработчик отправки уведомлений пользователю
 */
class CentrifugoNotifier
{
    /**
     * Обработчик слушателя для отправки уведомления
     *
     * @param \Illuminate\Notifications\Events\NotificationSent $event
     * @return void
     */
    public function handle(NotificationSent $event): void
    {
        try {
            if (!(new $event->response->notifiable_type instanceof User)) {
                return;
            }

            $user = DI::getRepository()->getUser()->findById($event->response->notifiable_id);

            // Если роль пользователя не клиентская, то ничего не отправляем
            if (!$user || !$user->hasRole(User::ROLE_CLIENT)) {
                return;
            }

            // отправляет клиенту уведомление в centrifugo
            DI::getCentrifugoService()->sendNotification(
                $user->getAuthIdentifier(),
                $event->response->only(['id', 'data'])
            );
        } catch (\Throwable $throwable) {
            Log::error('Ошибка отправки уведомления в канал centrifugo', [
                'exception' => $throwable,
                'response'  => $event->response,
            ]);
        }
    }
}
