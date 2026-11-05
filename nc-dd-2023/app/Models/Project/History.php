<?php declare(strict_types=1);

namespace App\Models\Project;

use App\DI;
use App\Models\Tariff;
use App\Models\UserTariff;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Модель для работы с историями фин моделей
 *
 * @method static increment($column, $amount = 1, array $extra = [])
 */
class History extends Model
{
    use SoftDeletes;

    /**
     * @var string
     */
    protected $table = 'projects-history';

    /**
     * @inheritdoc
     *
     * @var array
     */
    protected $guarded = [];

    /**
     * Возвращает ID истории
     *
     * @return int
     */
    public function getId(): int
    {
        return (int) $this->getAttributeValue('id');
    }

    /**
     * Возвращает UUID фин модели
     *
     * @return string
     */
    public function getUuid(): string
    {
        return (string) $this->getAttributeValue('uuid');
    }

    /**
     * Устанавливает UUID фин модели
     *
     * @param  string                     $uuid
     * @return \App\Models\Project\History
     */
    public function setUuid(string $uuid): self
    {
        $this->setAttribute('uuid', $uuid);

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
     * @param  int                        $id
     * @return \App\Models\Project\History
     */
    public function setTariffId(int $id): self
    {
        $this->setAttribute('tariffId', $id);

        return $this;
    }

    /**
     * Возвращает ID пользовательского тарифа
     *
     * @return int
     */
    public function getUserTariffId(): int
    {
        return (int) $this->getAttributeValue('userTariffId');
    }

    /**
     * Устанавливает ID пользовательского тарифа
     *
     * @param  int                        $id
     * @return \App\Models\Project\History
     */
    public function setUserTariffId(int $id): self
    {
        $this->setAttribute('userTariffId', $id);

        return $this;
    }

    /**
     * Возвращает кол-во правок
     *
     * @return int
     */
    public function getCorrection(): int
    {
        return (int) $this->getAttributeValue('correction');
    }

    /**
     * Устанавливает кол-во правок
     *
     * @param  int                        $value
     * @return \App\Models\Project\History
     */
    public function setCorrection(int $value): self
    {
        $this->setAttribute('correction', $value);

        return $this;
    }

    /**
     * Возвращает оригинальный тариф
     *
     * @return \App\Models\Tariff
     */
    public function tariff(): Tariff
    {
        return DI::getRepository()->getModelsTariff()->getOne($this->getTariffId());
    }

    /**
     * Возвращает пользовательский тариф
     *
     * @return \App\Models\UserTariff
     */
    public function userTariff(): UserTariff
    {
        return DI::getRepository()->getUserTariff()->getOne($this->getUserTariffId());
    }
}
