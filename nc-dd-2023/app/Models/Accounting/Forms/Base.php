<?php declare(strict_types=1);
namespace App\Models\Accounting\Forms;

use Illuminate\Http\Request;

/**
 * Форма запроса на добавление доходной части
 */
class Base
{
    /**
     * Ключ операции доходов
     */
    const OPERATION_INCOME = 'income';

    /**
     * Ключ операции расходов
     */
    const OPERATION_OUTCOME = 'outcome';

    /**
     * Ключ операции активов/пассивов
     */
    const OPERATION_ACTIVE  = 'active';

    /**
     * ID компании к которой привязывается доход
     *
     * @var string|null
     */
    public ?string $uuid = null;

    /**
     * Тип операции доходов/расходов
     *
     * @var string
     */
    protected string $operation;

    /**
     * ID компании к которой привязывается доход
     *
     * @var string
     */
    protected string $companyUuid;

    /**
     * Номер операции
     *
     * @see \App\Forms\Company\Base::getAccountCashList()
     * @var int
     */
    protected int $accountCash;

    /**
     * Номер банка из справочника
     *
     * @var string|null
     */
    protected ?string $bankId;

    /**
     * UUID направления затрат/доходов
     *
     * @var string
     */
    protected string $direction;

    /**
     * Номер вида деятельности
     *
     * @see \App\Forms\Company\Base::getKindActivity()
     * @var int
     */
    protected int $kindActivity;

    /**
     * Номер типа дохода
     *
     * @see \App\Forms\Company\Income::getTypes()
     * @var int
     */
    protected int $type;

    /**
     * Номер вида актива/пассива
     *
     * @var int
     */
    protected int $active;

    /**
     * Номер типа актива/пассива
     *
     * @see \App\Forms\Company\Income::getActiveTypeList()
     * @var string
     */
    protected string $activeType;

    /**
     * Номер статьи из справочника
     *
     * @var string|null
     */
    protected ?string $articleId;

    /**
     * Сумма операции
     *
     * @var float
     */
    protected float $price;

    /**
     * Ключ валюты (kzt/usd/eur)
     *
     * @var string
     */
    protected string $currency;

    /**
     * Дата поступления денег
     *
     * @var string|null
     */
    protected ?string $receiptDate;

    /**
     * Дата сделки
     *
     * @var string|null
     */
    protected ?string $dealDate;

    /**
     * Создает объект из запроса
     *
     * @param  \Illuminate\Http\Request $request
     * @return self
     * @throws \Exception
     */
    public function createFromRequest(Request $request): self
    {
        if ($request->input('uuid')) {
            $this->setUuid((string) $request->input('uuid'));
        }

        $this->setOperation((string) $request->input('operation'));
        $this->setCompanyUuid((string) $request->input('companyUuid'));
        $this->setAccountCash((int) $request->input('accountCash'));
        $this->setBankId($request->input('bankId'));
        $this->setDirection((string) $request->input('direction'));
        $this->setKindActivity((int) $request->input('kindActivity'));
        $this->setType((int) $request->input('type'));
        $this->setActive((int) $request->input('active'));
        $this->setActiveType((string) $request->input('activeType'));
        $this->setArticleId($request->input('articleId'));
        $this->setPrice((float) $request->input('price'));
        $this->setCurrency((string) $request->input('currency'));
        $this->setReceiptDate((string) $request->input('receiptDate'));
        $this->setDealDate((string) $request->input('dealDate'));

        return $this;
    }

    /**
     * @see \App\Models\Accounting\Forms\Base::$uuid
     *
     * @return string|null
     */
    public function getUuid(): ?string
    {
        return $this->uuid;
    }

    /**
     * @see \App\Models\Accounting\Forms\Base::$uuid
     *
     * @param  string                            $uuid
     * @return \App\Models\Accounting\Forms\Base
     */
    public function setUuid(string $uuid): self
    {
        $this->uuid = $uuid;

        return $this;
    }

    /**
     * @see \App\Models\Accounting\Forms\Base::$operation
     *
     * @return string
     */
    public function getOperation(): string
    {
        return $this->operation;
    }

    /**
     * @see \App\Models\Accounting\Forms\Base::$operation
     *
     * @param  string                            $operation
     * @return \App\Models\Accounting\Forms\Base
     */
    public function setOperation(string $operation): self
    {
        if (!in_array($operation, [self::OPERATION_INCOME, self::OPERATION_OUTCOME, self::OPERATION_ACTIVE], true)) {
            throw new \InvalidArgumentException('Неверная операция');
        }

        $this->operation = $operation;

        return $this;
    }

    /**
     * @see \App\Models\Accounting\Forms\Base::$companyUuid
     *
     * @return string
     */
    public function getCompanyUuid(): string
    {
        return $this->companyUuid;
    }

    /**
     * @see \App\Models\Accounting\Forms\Base::$companyUuid
     *
     * @param  string                            $companyUuid
     * @return \App\Models\Accounting\Forms\Base
     */
    public function setCompanyUuid(string $companyUuid): self
    {
        $this->companyUuid = $companyUuid;

        return $this;
    }

    /**
     * @see \App\Models\Accounting\Forms\Base::$accountCash
     *
     * @return int
     */
    public function getAccountCash(): int
    {
        return $this->accountCash;
    }

    /**
     * @see \App\Models\Accounting\Forms\Base::$accountCash
     *
     * @param  int                               $accountCash
     * @return \App\Models\Accounting\Forms\Base
     */
    public function setAccountCash(int $accountCash): self
    {
        $this->accountCash = $accountCash;

        return $this;
    }

    /**
     * @see \App\Models\Accounting\Forms\Base::$bankId
     *
     * @return string|null
     */
    public function getBankId(): ?string
    {
        return $this->bankId;
    }

    /**
     * @see \App\Models\Accounting\Forms\Base::$bankId
     *
     * @param  string|null                      $bankId
     * @return \App\Models\Accounting\Forms\Base
     */
    public function setBankId(?string $bankId): self
    {
        $this->bankId = $bankId;

        return $this;
    }

    /**
     * @see \App\Models\Accounting\Forms\Base::$direction
     *
     * @return string
     */
    public function getDirection(): string
    {
        return $this->direction;
    }

    /**
     * @see \App\Models\Accounting\Forms\Base::$direction
     *
     * @param  string                            $direction
     * @return \App\Models\Accounting\Forms\Base
     */
    public function setDirection(string $direction): self
    {
        $this->direction = $direction;

        return $this;
    }

    /**
     * @see \App\Models\Accounting\Forms\Base::$kindActivity
     *
     * @return int
     */
    public function getKindActivity(): int
    {
        return $this->kindActivity;
    }

    /**
     * @see \App\Models\Accounting\Forms\Base::$kindActivity
     *
     * @param  int                               $kindActivity
     * @return \App\Models\Accounting\Forms\Base
     */
    public function setKindActivity(int $kindActivity): self
    {
        $this->kindActivity = $kindActivity;

        return $this;
    }

    /**
     * @see \App\Models\Accounting\Forms\Base::$type
     *
     * @return int
     */
    public function getType(): int
    {
        return $this->type;
    }

    /**
     * @see \App\Models\Accounting\Forms\Base::$type
     *
     * @param  int                               $type
     * @return \App\Models\Accounting\Forms\Base
     */
    public function setType(int $type): self
    {
        $this->type = $type;

        return $this;
    }

    /**
     * @see \App\Models\Accounting\Forms\Base::$active
     *
     * @return int
     */
    public function getActive(): int
    {
        return $this->active;
    }

    /**
     * @see \App\Models\Accounting\Forms\Base::$active
     *
     * @param  int                               $active
     * @return \App\Models\Accounting\Forms\Base
     */
    public function setActive(int $active): self
    {
        $this->active = $active;

        return $this;
    }

    /**
     * @see \App\Models\Accounting\Forms\Base::$activeType
     *
     * @return string
     */
    public function getActiveType(): string
    {
        return $this->activeType;
    }

    /**
     * @see \App\Models\Accounting\Forms\Base::$activeType
     *
     * @param  string                            $activeType
     * @return \App\Models\Accounting\Forms\Base
     */
    public function setActiveType(string $activeType): self
    {
        $this->activeType = $activeType;

        return $this;
    }

    /**
     * @see \App\Models\Accounting\Forms\Base::$articleId
     *
     * @return string|null
     */
    public function getArticleId(): ?string
    {
        return $this->articleId;
    }

    /**
     * @see \App\Models\Accounting\Forms\Base::$articleId
     *
     * @param  string|null                       $articleId
     * @return \App\Models\Accounting\Forms\Base
     */
    public function setArticleId(?string $articleId): self
    {
        $this->articleId = $articleId;

        return $this;
    }

    /**
     * @see \App\Models\Accounting\Forms\Base::$price
     *
     * @return float
     */
    public function getPrice(): float
    {
        return $this->price;
    }

    /**
     * @see \App\Models\Accounting\Forms\Base::$price
     *
     * @param  float                             $price
     * @return \App\Models\Accounting\Forms\Base
     */
    public function setPrice(float $price): self
    {
        $this->price = $price;

        return $this;
    }

    /**
     * @see \App\Models\Accounting\Forms\Base::$currency
     *
     * @return string
     */
    public function getCurrency(): string
    {
        return $this->currency;
    }

    /**
     * @see \App\Models\Accounting\Forms\Base::$currency
     *
     * @param  string                            $currency
     * @return \App\Models\Accounting\Forms\Base
     */
    public function setCurrency(string $currency): self
    {
        $this->currency = $currency;

        return $this;
    }

    /**
     * @see \App\Models\Accounting\Forms\Base::$receiptDate
     *
     * @return string|null
     */
    public function getReceiptDate(): ?string
    {
        return $this->receiptDate;
    }

    /**
     * @see \App\Models\Accounting\Forms\Base::$receiptDate
     *
     * @param                                    $receiptDate
     * @return \App\Models\Accounting\Forms\Base
     * @throws \Exception
     */
    public function setReceiptDate($receiptDate): self
    {
        if (is_string($receiptDate)) {
            $receiptDate = new \DateTime($receiptDate);
        }

        if (!is_null($receiptDate) && $receiptDate instanceof \DateTime === false) {
            throw new \InvalidArgumentException(
                'ReceiptDate must be an instance of DateTime or valid date string, got ' . gettype($receiptDate)
            );
        }

        $this->receiptDate = $receiptDate->format(DATE_ATOM);

        return $this;
    }

    /**
     * @see \App\Models\Accounting\Forms\Base::$dealDate
     *
     * @return string|null
     */
    public function getDealDate(): ?string
    {
        return $this->dealDate;
    }

    /**
     * @see \App\Models\Accounting\Forms\Base::$dealDate
     *
     * @param                                    $dealDate
     * @return \App\Models\Accounting\Forms\Base
     * @throws \Exception
     */
    public function setDealDate($dealDate): self
    {
        if (is_string($dealDate)) {
            $dealDate = new \DateTime($dealDate);
        }

        if (!is_null($dealDate) && $dealDate instanceof \DateTime === false) {
            throw new \InvalidArgumentException(
                'DealDate must be an instance of DateTime or valid date string, got ' . gettype($dealDate)
            );
        }

        $this->dealDate = $dealDate->format(DATE_ATOM);

        return $this;
    }

    /**
     * Возвращает данные формы в виде массива
     *
     * @return array
     */
    public function toArray(): array
    {
        return [
            'operation'    => $this->getOperation(),
            'companyUuid'  => $this->getCompanyUuid(),
            'accountCash'  => $this->getAccountCash(),
            'bankId'       => $this->getBankId(),
            'direction'    => $this->getDirection(),
            'kindActivity' => $this->getKindActivity(),
            'type'         => $this->getType(),
            'active'       => $this->getActive(),
            'activeType'   => $this->getActiveType(),
            'articleId'    => $this->getArticleId(),
            'price'        => $this->getPrice(),
            'currency'     => $this->getCurrency(),
            'receiptDate'  => $this->getReceiptDate(),
            'dealDate'     => $this->getDealDate(),
        ];
    }
}
