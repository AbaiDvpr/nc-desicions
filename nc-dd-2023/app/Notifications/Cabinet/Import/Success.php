<?php declare(strict_types=1);

namespace App\Notifications\Cabinet\Import;

use App\Notifications\AlertStatuses;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

/**
 * Класс для информационного уведомления об импорте
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
            'message' => 'Импортируемый файл успешно загружен',
            'context' => null
        ];
    }
}
