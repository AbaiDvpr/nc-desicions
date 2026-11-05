<?php

namespace App\Modules\Payment\Wooppay\Objects\Response;

/**
 * Create invoice extended response.
 */
class CreateInvoiceExtended extends Base
{
    /**
     * Operation id.
     *
     * @var integer
     */
    protected $operationId;

    /**
     * Payment form URI.
     *
     * @var string
     */
    protected $operationUrl;

    /**
     * @see \App\Modules\Payment\Wooppay\Objects\Response\CreateInvoiceExtended::$operationId
     *
     * @return integer
     */
    public function getOperationId()
    {
        return $this->operationId;
    }

    /**
     * @see \App\Modules\Payment\Wooppay\Objects\Response\CreateInvoiceExtended::$operationId
     *
     * @param  integer                                         $operationId
     * @return \Wooppay\Objects\Response\CreateInvoiceExtended
     */
    public function setOperationId($operationId)
    {
        $this->operationId = $operationId;

        return $this;
    }

    /**
     * @see \App\Modules\Payment\Wooppay\Objects\Response\CreateInvoiceExtended::$operationUrl
     *
     * @return string
     */
    public function getOperationUrl()
    {
        return $this->operationUrl;
    }

    /**
     * @see \App\Modules\Payment\Wooppay\Objects\Response\CreateInvoiceExtended::$operationUrl
     *
     * @param  string                                          $operationUrl
     * @return \Wooppay\Objects\Response\CreateInvoiceExtended
     */
    public function setOperationUrl($operationUrl)
    {
        $this->operationUrl = $operationUrl;

        return $this;
    }

    /**
     * {@inheritdoc}
     *
     * @param  mixed                                            $data
     * @return \Wooppay\Objects\Response\CreateInvoiceExtended
     * @throws \Wooppay\Objects\Exceptions\BadCredentials
     * @throws \Wooppay\Objects\Exceptions\UnsuccessfulResponse
     */
    public static function factory($data)
    {
        $response = new self();

        if (self::checkResponse($data)) {
            $response
                ->setOperationId($data->response->operationId)
                ->setOperationUrl($data->response->operationUrl);
        }

        return $response;
    }
}
