<?php

namespace App\Modules\Payment\Wooppay\Objects\Exceptions;

use App\Modules\Payment\Wooppay\Objects\Response\Base as BaseResponse;

/**
 * Bad credentials exception.
 */
class BadCredentials extends Exception
{
    /**
     * {@inheritdoc}
     *
     * @var int
     */
    protected $code = BaseResponse::ERROR_CODE_BAD_CREDENTIALS;

    /**
     * {@inheritdoc}
     *
     * @var string
     */
    protected $message = 'Bad credentials.';
}
