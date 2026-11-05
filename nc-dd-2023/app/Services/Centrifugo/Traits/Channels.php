<?php declare(strict_types=1);

namespace App\Services\Centrifugo\Traits;

/**
 * Трейт для работы с каналами сообщений
 */
trait Channels
{
    /**
     * Канал для отправки уведомлений пользователю
     */
    public string $notificationName = 'notification';

    /**
     * Возвращает наименование канала пользователя для получения уведомлений
     *
     * @param  int    $userId
     * @return string
     */
    public function getNotificationName(int $userId): string
    {
        return sprintf(
            '%s-%s',
            $this->notificationName,
            md5('nс' . $userId)
        );
    }
}
