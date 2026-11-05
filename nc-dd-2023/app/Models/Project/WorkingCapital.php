<?php declare(strict_types=1);

namespace App\Models\Project;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property mixed $replenishPeriod
 * @property mixed $gracePeriod
 */
class WorkingCapital extends Model
{
    use SoftDeletes, HasFactory;

    /**
     * @inheritdoc
     *
     * @var string
     */
    protected $table = 'projects-working-capital';

    /**
     * @inheritdoc
     *
     * @var array
     */
    protected $hidden = [
        'uuid',
        'deleted_at',
        'created_at',
        'updated_at'
    ];

    /**
     * @inheritdoc
     *
     * @var array
     */
    protected $guarded = [];

    /**
     * Поля, которые определяют, подается проект с учетом пополнения оборотных средств или нет
     *
     * @var array
     */
    public const WORKING_CAPITAL_FIELDS = [
        'raw',
        'advertising',
        'wages',
        'rentalExpenses',
        'otherExpenses',
    ];

    /**
     * Поля инвестиционных затрат
     *
     * @var array
     */
    public const INVESTMENT_FIELDS = [
        'smr',
        'overhaul',
        'readyPremises',
        'equipment',
    ];

    /**
     * Годовая ставка вознаграждения (6% по умолчанию)
     *
     * @return mixed
     */
    public function getAnnualRate()
    {
        return $this->annualRate ?? 6.00;
    }

    /**
     * Общий срок пополнения
     *
     * @return int|mixed
     */
    public function getReplenishPeriod()
    {
        return $this->replenishPeriod ?? 36;
    }

    /**
     * Льготный период
     *
     * @return int|mixed
     */
    public function getGracePeriod()
    {
        return $this->gracePeriod ?? 6;
    }

    /**
     * Возвращает кол-во месяцев с учетом льготного периода
     *
     * @return mixed
     */
    public function calculateGracePeriod()
    {
        return $this->getReplenishPeriod() ?
            ($this->getReplenishPeriod() - $this->getGracePeriod()) :
            $this->getReplenishPeriod();
    }
}
