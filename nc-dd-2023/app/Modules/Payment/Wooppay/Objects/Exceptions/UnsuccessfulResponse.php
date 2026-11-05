<?php

namespace App\Modules\Payment\Wooppay\Objects\Exceptions;

/**
 * Unsuccessful response exception.
 */
class UnsuccessfulResponse extends Exception
{
    /**
     * {@inheritdoc}
     *
     * @var string
     */
    protected $message = 'Unsuccessful response.';

    /**
     * Response from wooppay
     *
     * @var \stdClass
     */
    protected $response = [];

    /**
     * {@inheritdoc}
     *
     * @param \stdClass       $response
     * @param string          $message
     * @param integer         $code
     * @param \Exception|null $previous
     */
    public function __construct(\stdClass $response, $message = "", $code = 0, \Exception $previous = null)
    {
        if (is_numeric($message) && $code === 0) {
            $this->code = $message;
        }

        $this->response = $response;

        parent::__construct($this->message, $this->code, $previous);
    }

    /**
     * Returns response from Wooppay
     *
     * @return \stdClass
     */
    public function getResponse()
    {
        return $this->response;
    }
}
