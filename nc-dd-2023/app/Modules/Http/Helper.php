<?php

namespace App\Modules\Http;

use App\Modules\Http\Exceptions\InvalidResponseException;
use Psr\Http\Message\MessageInterface;
use Psr\Http\Message\ResponseInterface;

class Helper
{
    /**
     * Преобразует ответ из JSON строки в массив
     *
     * @param  \Psr\Http\Message\ResponseInterface                   $response
     * @return  array
     * @throws \App\Modules\Http\Exceptions\InvalidResponseException
     */
    public static function getResponseJSON(ResponseInterface $response): array
    {
        $responseStr = self::getBodySummary($response);

        if (!$responseStr) {
            throw new InvalidResponseException($response, "Не удалось прочитать ответ или ответ пустой");
        }

        $data = json_decode($responseStr, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new InvalidResponseException($response, json_last_error_msg(), $responseStr);
        }

        if (!is_array($data)) {
            throw new InvalidResponseException($response, 'Полученный JSON не является массивом', $responseStr);
        }

        return $data;
    }

    /**
     * Возвращает тело запроса\ответа в виде строки.
     * Реализация скопирована из guzzle.
     *
     * @param  \Psr\Http\Message\MessageInterface $message
     * @param  int                                $truncateAt - если > 0, то тело будет обрезано
     * @return string|null
     */
    public static function getBodySummary(MessageInterface $message, int $truncateAt = 0): ?string
    {
        $body = $message->getBody();

        if (!is_string($body)) {
            if (!$body->isSeekable() || !$body->isReadable()) {
                return null;
            }

            $size = $body->getSize();

            if ($size === 0) {
                return null;
            }

            if ($truncateAt) {
                // читаем часть тела
                $summary = $body->read($truncateAt);
            } else {
                // читаем всё тело
                $summary = $body->getContents();
            }

            $body->rewind();
        } else {
            $summary = $body;
        }

        if ($truncateAt > 0 && $size > $truncateAt) {
            $summary .= sprintf(' [...%d more bytes]', $size - $truncateAt);
        }

        return $summary;
    }
}
