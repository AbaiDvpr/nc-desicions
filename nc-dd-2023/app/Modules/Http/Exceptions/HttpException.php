<?php
namespace App\Modules\Http\Exceptions;

use Psr\Http\Client\RequestExceptionInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * Сервер ответил статусом 4хх-5xx
 */
abstract class HttpException extends BaseException implements RequestExceptionInterface
{
    /**
     * {@inheritDoc}
     *
     * @param \Psr\Http\Message\RequestInterface  $request
     * @param \Psr\Http\Message\ResponseInterface $response
     */
    public function __construct(RequestInterface $request, ResponseInterface $response)
    {
        $this->request  = $request;
        $this->response = $response;

        parent::__construct(
            'HTTP ответ с кодом ' . $response->getStatusCode(),
            $response->getStatusCode()
        );
    }

    /**
     * {@inheritDoc}
     *
     * Ппереопределяем, потому что при ошибках HTTP информация о запросе доступна всегда (не может быть null)
     *
     * @return \Psr\Http\Message\RequestInterface
     */
    public function getRequest(): RequestInterface
    {
        return $this->request;
    }
}
