<?php declare(strict_types=1);

namespace App\Modules\Http\Api;

use App\Modules\Http\Api\Methods\Health;
use App\Modules\Http\Exceptions\BadResponseJsonException;
use App\Modules\Http\JsonServiceClient;
use App\Modules\Http\RequestParameters;
use Psr\Http\Message\RequestInterface;

/**
 * Клиент для запросов в API
 */
class Client extends JsonServiceClient implements ApiClientInterface
{

    /**
     * {@inheritDoc}
     *
     * @return string
     */
    public function getServiceName(): string
    {
        return 'api';
    }

    /**
     * @inheritdoc
     *
     * @return void
     * @throws \App\Modules\Http\Exceptions\BadResponseJsonException
     * @throws \App\Modules\Http\Exceptions\BaseException
     */
    public function health(): void
    {
        $method = new Health();

        $response = $this->requestJson($method->getRequest($this));

        try {
            $method->parseResponse($response);
        } catch (BadResponseJsonException $e) {
            $this->logException($e);

            throw $e;
        }
    }

    /**
     * {@inheritDoc}
     *
     * @param string                                $method
     * @param \Psr\Http\Message\UriInterface|string $uri
     * @return \Psr\Http\Message\RequestInterface
     */
    public function createRequest(string $method, $uri, RequestParameters $params)
    : RequestInterface {
        $params->setQuery(array_merge($params->getQuery(), [
        ]));

        return parent::createRequest($method, $uri, $params);
    }
}
