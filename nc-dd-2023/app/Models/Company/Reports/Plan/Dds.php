<?php declare(strict_types=1);

namespace App\Models\Company\Reports\Plan;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Сущность для хранения плана ДДС
 */
class Dds extends Model
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
    protected $table = 'companies-dds-reports-plan';

    /**
     * Возвращает инвестиции
     *
     * @return float
     */
    public function getInvestments(): float
    {
        return (float) $this->getAttribute('investments');
    }

    /**
     * Возвращает денежные средства на конец периода
     *
     * @return float
     */
    public function getCashAtEndPeriod(): float
    {
        return (float) $this->getAttribute('cashAtEndPeriod');
    }
}
