<?php declare(strict_types=1);

namespace App\Modules\Http;

use Psr\Http\Message\RequestInterface;

interface RequestCreatorInterface
{
    /**
     * Создаёт запрос
     *
     * @param  string                                $method
     * @param  string|\Psr\Http\Message\UriInterface $uri
     * @param  \App\Modules\Http\RequestParameters   $params
     * @return \Psr\Http\Message\RequestInterface
     */
    public function createRequest(string $method, $uri, RequestParameters $params): RequestInterface;
}
