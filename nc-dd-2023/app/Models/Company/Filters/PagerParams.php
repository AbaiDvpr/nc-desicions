<?php declare(strict_types=1);

namespace App\Models\Company\Filters;

use Illuminate\Http\Request;

/**
 * Класс для работы с параметрами запроса для получения списка компании
 */
class PagerParams
{
    /**
     * Лимит получаемых записей по умолчанию
     */
    const DEFAULT_LIMIT = 10;

    /**
     * ID пользователя
     *
     * @var int
     */
    protected int $userId;

    /**
     * Тариф по которому расчитывался проект
     *
     * @var int
     */
    protected int $tariffId;

    /**
     * Название компании
     *
     * @var string
     */
    protected string $name;

    /**
     * Валюта расчета
     *
     * @var string
     */
    protected string $currency;

    /**
     * Номер страницы
     *
     * @var int
     */
    protected int $page = 1;

    /**
     * Лимит получаемых записей
     *
     * @var int
     */
    protected int $limit = self::DEFAULT_LIMIT;

    /**
     * Смещение для получения записей
     *
     * @var int
     */
    protected int $offset = 0;

    /**
     * Получение компании с выбранной даты
     *
     * @var string|null
     */
    protected ?string $fromDate = null;

    /**
     * Получение компании до выбранной дату
     *
     * @var string|null
     */
    protected ?string $toDate = null;

    /**
     * Создает экземпляр класса заполняя свойства данными из запроса
     *
     * @param  \Illuminate\Http\Request                $request
     * @return \App\Models\Company\Filters\PagerParams
     */
    public static function createFromRequest(Request $request): PagerParams
    {
        $params = new self();

        $params->userId   = (int) $request->input('userId');
        $params->tariffId = (int) $request->input('tariffId');
        $params->name     = (string) $request->input('name');
        $params->page     = (int) $request->input('page', 1);
        $params->limit    = (int) $request->input('limit', self::DEFAULT_LIMIT);
        $params->offset   = ($params->page * $params->limit) - $params->limit;
        $params->currency = (string) $request->input('currency');

        $params->setFromDate((string) $request->input('fromDate'));
        $params->setToDate((string) $request->input('toDate'));

        return $params;
    }

    /**
     * Получает по ID пользователя
     *
     * @return int
     */
    public function getUserId(): int
    {
        return $this->userId;
    }

    /**
     * Устанавливает ID пользователя для запроса
     *
     * @param  int  $userId
     * @return void
     */
    public function setUserId(int $userId): void
    {
        $this->userId = $userId;
    }

    /**
     * Получение компании по тарифу
     *
     * @return int
     */
    public function getTariffId(): int
    {
        return $this->tariffId;
    }

    /**
     * Получение компании по валюте
     *
     * @return string
     */
    public function getCurrency(): string
    {
        return $this->currency;
    }

    /**
     * Получение компании по наименованию
     *
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Получение по установленному лимиту
     *
     * @return int
     */
    public function getLimit(): int
    {
        return $this->limit;
    }

    /**
     * Устанавливает лимит
     *
     * @param int   $limit
     * @return void
     */
    public function setLimit(int $limit): void
    {
        $this->limit = $limit;
    }

    /**
     * Получение компании c даты создания
     *
     * @return string|null
     */
    public function getFromDate(): ?string
    {
        return $this->fromDate;
    }

    /**
     * Выбор начальной даты создания компании
     *
     * @param  string                                  $fromDate
     * @return \App\Models\Company\Filters\PagerParams
     */
    public function setFromDate(string $fromDate): self
    {
        $this->fromDate = $fromDate;

        return $this;
    }

    /**
     * Получение компании по дату создания
     *
     * @return string|null
     */
    public function getToDate(): ?string
    {
        return $this->toDate;
    }

    /**
     * Выбор конечной даты создания компании
     *
     * @param  string                                  $toDate
     * @return \App\Models\Company\Filters\PagerParams
     */
    public function setToDate(string $toDate): self
    {
        $this->toDate = $toDate;

        return $this;
    }

    /**
     * Возвращает список заполненных свойств в виде массива
     *
     * @return array
     */
    public function toArray(): array
    {
        return array_merge(array_filter([
            'userId'   => $this->userId,
            'tariffId' => $this->tariffId,
            'name'     => $this->name,
        ]), [
            'offset'   => $this->offset,
            'limit'    => $this->limit,
        ]);
    }
}
