<?php
namespace App\Modules\Http\Exceptions;

use Psr\Http\Message\ResponseInterface;

/**
 * Невалидный или неожиданный ответ.
 * От BadResponseJsonException отличается тем, что здесь мы получили ответ в невалидном формате
 * (например, html вместо json).
 */
class InvalidResponseException extends BaseException
{
    /**
     * {@inheritDoc}
     *
     * @param \Psr\Http\Message\ResponseInterface $response
     * @param string                              $message
     * @param string|null                         $responseString
     * @param \Throwable|null                     $previousException
     */
    public function __construct(
        ResponseInterface $response,
        string $message,
        string $responseString = null,
        \Throwable $previousException = null
    ) {
        $this->response       = $response;
        $this->responseString = $responseString;

        if ($responseString) {
            $message .= ': ' . $responseString;
        }

        parent::__construct($message, 0, $previousException);
    }
}
