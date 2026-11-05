<?php declare(strict_types=1);
namespace App\Models;

use App\DI;
use App\Models\Handbook\Article;
use App\Models\Presenters\Accounting\Data;
use Illuminate\Support\Carbon;
use MongoDB\Laravel\Eloquent\Model;
use MongoDB\Laravel\Eloquent\SoftDeletes;

/**
 * Сущность для хранения операционной деятельности (Доходы/Расходы)
 * @method static find(string $uuid)
 */
class Accounting extends Model
{
    use SoftDeletes;

    /**
     * @inheritdoc
     *
     * @var string
     */
    protected $connection = 'mongodb';

    /**
     * @inheritdoc
     *
     * @var string
     */
    protected $collection = 'accounting';

    /**
     * @inheritdoc
     *
     * @var array
     */
    protected $guarded = [];

    /**
     * @inheritdoc
     *
     * @var string[]
     */
    protected $fillable = [
        'operation',
        'companyUuid',
        'accountCash',
        'bankId',
        'direction',
        'kindActivity',
        'type',
        'active',
        'activeType',
        'articleId',
        'price',
        'currency',
        'receiptDate',
        'dealDate',
    ];

    /**
     * Презентер для работы с представлением данных
     *
     * @var \App\Models\Presenters\Accounting\Data
     */
    protected $presenter;

    /**
     * Возвращает ID статьи
     *
     * @return string
     */
    public function getId(): string
    {
        return $this->getAttribute('_id');
    }

    /**
     * Возвращает тип операции (income/outcome)
     *
     * @return string
     */
    public function getOperation(): string
    {
        return (string) $this->getAttribute('operation');
    }

    /**
     * Возвращает UUID компании
     *
     * @return string
     */
    public function getCompanyUuid(): string
    {
        return (string) $this->getAttribute('companyUuid');
    }

    /**
     * Возвращает номер операции
     *
     * @see    \App\Forms\Company\Base::getAccountCashList()
     * @return int
     */
    public function getAccountCash(): int
    {
        return (int) $this->getAttribute('accountCash');
    }

    /**
     * Возвращает номер банка из справочника
     *
     * @return string|null
     */
    public function getBankId(): ?string
    {
        return (string) $this->getAttribute('bankId');
    }

    /**
     * Возвращает uuid направления
     *
     * @return string
     */
    public function getDirection(): string
    {
        return (string) $this->getAttribute('direction');
    }

    /**
     * Возвращает номер вида деятельности
     *
     * @see    \App\Forms\Company\Base::getKindActivity()
     * @return int
     */
    public function getKindActivity(): int
    {
        return (int) $this->getAttribute('kindActivity');
    }

    /**
     * Возвращает номер типа дохода
     *
     * @see    \App\Forms\Company\Income::getTypes()
     * @return int
     */
    public function getType(): int
    {
        return (int) $this->getAttribute('type');
    }

    /**
     * Возвращает вид актива/пассива
     *
     * @return int
     */
    public function getActive(): int
    {
        return (int) $this->getAttribute('active');
    }

    /**
     * Возвращает номер актива/пассива
     *
     * @see    \App\Forms\Company\Income::getActiveTypeList
     * @return string
     */
    public function getActiveType(): string
    {
        return (string) $this->getAttribute('activeType');
    }

    /**
     * Возвращает номер статьи из справочника
     *
     * @return string|null
     */
    public function getArticleId(): ?string
    {
        return (string) $this->getAttribute('articleId');
    }

    /**
     * Возвращает сумму операции
     *
     * @return float
     */
    public function getPrice(): float
    {
        return (float) $this->getAttribute('price');
    }

    /**
     * Возвращает ключ валюты
     *
     * @return string
     */
    public function getCurrency(): string
    {
        return (string) $this->getAttribute('currency');
    }

    /**
     * Возвращает дату поступления денег
     *
     * @return \Illuminate\Support\Carbon|null
     */
    public function getReceiptDate(): ?Carbon
    {
        $receiptDate = $this->getAttribute('receiptDate');

        if (!$receiptDate) {
            return null;
        }

        return Carbon::parse($receiptDate);
    }

    /**
     * Возвращает дату сделки
     *
     * @return \Illuminate\Support\Carbon|null
     */
    public function getDealDate(): ?Carbon
    {
        $dealDate = $this->getAttribute('dealDate');

        if (!$dealDate) {
            return null;
        }

        return Carbon::parse($dealDate);
    }

    /**
     * Возвращает дату создания компании
     *
     * @return \Illuminate\Support\Carbon
     */
    public function getCreatedAt(): Carbon
    {
        return $this->created_at;
    }

    /**
     * Возвращает связь со статьёй доходов/расходов
     *
     * @return \App\Models\Handbook\Article|null
     */
    public function getArticle(): ?Article
    {
        return DI::getRepository()->getHandbook()->getArticle()->getOne(
            $this->getArticleId()
        );
    }

    /**
     * Презентер для работы с публичными данными
     *
     * @return \App\Models\Presenters\Accounting\Data
     */
    public function getPresenter(): Data
    {
        if (!$this->presenter) {
            $this->presenter = new Data($this);
        }

        return $this->presenter;
    }
}
