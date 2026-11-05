<?php declare(strict_types=1);

namespace App\Modules\Http\Api\Methods;

use App\Modules\Http\Exceptions\BadResponseJsonException;
use App\Modules\Http\RequestCreatorInterface;
use App\Modules\Http\RequestParameters;
use Psr\Http\Message\RequestInterface;

class Health
{
    /**
     * Формирует запрос
     *
     * @param  \App\Modules\Http\RequestCreatorInterface $requestCreator
     * @return \Psr\Http\Message\RequestInterface
     */
    public function getRequest(RequestCreatorInterface $requestCreator): RequestInterface
    {
        return $requestCreator->createRequest(
            'get',
            '/health',
            (new RequestParameters())
        );
    }

    /**
     * Формирует ответ
     *
     * @param  array                                                 $response
     * @return void
     * @throws \App\Modules\Http\Exceptions\BadResponseJsonException
     */
    public function parseResponse(array $response): void
    {
        if (!isset($response['status'])) {
            throw new BadResponseJsonException('В ответе нет свойства status', $response);
        }
    }
}
