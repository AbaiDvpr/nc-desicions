<?php declare(strict_types=1);

namespace App\Models\RequestRefill;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use App\Models\Presenters\RequestRefill\Data as RefillData;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * Сущность записи на пополнение кабинете через Kaspi
 * @method offset(int $param)
 */
class RequestRefill extends Model
{
    /**
     * @inheritdoc
     *
     * @var string
     */
    protected $table = 'request-refills';

    /**
     * @inheritdoc
     *
     * @var array
     */
    protected $guarded = [];

    /**
     * Презентер для данных компании
     *
     * @var \App\Models\Presenters\RequestRefill\Data
     */
    protected $refill;

    /**
     * Возвращает ID записи
     *
     * @return int
     */
    public function getId(): int
    {
        return (int) $this->getAttribute('id');
    }

    /**
     * Оплачена заявка или нет
     *
     * @return bool
     */
    public function isPaid(): bool
    {
        return (bool) $this->getAttribute('isPaid');
    }

    /**
     * Возвращает полное имя пользователя с формы
     *
     * @return string
     */
    public function getFullName(): string
    {
        return (string) $this->getAttribute('fullName');
    }

    /**
     * Возвращает номер телефона привязанный к Kaspi
     *
     * @return string
     */
    public function getPhone(): string
    {
        return (string) $this->getAttribute('phone');
    }

    /**
     * Возвращает комментарий оставленный при заполнении
     *
     * @return string
     */
    public function getComment(): string
    {
        return (string) $this->getAttribute('comment');
    }

    /**
     * Возвращает связь с пользователем подавшим заявку
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function getUser(): HasOne
    {
        return $this->hasOne(User::class, 'id', 'userId');
    }

    /**
     * Возвращает объект презентера для элемента сущности
     *
     * @return \App\Models\Presenters\RequestRefill\Data
     */
    public function getRefillData(): RefillData
    {
        if (!$this->refill) {
            $this->refill = new RefillData($this);
        }

        return $this->refill;
    }
}
