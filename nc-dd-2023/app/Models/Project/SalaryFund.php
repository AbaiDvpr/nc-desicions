<?php declare(strict_types=1);

namespace App\Models\Project;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property mixed $percent
 * @property mixed $salaryPerMonth
 * @property mixed $services
 */
class SalaryFund extends Model
{
    use SoftDeletes, HasFactory;

    /**
     * @inheritdoc
     *
     * @var string
     */
    protected $table = 'projects-salary-fund';

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
     * @inheritdoc
     *
     * @var array
     */
    protected $casts = [
        'services' => 'array',
    ];

    /**
     * Получаем данные по выбранным услугам
     *
     * @return null
     */
    public function getIncomes()
    {
        // TODO: Нужно переделать получение из кеша
        if ($this->services) {
            return VolumeSales::whereIn('id', $this->services)->get();
        }

        return null;
    }
}
