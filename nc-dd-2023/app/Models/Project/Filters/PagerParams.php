<?php declare(strict_types=1);

namespace App\Models\Project\Filters;

use Illuminate\Http\Request;

/**
 * Класс для работы с параметрами запроса для получения списка проекта
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
     * Тариф по которому рассчитывался проект
     *
     * @var int
     */
    protected int $tariffId;

    /**
     * Название проекта
     *
     * @var string
     */
    protected string $name;

    /**
     * Статус проекта
     *
     * @var string
     */
    protected string $status;

    /**
     * Налоговый режим проекта
     *
     * @var int
     */
    protected int $tax_regime;

    /**
     * Организационно-правовая форма
     *
     * @var int
     */
    protected int $form;

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
     * Получение проекта с выбранной даты
     *
     * @var string|null
     */
    protected ?string $fromDate = null;

    /**
     * Получение проекта до выбранной дату
     *
     * @var string|null
     */
    protected ?string $toDate = null;

    /**
     * Создает экземпляр класса заполняя свойства данными из запроса
     *
     * @param  \Illuminate\Http\Request                $request
     * @return \App\Models\Project\Filters\PagerParams
     */
    public static function createFromRequest(Request $request): PagerParams
    {
        $params = new self();

        $params->userId     = (int) $request->input('userId');
        $params->tariffId   = (int) $request->input('tariffId');
        $params->name       = (string) $request->input('name');
        $params->status     = (string) $request->input('status');
        $params->form       = (int) $request->input('form');
        $params->currency   = (string) $request->input('currency');
        $params->page       = (int) $request->input('page', 1);
        $params->limit      = (int) $request->input('limit', self::DEFAULT_LIMIT);
        $params->offset     = ($params->page * $params->limit) - $params->limit;
        $params->tax_regime = (int) $request->input('tax_regime');

        $params->setFromDate((string) $request->input('fromDate'));
        $params->setToDate((string) $request->input('toDate'));

        return $params;
    }

    /**
     * Получение по ID пользователя
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
     * Получение проекта по тарифу
     *
     * @return int
     */
    public function getTariffId(): int
    {
        return $this->tariffId;
    }

    /**
     * Получение проекта по наименованию
     *
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Получение проекта по статусу
     *
     * @return string
     */
    public function getStatus(): string
    {
        return $this->status;
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
     * @param int $limit
     * @return void
     */
    public function setLimit(int $limit): void
    {
        $this->limit = $limit;
    }

    /**
     * Получение проекта по налоговому режиму
     *
     * @return int
     */
    public function getTaxRegime(): int
    {
        return $this->tax_regime;
    }

    /**
     * Получение проекта c даты создания
     *
     * @return string|null
     */
    public function getFromDate(): ?string
    {
        return $this->fromDate;
    }

    /**
     * Выбор начальной даты создания проекта
     *
     * @param  string                                  $fromDate
     * @return \App\Models\Project\Filters\PagerParams
     */
    public function setFromDate(string $fromDate): self
    {
        $this->fromDate = $fromDate;

        return $this;
    }

    /**
     * Получение проекта по дату создания
     *
     * @return string|null
     */
    public function getToDate(): ?string
    {
        return $this->toDate;
    }

    /**
     * Выбор конечной даты создания проекта
     *
     * @param  string                                  $toDate
     * @return \App\Models\Project\Filters\PagerParams
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
            'status'   => $this->status,
            'form'     => $this->form,
            'currency' => $this->currency,
        ]), [
            'offset'   => $this->offset,
            'limit'    => $this->limit,
        ]);
    }
}
