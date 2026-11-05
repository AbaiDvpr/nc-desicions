<?php declare(strict_types=1);

namespace App\Models\Company\Reports\Fact;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Сущность для хранения отчетов ОПУ о прибыли
 */
class Opu extends Model
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
    protected $table = 'companies-opu-reports';

    /**
     * Возвращает чистую прибыль
     *
     * @return float
     */
    public function getNetProfit(): float
    {
        return (float) $this->getAttribute('netProfit');
    }

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
     * Возвращает выручку от реализации продукции
     *
     * @return float
     */
    public function getIncome(): float
    {
        return (float) $this->getAttribute('income');
    }

    /**
     * Возвращает себестоимость
     *
     * @return float
     */
    public function getCostPrice(): float
    {
        return (float) $this->getAttribute('costPrice');
    }

    /**
     * Возвращает ОиАР
     *
     * @return float
     */
    public function getGeneralAndAdminExpenses(): float
    {
        return (float) $this->getAttribute('generalAndAdminExpenses');
    }

    /**
     * Возвращает прочие доходы/расходы
     *
     * @return float
     */
    public function getOtherIncomeOrExpenses(): float
    {
        return (float) $this->getAttribute('otherIncomeOrExpenses');
    }
}
