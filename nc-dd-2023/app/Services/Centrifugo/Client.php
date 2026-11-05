<?php declare(strict_types=1);

namespace App\Services\Centrifugo;

use App\Services\Centrifugo\Traits\Channels;
use phpcent\Client as CentrifugoClient;

/**
 * Сервис для работы с Centrifugo
 */
class Client
{
    use Channels;

    /**
     * Клиент для работы с Centrifugo
     *
     * @var \phpcent\Client
     */
    private CentrifugoClient $client;

    /**
     * Конструктор
     *
     * @param \phpcent\Client $client
     */
    public function __construct(CentrifugoClient $client)
    {
        $this->client = $client;
    }

    /**
     * Возвращает токен для подключения пользователя
     *
     * @param  int    $userId
     * @return string
     */
    public function getConnectionToken(int $userId): string
    {
        return $this->client->setSecret(config('websocket.secretKey'))
            ->generateConnectionToken($userId);
    }

    /**
     * Возвращает токен для подписки на канал
     *
     * @param  int    $userId
     * @param  string $chanel
     * @return string
     */
    public function getSubscriptionToken(int $userId, string $chanel): string
    {
        return $this->client->setSecret(config('websocket.secretKey'))
            ->generateSubscriptionToken($userId, $chanel);
    }

    /**
     * Отправляет сообщение в канал
     *
     * @param  string $chanel
     * @param  mixed  $message
     * @return mixed
     */
    public function publish(string $chanel, mixed $message): mixed
    {
        return $this->client->publish($chanel, $message);
    }

    /**
     * Отправляет сообщение сразу в несколько каналов
     *
     * @param  array $channels
     * @param  array $message
     * @return mixed
     */
    public function broadcast(array $channels, array $message): mixed
    {
        return $this->client->broadcast($channels, [
            'message' => $message,
        ]);
    }

    /**
     * Отправляет сообщение о новом уведомлении
     *
     * @param  int   $userId
     * @param  mixed $message
     * @return mixed
     */
    public function sendNotification(int $userId, mixed $message): mixed
    {
        return $this->publish($this->getNotificationName($userId), $message);
    }
}
