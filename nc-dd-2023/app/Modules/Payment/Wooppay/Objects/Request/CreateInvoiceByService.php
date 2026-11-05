<?php declare(strict_types=1);

namespace App\Modules\Payment\Wooppay\Objects\Request;

/**
 * Создание счета по запросу на обслуживание
 */
class CreateInvoiceByService extends CreateInvoiceExtended
{
    /**
     * Наименование услуги
     *
     * @var string
     */
    protected string $serviceName;

    /**
     * Параметр оплаты только с кошелька или нет
     *
     * @var int
     */
    protected int $cardForbidden;

    public function getServiceName(): string
    {
        return $this->serviceName;
    }

    public function setServiceName(string $serviceName): self
    {
        $this->serviceName = $serviceName;

        return $this;
    }

    public function getCardForbidden(): int
    {
        return $this->cardForbidden;
    }

    public function setCardForbidden(int $cardForbidden): self
    {
        $this->cardForbidden = $cardForbidden;

        return $this;
    }
}
