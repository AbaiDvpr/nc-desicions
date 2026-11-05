<?php declare(strict_types=1);

namespace App\Models;

use App\Models\Presenters\BalanceHistory\Data as BalanceHistoryData;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property mixed operation
 */
class BalanceHistory extends Model
{
    use HasFactory;

    /**
     * @var string
     */
    protected $table = 'balance-histories';

    /**
     * @inheritdoc
     *
     * @var array
     */
    protected $guarded = [];

    /**
     * Операция пополнения
     */
    const OPERATION_REFILL = 'refill';

    /**
     * Операция списания
     */
    const OPERATION_CUT = 'cut';

    /**
     * Операция возврата средств
     */
    const OPERATION_REFUND = 'refund';

    /**
     * Имена операций на русском языке
     */
    const OPERATION_NAMES = [
        self::OPERATION_REFILL => 'Пополнение',
        self::OPERATION_CUT    => 'Списание',
        self::OPERATION_REFUND => 'Возврат'
    ];

    /**
     * Презентер для данных компании
     *
     * @var \App\Models\Presenters\BalanceHistory\Data
     */
    protected $presenter;

    /**
     * Возвращает того кто создал операцию
     *
     * @return BelongsTo
     */
    public function createdUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_id', 'id');
    }

    /**
     * Возвращает идентификатор записи
     *
     * @return int
     */
    public function getId(): int
    {
        return (int) $this->getAttribute('id');
    }

    /**
     * Возвращает строковое название операции по счёту
     *
     * @return string
     */
    public function getOperation(): string
    {
        return (string) $this->getAttribute('operation');
    }

    /**
     * Название сумму транзакции
     *
     * @return float
     */
    public function getPaid(): float
    {
        return (float) $this->getAttribute('paid');
    }

    /**
     * Название сумму остатка на счету
     *
     * @return float
     */
    public function getResidue(): float
    {
        return (float) $this->getAttribute('residue');
    }

    /**
     * Возвращает дату проведения транзакции
     *
     * @return mixed
     */
    public function getPaidAt(): mixed
    {
        return $this->getAttribute('paid_at');
    }

    /**
     * Возвращает комментарий к операции
     *
     * @return string
     */
    public function getComment(): string
    {
        return (string) $this->getAttribute('comment');
    }

    /**
     * Получить имя проводимой операции
     *
     * @return string
     */
    public function getOperationName(): string
    {
        return self::OPERATION_NAMES[$this->getOperation()] ?? 'Не определена';
    }

    /**
     * Возвращает презентер
     *
     * @return \App\Models\Presenters\BalanceHistory\Data
     */
    public function getPresenter(): BalanceHistoryData
    {
        if (!$this->presenter) {
            $this->presenter = new BalanceHistoryData($this);
        }

        return $this->presenter;
    }
}
