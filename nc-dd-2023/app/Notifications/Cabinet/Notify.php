<?php declare(strict_types=1);

namespace App\Notifications\Cabinet;

use App\Notifications\AlertStatuses;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

/**
 * Класс для информационного уведомления о получении показателей
 */
class Notify extends Notification
{
    use Queueable, AlertStatuses;

    /**
     * Get the notification’s delivery channels.
     *
     * @return array
     */
    public function via(): array
    {
        return ['database'];
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array
     */
    public function toArray(): array
    {
        return [
            'alert'   => $this->getAlertInfo(),
            'message' => 'Новое сообщение пользователю',
            'context' => null
        ];
    }
}
