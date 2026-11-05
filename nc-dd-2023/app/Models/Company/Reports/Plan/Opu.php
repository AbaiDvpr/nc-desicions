<?php declare(strict_types=1);

namespace App\Models\Company\Reports\Plan;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Сущность для хранения плана ОПУ
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
    protected $table = 'companies-opu-reports-plan';

    /**
     * Возвращает чистую прибыль
     *
     * @return float
     */
    public function getNetProfit(): float
    {
        return (float) $this->getAttribute('netProfit');
    }
}
