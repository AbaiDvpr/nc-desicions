<?php declare(strict_types=1);

namespace App\Modules\Payment\Wooppay\Objects\Request;

/**
 * Create invoice extended request.
 */
class CreateInvoiceExtended
{
    /**
     * Default service type.
     */
    const DEFAULT_SERVICE_TYPE = 2;

    /**
     * ID заказа на стороне мерчанта
     *
     * @var string
     */
    protected string $referenceId;

    /**
     * Адрес, на который будет переадресован пользователь после оплаты
     *
     * @var string
     */
    protected string $backUrl;

    /**
     * Запрос, который мы высылаем вам после успешной оплаты
     *
     * @var string
     */
    protected string $requestUrl;

    /**
     * Строка - что увидит клиент в форме оплаты
     *
     * @var string
     */
    protected string $addInfo;

    /**
     * Сумма выставляемого счета.
     * Тип данных – вещественное беззнаковое число с разделителем-точкой (можно передавать целые числа)
     *
     * @var float
     */
    protected float $amount;

    /**
     * Время, когда операция становится недействительной.
     * Тип данных – строка с датой (ГГГГ-ММ-ДД ЧЧ:ММ:СС)
     *
     * @var string
     */
    protected string $deathDate;

    /**
     * Service type.
     *
     * @var integer
     */
    protected int $serviceType;

    /**
     * Короткий необязательный комментарий, который попадет в историю операций клиента
     *
     * @var string
     */
    protected string $description;

    /**
     * Идентификатор заказа. Тип данных – целое беззнаковое число (не используется)
     *
     * @var string
     */
    protected string $orderNumber;

    /**
     * Адрес электронной почты клиента (будет отображаться в форме при оплате платежной картой)
     *
     * @var string
     */
    protected string $userEmail;

    /**
     * Телефон клиента (будет отображаться в форме при оплате платежной картой)
     *
     * @var string
     */
    protected string $userPhone;

    public function getReferenceId(): string
    {
        return $this->referenceId;
    }

    public function setReferenceId(string $referenceId): self
    {
        $this->referenceId = $referenceId;

        return $this;
    }

    public function getBackUrl(): string
    {
        return $this->backUrl;
    }

    public function setBackUrl(string $backUrl): self
    {
        $this->backUrl = $backUrl;

        return $this;
    }

    public function getRequestUrl(): string
    {
        return $this->requestUrl;
    }

    public function setRequestUrl(string $requestUrl): self
    {
        $this->requestUrl = $requestUrl;

        return $this;
    }

    public function getAddInfo(): string
    {
        return $this->addInfo;
    }

    public function setAddInfo(string $addInfo): self
    {
        $this->addInfo = $addInfo;

        return $this;
    }

    public function getAmount(): float
    {
        return $this->amount;
    }

    public function setAmount(float $amount): self
    {
        $this->amount = $amount;

        return $this;
    }

    public function getDeathDate(): string
    {
        return $this->deathDate;
    }

    public function setDeathDate(string $deathDate): self
    {
        $this->deathDate = $deathDate;

        return $this;
    }

    public function getServiceType(): int
    {
        return $this->serviceType;
    }

    public function setServiceType(int $serviceType): self
    {
        $this->serviceType = $serviceType;

        return $this;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function setDescription(string $description): self
    {
        $this->description = $description;

        return $this;
    }

    public function getOrderNumber(): string
    {
        return $this->orderNumber;
    }

    public function setOrderNumber(string $orderNumber): self
    {
        $this->orderNumber = $orderNumber;

        return $this;
    }

    public function getUserEmail(): string
    {
        return $this->userEmail;
    }

    public function setUserEmail(string $userEmail): self
    {
        $this->userEmail = $userEmail;

        return $this;
    }

    public function getUserPhone(): string
    {
        return $this->userPhone;
    }

    public function setUserPhone(string $userPhone): self
    {
        $this->userPhone = $userPhone;

        return $this;
    }
}
