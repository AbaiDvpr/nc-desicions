<?php declare(strict_types=1);

namespace App\Models\Company\Reports\Fact;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Сущность для хранения отчетов Баланса
 */
class Balance extends Model
{
    use SoftDeletes;

    /**
     * @inheritdoc
     *
     * @var array
     */
    protected $guarded = [];

    /**
     * @inheritdoc
     *
     * @var string
     */
    protected $table = 'companies-balance-reports';

    /**
     * Возвращает период
     *
     * @return string
     */
    public function getPeriod(): string
    {
        return $this->getAttribute('period');
    }

    /**
     * Возвращает денежные средства
     *
     * @return float
     */
    public function getCash(): float
    {
        return (float) $this->getAttribute('cash');
    }

    /**
     * Возвращает долгосрочные активы
     *
     * @return float
     */
    public function getLongTermAssets(): float
    {
        return (float) $this->getAttribute('longTermAssets');
    }

    /**
     * Возвращает запасы
     *
     * @return float
     */
    public function getStocks(): float
    {
        return (float) $this->getAttribute('stocks');
    }

    /**
     * Возвращает прочие активы
     *
     * @return float
     */
    public function getOtherAssets(): float
    {
        return (float) $this->getAttribute('otherAssets');
    }

    /**
     * Возвращает уставный капитал
     *
     * @return float
     */
    public function getAuthorizedCapital(): float
    {
        return (float) $this->getAttribute('authorizedCapital');
    }

    /**
     * Возвращает нераспределенную прибыль
     *
     * @return float
     */
    public function getRetainedEarnings(): float
    {
        return (float) $this->getAttribute('retainedEarnings');
    }

    /**
     * Возвращает кредиты и займы
     *
     * @return float
     */
    public function getCreditsAndLoans(): float
    {
        return (float) $this->getAttribute('creditsAndLoans');
    }

    /**
     * Возвращает прочие обязательства
     *
     * @return float
     */
    public function getOtherLiabilities(): float
    {
        return (float) $this->getAttribute('otherLiabilities');
    }
}
