<?php declare(strict_types = 1);

namespace App\Models\Counterparty\Filter;

use Illuminate\Http\Request;

/**
 * Класс для работы с параметрами запроса для получения списка контрагентов
 */
class PagerParams
{
    /**
     * Лимит получаемых записей по умолчанию
     */
    const DEFAULT_LIMIT = 20;

    /**
     * ID пользователя
     *
     * @var int|null
     */
    protected ?int $userId;

    /**
     * ID банка
     *
     * @var string|null
     */
    protected ?string $bankId;

    /**
     * Наименование компании/физ лица
     *
     * @var string|null
     */
    protected ?string $name;

    /**
     * E-mail
     *
     * @var string|null
     */
    protected ?string $email;

    /**
     * БИН/ИИН компании
     *
     * @var string|null
     */
    protected ?string $bin;

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
     * Создает экземпляр класса заполняя свойства данными из запроса
     *
     * @param  \Illuminate\Http\Request $request
     * @return \App\Models\Counterparty\Filter\PagerParams
     */
    public static function createFromRequest(Request $request): PagerParams
    {
        $params = new self();

        $params->bankId = (string) $request->input('bankId');
        $params->name   = (string) $request->input('name');
        $params->email  = (string) $request->input('email');
        $params->bin    = (string) $request->input('bin');
        $params->page   = (int) $request->input('page', 1);
        $params->limit  = (int) $request->input('limit', self::DEFAULT_LIMIT);
        $params->offset = ($params->page * $params->limit) - $params->limit;

        return $params;
    }

    /**
     * @return int
     */
    public function getUserId(): int
    {
        return $this->userId;
    }

    /**
     * @param  int|null $userId
     * @return \App\Models\Counterparty\Filter\PagerParams
     */
    public function setUserId(?int $userId): self
    {
        $this->userId = $userId;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getName(): ?string
    {
        return $this->name;
    }

    /**
     * @return string|null
     */
    public function getEmail(): ?string
    {
        return $this->email;
    }

    /**
     * @return string|null
     */
    public function getBin(): ?string
    {
        return $this->bin;
    }

    /**
     * @return string|null
     */
    public function getBankId(): ?string
    {
        return $this->bankId;
    }

    /**
     * @param  string|null $bankId
     * @return \App\Models\Counterparty\Filter\PagerParams
     */
    public function setBankId(?string $bankId): self
    {
        $this->bankId = $bankId;

        return $this;
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
     * @param  int $limit
     * @return \App\Models\Counterparty\Filter\PagerParams
     */
    public function setLimit(int $limit): self
    {
        $this->limit = $limit;

        return $this;
    }

    /**
     * @return int
     */
    public function getOffset(): int
    {
        return $this->offset;
    }

    /**
     * @param  int $offset
     * @return \App\Models\Counterparty\Filter\PagerParams
     */
    public function setOffset(int $offset): self
    {
        $this->offset = $offset;

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
            'userId' => $this->getUserId(),
            'name'   => $this->getName(),
            'email'  => $this->getEmail(),
            'bin'    => $this->getBin(),
        ]), [
            'offset' => $this->getOffset(),
            'limit'  => $this->getLimit(),
        ]);
    }
}