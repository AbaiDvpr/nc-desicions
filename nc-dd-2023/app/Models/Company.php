<?php declare(strict_types=1);

namespace App\Models;

use App\Models\Presenters\Company\Data as CompanyData;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * Сущность для хранения Компании
 *
 * @method static where (string $string, mixed $id)
 */
class Company extends CoreModel
{
    use HasFactory;

    /**
     * @inheritdoc
     *
     * @var array
     */
    protected $guarded = [];

    /**
     * @inheritdoc
     *
     * @var string
     */
    protected $primaryKey = 'uuid';

    /**
     * Презентер для данных компании
     *
     * @var \App\Models\Presenters\Company\Data
     */
    protected $companyData;

    /**
     * Тип ОПУ
     */
    const TYPE_OPU = 'opu';

    /**
     * Тип ДДС
     */
    const TYPE_DDS = 'dds';

    /**
     * Тип Баланс
     */
    const TYPE_BALANCE = 'balance';

    /**
     * Возвращает UUID компании
     *
     * @return string
     */
    public function getUuid(): string
    {
        return (string) $this->getAttribute('uuid');
    }

    /**
     * Устанавливает UUID компании
     *
     * @param  string $uuid
     * @return \App\Models\Company
     */
    public function setUuid(string $uuid): self
    {
        $this->setAttribute('uuid', $uuid);

        return $this;
    }

    /**
     * Возвращает ID клиента
     *
     * @return int
     */
    public function getUserId(): int
    {
        return (int) $this->getAttribute('userId');
    }

    /**
     * Устанавливает ID клиента
     *
     * @param  int                $userId
     * @return \App\Models\Company
     */
    public function setUserId(int $userId): self
    {
        $this->setAttribute('userId', $userId);

        return $this;
    }

    /**
     * Возвращает ID тарифа
     *
     * @return int
     */
    public function getTariffId(): int
    {
        return (int) $this->getAttribute('tariffId');
    }

    /**
     * Устанавливает ID тарифа
     *
     * @param  int                $tariffId
     * @return \App\Models\Company
     */
    public function setTariffId(int $tariffId): self
    {
        $this->setAttribute('tariffId', $tariffId);

        return $this;
    }

    /**
     * Возвращает название компании
     *
     * @return string
     */
    public function getName(): string
    {
        return (string) $this->getAttribute('name');
    }

    /**
     * Устанавливает название компании
     *
     * @param  string             $name
     * @return \App\Models\Company
     */
    public function setName(string $name): self
    {
        $this->setAttribute('name', $name);

        return $this;
    }

    /**
     * Возвращает описание компании
     *
     * @return string|null
     */
    public function getDescription(): ?string
    {
        return $this->getAttribute('description');
    }

    /**
     * Устанавливает описание компании
     *
     * @param  null|string        $description
     * @return \App\Models\Company
     */
    public function setDescription(?string $description): self
    {
        $this->setAttribute('description', $description);

        return $this;
    }

    /**
     * Возвращает валюту
     *
     * @return string
     */
    public function getCurrency(): string
    {
        return (string) $this->getAttribute('currency');
    }

    /**
     * Устанавливает валюту
     *
     * @param  string             $currency
     * @return \App\Models\Company
     */
    public function setCurrency(string $currency): self
    {
        $this->setAttribute('currency', $currency);

        return $this;
    }

    /**
     * Возвращает организационно-правовую форму
     *
     * @return int
     */
    public function getForm(): int
    {
        return (int) $this->getAttribute('form');
    }

    /**
     * Устанавливает организационно-правовую форму
     *
     * @param  int                $form
     * @return \App\Models\Company
     */
    public function setForm(int $form): self
    {
        $this->setAttribute('form', $form);

        return $this;
    }

    /**
     * Возвращает форму налогообложения
     *
     * @return int
     */
    public function getTaxRegime(): int
    {
        return (int) $this->getAttribute('tax_regime');
    }

    /**
     * Устанавливает форму налогообложения
     *
     * @param  int                $taxRegime
     * @return \App\Models\Company
     */
    public function setTaxRegime(int $taxRegime): self
    {
        $this->setAttribute('tax_regime', $taxRegime);

        return $this;
    }

    /**
     * Возвращает НДС
     *
     * @return bool
     */
    public function getNds(): bool
    {
        return (bool) $this->getAttribute('nds');
    }

    /**
     * Устанавливает НДС
     *
     * @param  bool                $nds
     * @return \App\Models\Company
     */
    public function setNds(bool $nds): self
    {
        $this->setAttribute('nds', $nds);

        return $this;
    }

    /**
     * Возвращает дату создания компании
     *
     * @return \Illuminate\Support\Carbon
     */
    public function getCreatedAt(): Carbon
    {
        return $this->getAttribute('created_at');
    }

    /**
     * Возвращает объект для работы с данными пользователя
     *
     * @return \App\Models\Presenters\Company\Data
     */
    public function getCompanyData(): CompanyData
    {
        if (!$this->companyData) {
            $this->companyData = new CompanyData($this);
        }

        return $this->companyData;
    }

    /**
     * Возвращает связь компании с тарифом
     *
     * @return HasOne
     */
    public function tariff(): HasOne
    {
        return $this->hasOne(Tariff::class, 'id', 'tariffId');
    }

    /**
     * Возвращает связь компании с пользователем
     *
     * @return HasOne
     */
    public function user(): HasOne
    {
        return $this->hasOne(User::class, 'id', 'userId');
    }

    /**
     * Возвращает данные для записи в логи
     *
     * @return array
     */
    public function getLogData(): array
    {
        return [
            'uuid'   => $this->getUuid(),
            'userId' => $this->getUserId(),
        ];
    }
}
