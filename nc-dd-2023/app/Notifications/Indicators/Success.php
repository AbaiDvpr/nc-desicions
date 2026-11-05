<?php declare(strict_types=1);

namespace App\Notifications\Indicators;

use Illuminate\Bus\Queueable;
use App\Notifications\AlertStatuses;
use Illuminate\Notifications\Notification;

/**
 * Класс для уведомления об успешном получении показателей
 */
class Success extends Notification
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
            'alert'   => $this->getAlertSuccess(),
            'message' => 'Показатели успешно получены',
            'context' => null
        ];
    }
}
