<?php

namespace App\Modules\Payment\Wooppay;

use App\Modules\Payment\Wooppay\Objects\Request;
use App\Modules\Payment\Wooppay\Objects\Response;

/**
 * Wooppay SOAP client.
 */
class Client extends \SoapClient
{
    /**
     * Default options.
     *
     * @var array
     */
    protected array $defaults = [
        'connection_timeout' => 60,
        'cache_wsdl'         => WSDL_CACHE_MEMORY,
        'trace'              => 1,
        'soap_version'       => SOAP_1_2,
        'encoding'           => 'UTF-8',
        'exceptions'         => true,
    ];

    /**
     * A flag indicating whether a client has logged in
     *
     * @var bool
     */
    protected bool $isLoggedIn = false;

    /**
     * Конструктор клиента
     *
     * @param  null       $wsdl
     * @param  array      $options
     * @throws \SoapFault
     */
    public function __construct($wsdl = null, array $options = [])
    {
        $options = array_merge($this->defaults, $options);

        parent::__construct($wsdl, $options);
    }

    /**
     * Calls a SOAP function
     *
     * @param string $name
     * @param array  $args
     * @param array  $options
     * @param mixed  $inputHeaders
     * @param array  $outputHeaders
     * @return mixed
     */
    public function __soapCall($name, $args, $options = null, $inputHeaders = null, &$outputHeaders = null)
    {
        $result = parent::__soapCall($name, $args, $options, $inputHeaders,$outputHeaders);
        $result->raw_result = $this->__getLastResponse();

        return $result;
    }

    /**
     * Авторизует пользователя в Wooppay.
     *
     * @param  string                                                               $username
     * @param  string                                                               $password
     * @return \App\Modules\Payment\Wooppay\Objects\Response\Login
     * @throws \App\Modules\Payment\Wooppay\Objects\Exceptions\BadCredentials
     * @throws \App\Modules\Payment\Wooppay\Objects\Exceptions\LowBalance
     * @throws \App\Modules\Payment\Wooppay\Objects\Exceptions\UnsuccessfulResponse
     */
    public function login(string $username, string $password): Response\Login
    {
        $request  = (new Request\Login())->setUsername($username)->setPassword($password);
        $result   = $this->__soapCall('core_login', [$request]);
        $response = Response\Login::factory($result);

        $this->__setCookie('session', $response->getSession());
        $this->isLoggedIn = true;

        return $response;
    }

    /**
     * Возвращает флаг, указывающий, вошел ли клиент в систему
     *
     * @return bool
     */
    public function isLoggedIn(): bool
    {
        return $this->isLoggedIn;
    }

    /**
     * Отправляет код подтверждения на телефон пользователя.
     *
     * @param  string                                                               $phone
     * @return boolean
     * @throws \App\Modules\Payment\Wooppay\Objects\Exceptions\BadCredentials
     * @throws \App\Modules\Payment\Wooppay\Objects\Exceptions\LowBalance
     * @throws \App\Modules\Payment\Wooppay\Objects\Exceptions\UnsuccessfulResponse
     */
    public function requestConfirmationCode(string $phone): bool
    {
        $request = (new Request\RequestConfirmationCode())->setPhone($phone);
        $result  = $this->__soapCall('core_requestConfirmationCode', [$request]);

        return Response\Base::checkResponse($result);
    }

    /**
     * Creates invoice.
     *
     * @param  string                                                              $referenceId
     * @param  string                                                              $backUrl
     * @param  string                                                              $requestUrl
     * @param  string                                                              $addInfo
     * @param  float                                                               $amount
     * @param  string                                                              $deathDate
     * @param  integer                                                             $serviceType
     * @param  string                                                              $description
     * @param  string                                                              $orderNumber
     * @param  string                                                              $userEmail
     * @param  string                                                              $userPhone
     * @return \App\Modules\Payment\Wooppay\Objects\Response\CreateInvoiceExtended
     * @throws \App\Modules\Payment\Wooppay\Objects\Exceptions\Exception
     */
    public function createInvoiceExtended(
        $referenceId,
        $backUrl,
        $requestUrl,
        $addInfo,
        $amount,
        $deathDate,
        $serviceType,
        $description,
        $orderNumber,
        $userEmail,
        $userPhone
    ) {
        $request = (new Request\CreateInvoiceExtended())
            ->setReferenceId($referenceId)
            ->setBackUrl($backUrl)
            ->setRequestUrl($requestUrl)
            ->setAddInfo($addInfo)
            ->setAmount($amount)
            ->setDeathDate($deathDate)
            ->setServiceType($serviceType)
            ->setDescription($description)
            ->setOrderNumber($orderNumber)
            ->setUserEmail($userEmail)
            ->setUserPhone($userPhone);

        $result = $this->__soapCall('cash_createInvoiceExtended', [$request]);

        return Response\CreateInvoiceExtended::factory($result);
    }

    /**
     * Creates invoice by service.
     *
     * @param  string                                          $referenceId
     * @param  string                                          $backUrl
     * @param  string                                          $requestUrl
     * @param  string                                          $addInfo
     * @param  float                                           $amount
     * @param  string                                          $deathDate
     * @param  integer                                         $serviceType
     * @param  string                                          $description
     * @param  string                                          $orderNumber
     * @param  string                                          $userEmail
     * @param  string                                          $userPhone
     * @param  string                                          $serviceName
     * @return \App\Modules\Payment\Wooppay\Objects\Response\CreateInvoiceExtended
     */
    public function createInvoiceByService(
        $referenceId,
        $backUrl,
        $requestUrl,
        $addInfo,
        $amount,
        $deathDate,
        $serviceType,
        $description,
        $orderNumber,
        $userEmail,
        $userPhone,
        $serviceName
    ) {
        $request = (new Request\CreateInvoiceByService())
            ->setServiceName($serviceName)
            ->setReferenceId($referenceId)
            ->setBackUrl($backUrl)
            ->setRequestUrl($requestUrl)
            ->setAddInfo($addInfo)
            ->setAmount($amount)
            ->setDeathDate($deathDate)
            ->setServiceType($serviceType)
            ->setDescription($description)
            ->setOrderNumber($orderNumber)
            ->setUserEmail($userEmail)
            ->setUserPhone($userPhone);

        $result = $this->__soapCall('cash_createInvoiceByService', [$request]);

        return Response\CreateInvoiceExtended::factory($result);
    }

    /**
     * Создание счёта для оплаты картой
     *
     * @param  string                                                               $referenceId
     * @param  string                                                               $backUrl
     * @param  string                                                               $requestUrl
     * @param  string                                                               $addInfo
     * @param  float                                                                $amount
     * @param  string                                                               $deathDate
     * @param  integer                                                              $serviceType
     * @param  string                                                               $description
     * @param  string                                                               $userEmail
     * @param  string                                                               $userPhone
     * @return \App\Modules\Payment\Wooppay\Objects\Response\CreateInvoice2Extended
     * @throws \App\Modules\Payment\Wooppay\Objects\Exceptions\Exception
     */
    public function createInvoice2Extended(
        string $referenceId,
        string $backUrl,
        string $requestUrl,
        string $addInfo,
        float $amount,
        string $deathDate,
        int $serviceType,
        string $description,
        string $userEmail,
        string $userPhone
    ): Response\CreateInvoice2Extended {
        $request = (new Request\CreateInvoice2Extended())
            ->setCardForbidden(0)
            ->setReferenceId($referenceId)
            ->setBackUrl($backUrl)
            ->setRequestUrl($requestUrl)
            ->setAddInfo($addInfo)
            ->setAmount($amount)
            ->setDeathDate($deathDate)
            ->setServiceType($serviceType)
            ->setDescription($description)
            ->setUserEmail($userEmail)
            ->setUserPhone($userPhone)
            ->setOrderNumber('');

        $result = $this->__soapCall('cash_createInvoice2Extended', [$request]);

        return Response\CreateInvoice2Extended::factory($result);
    }

    /**
     * Get operations' status.
     *
     * @param  array $ids
     * @return array
     */
    public function getOperationData(array $ids): array
    {
        $request = (new Request\GetOperationData())->setOperationId($ids);
        $result  = $this->__soapCall('cash_getOperationData', [$request]);

        return Response\GetOperationData::factory($result);
    }
}
