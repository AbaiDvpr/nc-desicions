<?php

namespace App\Modules\Http;

use Psr\Http\Message\RequestInterface;

interface JsonClientInterface extends RequestCreatorInterface
{
    /**
     * Выполняет переданный запрос. Возвращает декодированный json.
     *
     * @param  \Psr\Http\Message\RequestInterface $request
     * @return array
     */
    public function requestJson(RequestInterface $request): array;
}
