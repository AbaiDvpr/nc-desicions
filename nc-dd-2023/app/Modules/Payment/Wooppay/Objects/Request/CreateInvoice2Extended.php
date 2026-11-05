<?php declare(strict_types=1);

namespace App\Modules\Payment\Wooppay\Objects\Request;

/**
 * Создание счёта для оплаты через карту
 */
class CreateInvoice2Extended extends CreateInvoiceExtended
{
    /**
     * Default service type.
     */
    const DEFAULT_SERVICE_TYPE = 4;

    /**
     * Параметр оплаты только с кошелька или нет:
     *
     * @var int
     */
    protected int $cardForbidden;

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
