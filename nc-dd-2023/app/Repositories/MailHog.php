<?php declare(strict_types=1);

namespace App\Repositories;

use GuzzleHttp\Client;
use Illuminate\Support\Facades\Log;

/**
 * Репозиторий для работы с почтовым клиентом на тесте MailHog
 */
class MailHog
{
    /**
     * Клиент для отправки запросов
     *
     * @var \GuzzleHttp\Client
     */
    private mixed $mailcatcher;

    /**
     * Endpoint для получения списка последних сообщений
     *
     * @var string
     */
    private string $messageLink = '/api/v1/messages';

    /**
     * Инициализация подключения к MailHog
     */
    public function __construct()
    {
        $this->mailcatcher = new Client([
            'base_uri' => config('mail.mailHog'),
        ]);

        try {
            $this->mailcatcher->get($this->messageLink);
        } catch (\Throwable $throwable) {
            Log::error('Ошибка подключения к mailHog', [
                'exception' => $throwable,
            ]);
        }
    }

    /**
     * Возвращает последнее отправленное письмо
     *
     * @return array
     */
    public function getLastMessage(): mixed
    {
        $messages = $this->getMessages();

        if (empty($messages)) {
            return null;
        }

        return reset($messages);
    }

    /**
     * Возвращает список отправленных писем
     *
     * @return array
     */
    public function getMessages(): array
    {
        try {
            $jsonResponse = $this->mailcatcher->get($this->messageLink);

            return json_decode($jsonResponse->getBody()->getContents());
        } catch (\Throwable $throwable) {
            Log::error('Ошибка получения сообщений с MailHog', [
                'exception' => $throwable,
            ]);
        }

        return [];
    }

    /**
     * Возвращает тему последнего отправленного письма
     *
     * @return bool|string
     */
    public function getSubjectForLastMessage(): bool|string
    {
        $message = $this->getLastMessage();

        return iconv_mime_decode($message->Content->Headers->Subject[0]);
    }
}
