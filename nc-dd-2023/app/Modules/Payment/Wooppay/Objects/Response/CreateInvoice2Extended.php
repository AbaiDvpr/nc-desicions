<?php
declare(strict_types=1);

namespace App\Modules\Payment\Wooppay\Objects\Response;

use \App\Modules\Payment\Wooppay\Objects\Request\CreateInvoiceExtended;

/**
 * Create invoice extended response.
 */
class CreateInvoice2Extended extends Base
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
     * @see \App\Modules\Payment\Wooppay\Objects\Response\CreateInvoice2Extended::$operationId
     *
     * @return integer
     */
    public function getOperationId(): int
    {
        return $this->operationId;
    }

    /**
     * @see \App\Modules\Payment\Wooppay\Objects\Response\CreateInvoice2Extended::$operationId
     *
     * @param  integer                                                              $operationId
     * @return \App\Modules\Payment\Wooppay\Objects\Response\CreateInvoice2Extended
     */
    public function setOperationId(int $operationId): self
    {
        $this->operationId = $operationId;

        return $this;
    }

    /**
     * @see \App\Modules\Payment\Wooppay\Objects\Response\CreateInvoice2Extended::$operationUrl
     *
     * @return string
     */
    public function getOperationUrl(): string
    {
        return $this->operationUrl;
    }

    /**
     * @see \App\Modules\Payment\Wooppay\Objects\Response\CreateInvoice2Extended::$operationUrl
     *
     * @param  string                                                               $operationUrl
     * @return \App\Modules\Payment\Wooppay\Objects\Response\CreateInvoice2Extended
     */
    public function setOperationUrl(string $operationUrl): self
    {
        $this->operationUrl = $operationUrl;

        return $this;
    }

    /**
     * @see \App\Modules\Payment\Wooppay\Objects\Request\CreateInvoiceExtended::$cardForbidden
     *
     * @return string
     */
    public function getCardForbidden()
    {
        return $this->cardForbidden;
    }

    /**
     * @see \App\Modules\Payment\Wooppay\Objects\Request\CreateInvoiceExtended::$cardForbidden
     *
     * @param  string                                                              $cardForbidden
     * @return \App\Modules\Payment\Wooppay\Objects\Request\CreateInvoiceByService
     */
    public function setCardForbidden($cardForbidden)
    {
        $this->cardForbidden = $cardForbidden;

        return $this;
    }

    /**
     * @inheritdoc
     *
     * @param  mixed                                                                $data
     * @return \App\Modules\Payment\Wooppay\Objects\Response\CreateInvoice2Extended
     * @throws \App\Modules\Payment\Wooppay\Objects\Exceptions\BadCredentials
     * @throws \App\Modules\Payment\Wooppay\Objects\Exceptions\LowBalance
     * @throws \App\Modules\Payment\Wooppay\Objects\Exceptions\UnsuccessfulResponse
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
