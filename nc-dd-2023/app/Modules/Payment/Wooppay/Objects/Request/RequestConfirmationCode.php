<?php declare(strict_types=1);

namespace App\Modules\Payment\Wooppay\Objects\Request;

/**
 * Запросить код подтверждения
 */
class RequestConfirmationCode
{
    /**
     * Номер телефона для отправки кода подтверждения.
     *
     * @var string
     */
    protected string $phone;

    public function getPhone(): string
    {
        return $this->phone;
    }

    public function setPhone(string $phone): self
    {
        $this->phone = $phone;

        return $this;
    }
}
