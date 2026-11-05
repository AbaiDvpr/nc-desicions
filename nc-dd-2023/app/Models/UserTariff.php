<?php declare(strict_types=1);

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Casts\AsCollection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Spatie\Translatable\HasTranslations;
use App\Models\Presenters\UserTariff\Data as UserTariffData;

/**
 * Модель для работы с тарифами пользователей
 */
class UserTariff extends Model
{
    use HasTranslations;

    /**
     * @var string
     */
    protected $table = 'users-tariffs';

    /**
     * @inheritdoc
     *
     * @var array
     */
    protected $guarded = [];

    /**
     * Field for translatable
     *
     * @var array
     */
    public array $translatable = ['name', 'description'];

    /**
     * @inheritdoc
     *
     * @var string[]
     */
    protected $casts = [
        'company' => AsCollection::class,
        'project' => AsCollection::class,
        'expire'  => 'datetime',
    ];

    /**
     * Презентер для данных компании
     */
    protected $presenter;

    /**
     * Возвращает ID тарифа
     *
     * @return int
     */
    public function getId(): int
    {
        return (int) $this->getAttributeValue('id');
    }

    /**
     * Возвращает ID пользователя
     *
     * @return int
     */
    public function getUserId(): int
    {
        return (int) $this->getAttributeValue('userId');
    }

    /**
     * Устанавливает ID пользователя
     *
     * @param  int                   $id
     * @return \App\Models\UserTariff
     */
    public function setUserId(int $id): self
    {
        $this->setAttribute('userId', $id);

        return $this;
    }

    /**
     * Возвращает ID оригинального тарифа
     *
     * @return int
     */
    public function getTariffId(): int
    {
        return (int) $this->getAttributeValue('tariffId');
    }

    /**
     * Устанавливает ID оригинального тарифа
     *
     * @param  int                   $id
     * @return \App\Models\UserTariff
     */
    public function setTariffId(int $id): self
    {
        $this->setAttribute('tariffId', $id);

        return $this;
    }

    /**
     * Возвращает тип тарифа
     *
     * @return int
     */
    public function getType(): int
    {
        return (int) $this->getAttributeValue('type');
    }

    /**
     * Устанавливает тип тарифа
     *
     * @param  int                   $type
     * @return \App\Models\UserTariff
     */
    public function setType(int $type): self
    {
        $this->setAttribute('type', $type);

        return $this;
    }

    /**
     * Возвращает название тарифа согласно текущей локализации
     *
     * @return string
     */
    public function getName(): string
    {
        return (string) $this->getAttributeValue('name');
    }

    /**
     * Устанавливает название тарифа с локализациями
     *
     * @param  array                 $names
     * @return \App\Models\UserTariff
     */
    public function setName(array $names): self
    {
        $this->setTranslations('name', $names);

        return $this;
    }

    /**
     * Возвращает цену тарифа
     *
     * @return float
     */
    public function getPrice(): float
    {
        return (float) $this->getAttributeValue('price');
    }

    /**
     * Устанавливает цену тарифа
     *
     * @param  float                 $price
     * @return \App\Models\UserTariff
     */
    public function setPrice(float $price): self
    {
        $this->setAttribute('price', $price);

        return $this;
    }

    /**
     * Возвращает описание тарифа согласно текущей локализации
     *
     * @return string
     */
    public function getDescription(): string
    {
        return (string) $this->getAttributeValue('description');
    }

    /**
     * Устанавливает описание тарифа с локализациями
     *
     * @param  array                 $descriptions
     * @return \App\Models\UserTariff
     */
    public function setDescription(array $descriptions): self
    {
        $this->setTranslations('description', $descriptions);

        return $this;
    }

    /**
     * Возвращает параметры по ограничениям для "Управленческого учёта"
     *
     * @return \Illuminate\Support\Collection
     */
    public function getCompany(): Collection
    {
        return $this->getAttributeValue('company');
    }

    /**
     * Устанавливает параметры по ограничениям для "Управленческого учёта"
     *
     * @param  array                 $company
     * @return \App\Models\UserTariff
     */
    public function setCompany(array $company): self
    {
        $this->setAttribute('company', $company);

        return $this;
    }

    /**
     * Возвращает параметры по ограничениям для "Финансовых моделей"
     *
     * @return \Illuminate\Support\Collection
     */
    public function getProject(): Collection
    {
        return $this->getAttributeValue('project');
    }

    /**
     * Устанавливает параметры по ограничениям для "Финансовых моделей"
     *
     * @param  array                 $project
     * @return \App\Models\UserTariff
     */
    public function setProject(array $project): self
    {
        $this->setAttribute('project', $project);

        return $this;
    }

    /**
     * Возвращает максимальное количество финансовых моделей по тарифу
     *
     * @return int
     */
    public function getMaxProjects(): int
    {
        return (int) $this->getAttributeValue('project')->get('count');
    }

    /**
     * Возвращает максимальное количество корректировок для финансовых моделей по тарифу
     *
     * @return int
     */
    public function getProjectCorrection(): int
    {
        return (int) $this->getAttributeValue('project')->get('correction');
    }

    /**
     * Возвращает дату истечения срока тарифного плана
     *
     * @return \Carbon\Carbon|null
     */
    public function getExpire(): ?Carbon
    {
        return $this->getAttributeValue('expire');
    }

    /**
     * Устанавливает дату истечения срока тарифного плана
     *
     * @param  \Carbon\Carbon|null   $expire
     * @return \App\Models\UserTariff
     */
    public function setExpire(?Carbon $expire = null): self
    {
        if (!$expire) {
            $expire = $this->calcExpirationDate();
        }

        $this->setAttribute('expire', $expire);

        return $this;
    }

    /**
     * Возвращает объект для работы с публичными данными
     *
     * @return \App\Models\Presenters\UserTariff\Data
     */
    public function getPresenter(): UserTariffData
    {
        if (!$this->presenter) {
            $this->presenter = new UserTariffData($this);
        }

        return $this->presenter;
    }

    /**
     * Является ли тариф ежемесячным
     *
     * @return bool
     */
    public function isMonthly(): bool
    {
        return $this->getType() === Tariff::TYPE_MONTHLY;
    }

    /**
     * Является ли тариф годовым
     *
     * @return bool
     */
    public function isAnnual(): bool
    {
        return $this->getType() === Tariff::TYPE_ANNUAL;
    }

    /**
     * Проверяет дату истечения тарифного плана
     *
     * @return bool
     */
    public function isExpired(): bool
    {
        if (is_null($this->getExpire())) {
            return false;
        }

        if (Carbon::now()->gt($this->getExpire())) {
            return true;
        }

        return false;
    }

    /**
     * Определяет дату истечения тарифа
     *
     * @return \Carbon\Carbon
     */
    private function calcExpirationDate(): Carbon
    {
        $date = Carbon::now();

        return match ($this->getType()) {
            Tariff::TYPE_ANNUAL => $date->addYear(),
            default => $date->addMonth(),
        };
    }

    /**
     * Возвращает дату создания тарифа
     *
     * @return \Illuminate\Support\Carbon
     */
    public function getCreatedAt(): \Illuminate\Support\Carbon
    {
        return $this->created_at;
    }
}
