<?php declare(strict_types=1);

namespace App\Modules\Http\Exceptions;


/**
 * Исключение, выбрасываемое, когда ответ от JSON API содержит ошибку.
 * От InvalidResponseException отличается тем, что здесь мы получили валидный json,
 * но в нём находятся некорректные данные или какой-то код ошибки.
 */
class BadResponseJsonException extends BaseException
{
    /**
     * Ответ от сервиса
     *
     * @var array
     */
    protected $responseJson;

    /**
     * Конструктор
     *
     * @param string $message
     * @param array  $responseJson
     */
    public function __construct(string $message, array $responseJson)
    {
        $this->responseJson = $responseJson;

        parent::__construct($message);
    }

    /**
     * Возвращает содержимое ответа в json
     *
     * @return array
     */
    public function getResponseJson(): array
    {
        return $this->responseJson;
    }

    /**
     * @inheritDoc
     *
     * @return array[]
     */
    public function getLogContext(): array
    {
        return [
            'serviceResponseJson' => $this->getResponseJson(),
        ];
    }
}
