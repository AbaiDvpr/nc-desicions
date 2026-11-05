<?php declare(strict_types=1);

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * Модель для хранения счетов
 */
class Invoice extends Model
{
    /**
     * @inheritdoc
     *
     * @var array
     */
    protected $guarded = [];

    /**
     * Возвращает идентификатор счёта
     *
     * @return int
     */
    public function getId(): int
    {
        return (int) $this->getAttribute('id');
    }

    /**
     * Возвращает ID пользователя для был выставлен счёт
     *
     * @return int
     */
    public function getUserId(): int
    {
        return $this->getAttribute('user_id');
    }

    public function setUserId(int $userId): void
    {
        $this->setAttribute('user_id', $userId);
    }

    public function getServiceType(): int
    {
        return $this->getAttribute('serviceType');
    }

    public function setServiceType(int $serviceType): void
    {
        $this->setAttribute('serviceType', $serviceType);
    }

    public function getService(): string
    {
        return $this->getAttribute('service');
    }

    public function setService(string $service): void
    {
        $this->setAttribute('service', $service);
    }

    public function getAmount(): float
    {
        return $this->getAttribute('amount');
    }

    public function setAmount(float $amount): void
    {
        $this->setAttribute('amount', $amount);
    }

    public function getDescription(): ?string
    {
        return $this->getAttribute('description');
    }

    public function setDescription(?string $description): void
    {
        $this->setAttribute('description', $description);
    }

    public function getEmail()
    {
        return $this->getAttribute('email');
    }

    public function setEmail(?string $email): void
    {
        $this->setAttribute('email', $email);
    }

    public function getPhone()
    {
        return $this->getAttribute('phone');
    }

    public function setPhone(?string $phone): void
    {
        $this->setAttribute('phone', $phone);
    }

    public function getTariffId(): int
    {
        return (int) $this->getAttribute('tariffId');
    }

    public function getPaidAt(): ?\DateTime
    {
        $paidAt = $this->getAttribute('paid_at');

        if (is_string($paidAt)) {
            return Carbon::parse($paidAt);
        }

        return $paidAt;
    }

    public function setPaidAt(?\DateTime $paidAt): void
    {
        $this->setAttribute('paid_at', $paidAt);
    }

    public function getDeathDate(): ?\DateTime
    {
        $deathDate = $this->getAttribute('deathDate');

        if (is_string($deathDate)) {
            return Carbon::parse($deathDate);
        }

        return $deathDate;
    }

    public function setDeathDate(?\DateTime $deathDate): void
    {
        $this->setAttribute('deathDate', $deathDate);
    }

    public function getOperationId(): string
    {
        return $this->getAttribute('operationId');
    }

    public function setOperationId(string $operationId): void
    {
        $this->setAttribute('operationId', $operationId);
    }

    /**
     * Возвращает тариф по которому была оплата
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function tariff(): HasOne
    {
        return $this->hasOne(Tariff::class, 'id', 'tariffId');
    }

    /**
     * Проверяет, оплачен ли счёт
     *
     * @return bool
     */
    public function isPaid(): bool
    {
        return !is_null($this->getPaidAt());
    }
}
