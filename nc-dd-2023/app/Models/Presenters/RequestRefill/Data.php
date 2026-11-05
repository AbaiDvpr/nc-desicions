<?php declare(strict_types = 1);

namespace App\Models\Presenters\RequestRefill;

use App\Models\RequestRefill\RequestRefill;
use Illuminate\Support\Stringable;

/**
 * Презентер для сущности запроса на пополнение
 */
class Data
{
    /**
     * @var \App\Models\RequestRefill\RequestRefill
     */
    private RequestRefill $refill;

    /**
     * Конструктор
     *
     * @param \App\Models\RequestRefill\RequestRefill $refill
     */
    public function __construct(RequestRefill $refill)
    {
        $this->refill = $refill;
    }

    /**
     * Возвращает полное имя пользователя с формы
     *
     * @return string
     */
    public function getFullName(): string
    {
        return $this->refill->getFullName();
    }

    /**
     * Возвращает отформатированный номер телефона привязанный к Kaspi
     *
     * @return string
     */
    public function getPhone(): string
    {
        return formatPhone($this->refill->getPhone());
    }

    /**
     * Возвращает комментарий оставленный при заполнении
     *
     * @return string
     */
    public function getComment(): string
    {
        return $this->refill->getComment();
    }

    /**
     * Возвращает имя пользователя подавшего заявку
     *
     * @return string
     */
    public function getUserName(): string
    {
        return $this->refill->getUser->getName();
    }

    /**
     * Возвращает номер кабинета пользователя
     *
     * @return \Illuminate\Support\Stringable
     */
    public function getCabinetId(): Stringable
    {
        return $this->refill->getUser->getCabinetId();
    }
}
