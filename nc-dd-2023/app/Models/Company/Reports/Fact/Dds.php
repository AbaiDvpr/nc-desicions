<?php declare(strict_types=1);

namespace App\Models\Company\Reports\Fact;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Сущность для хранения отчетов ДДС
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
    protected $table = 'companies-dds-reports';

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
     * Возвращает прирост чистого оборотного капитала
     *
     * @return float
     */
    public function getIncreaseInNetWorkingCapital(): float
    {
        return (float) $this->getAttribute('increaseInNetWorkingCapital');
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
