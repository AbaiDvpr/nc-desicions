<?php declare(strict_types=1);

namespace App\Models\Accounting\Filters;

use Illuminate\Http\Request;

/**
 * Класс для работы с параметрами запроса для получения списка ежедневного учёта
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
     * Uuid компании
     *
     * @var string|null
     */
    protected ?string $companyUuid;

    /**
     * Операция (income/outcome)
     *
     * @var string|null
     */
    protected ?string $operation;

    /**
     * Номер операции
     *
     * @var int|null
     */
    protected ?int $accountCash;

    /**
     * Вид деятельности
     *
     * @var int|null
     */
    protected ?int $kindActivity;

    /**
     * Дата поступления денег
     *
     * @var string|null
     */
    protected ?string $receiptDate;

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
     * @param  \Illuminate\Http\Request                   $request
     * @return \App\Models\Accounting\Filters\PagerParams
     */
    public static function createFromRequest(Request $request): PagerParams
    {
        $params = new self();

        $params->setOperation((string) $request->input('operation'));
        $params->setAccountCash((int) $request->input('accountCash'));
        $params->setKindActivity((int) $request->input('kindActivity'));
        $params->setReceiptDate((string) $request->input('receiptDate'));

        $params->page   = (int) $request->input('page', 1);
        $params->limit  = (int) $request->input('limit', self::DEFAULT_LIMIT);
        $params->offset = ($params->page * $params->limit) - $params->limit;

        return $params;
    }

    /**
     * @return int|null
     */
    public function getUserId(): ?int
    {
        return $this->userId;
    }

    /**
     * @param int|null                                    $userId
     * @return \App\Models\Accounting\Filters\PagerParams
     */
    public function setUserId(?int $userId): self
    {
        $this->userId = $userId;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getCompanyUuid(): ?string
    {
        return $this->companyUuid;
    }

    /**
     * @param  string|null                                $companyUuid
     * @return \App\Models\Accounting\Filters\PagerParams
     */
    public function setCompanyUuid(?string $companyUuid): self
    {
        $this->companyUuid = $companyUuid;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getOperation(): ?string
    {
        return $this->operation;
    }

    /**
     * @param  string|null                                $operation
     * @return \App\Models\Accounting\Filters\PagerParams
     */
    public function setOperation(?string $operation): self
    {
        $this->operation = $operation;

        return $this;
    }

    /**
     * @return int|null
     */
    public function getAccountCash(): ?int
    {
        return $this->accountCash;
    }

    /**
     * @param  int|null                                $accountCash
     * @return \App\Models\Accounting\Filters\PagerParams
     */
    public function setAccountCash(?int $accountCash): self
    {
        $this->accountCash = $accountCash;

        return $this;
    }

    /**
     * @return int|null
     */
    public function getKindActivity(): ?int
    {
        return $this->kindActivity;
    }

    /**
     * @param  int|null                                $kindActivity
     * @return \App\Models\Accounting\Filters\PagerParams
     */
    public function setKindActivity(?int $kindActivity): self
    {
        $this->kindActivity = $kindActivity;

        return $this;
    }

    /**
     * @return \DateTime|null
     */
    public function getReceiptDate(): ?string
    {
        return $this->receiptDate;
    }

    /**
     * @param                                             $receiptDate
     * @return \App\Models\Accounting\Filters\PagerParams
     */
    public function setReceiptDate($receiptDate): self
    {
        $this->receiptDate = $receiptDate;

        return $this;
    }

    /**
     * @return int
     */
    public function getLimit(): int
    {
        return $this->limit;
    }

    /**
     * @param  int                                        $limit
     * @return \App\Models\Accounting\Filters\PagerParams
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
     * @param  int                                        $offset
     * @return \App\Models\Accounting\Filters\PagerParams
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
            'userId'      => $this->userId,
            'companyUuid' => $this->companyUuid,
        ]), [
            'offset' => $this->offset,
            'limit'  => $this->limit,
        ]);
    }
}
