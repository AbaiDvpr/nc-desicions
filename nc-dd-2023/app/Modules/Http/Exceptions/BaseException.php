<?php declare(strict_types=1);

namespace App\Modules\Http\Exceptions;

use App\Modules\Http\Helper;
use Psr\Http\Message\MessageInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * Базовое исключение HTTP клиента
 */
abstract class BaseException extends \Exception
{
    /**
     * Запрос, при котором произошло исключение
     *
     * @var \Psr\Http\Message\RequestInterface|null
     */
    protected $request;

    /**
     * Ответ, полученный при выполнении запроса (доступен не во всех исключениях)
     *
     * @var \Psr\Http\Message\ResponseInterface|null
     */
    protected $response;

    /**
     * Строка с телом ответа
     *
     * @var string|null
     */
    protected $responseString;

    /**
     * @see \App\Modules\Http\Exceptions\BaseException::$request
     *
     * @return \Psr\Http\Message\RequestInterface|null
     */
    public function getRequest(): ?RequestInterface
    {
        return $this->request;
    }

    /**
     * @see \App\Modules\Http\Exceptions\BaseException::$request
     *
     * @param \Psr\Http\Message\RequestInterface $request
     */
    public function setRequest(RequestInterface $request)
    {
        $this->request = $request;
    }

    /**
     * @see \App\Modules\Http\Exceptions\BaseException::$response
     *
     * @return \Psr\Http\Message\ResponseInterface|null
     */
    public function getResponse(): ?ResponseInterface
    {
        return $this->response;
    }

    /**
     * @see \App\Modules\Http\Exceptions\BaseException::$responseString
     *
     * @return string|null
     */
    public function getResponseString(): ?string
    {
        if ($this->responseString === null && $this->response) {
            $this->responseString = Helper::getBodySummary($this->response);
        }

        return $this->responseString;
    }

    /**
     * Возвращает контекст для логирования ошибки
     *
     * @return array
     */
    public function getLogContext(): array
    {
        return [];
    }
}
