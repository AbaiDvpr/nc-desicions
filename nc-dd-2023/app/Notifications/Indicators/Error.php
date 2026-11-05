<?php declare(strict_types=1);

namespace App\Notifications\Indicators;

use App\Notifications\AlertStatuses;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

/**
 * Класс для уведомления об ошибке получения показателей
 */
class Error extends Notification
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
            'alert'   => $this->getAlertError(),
            'message' => 'Показатели не получены',
            'context' => null
        ];
    }
}
