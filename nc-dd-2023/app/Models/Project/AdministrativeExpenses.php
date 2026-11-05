<?php declare(strict_types=1);

namespace App\Models\Project;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class AdministrativeExpenses extends Model
{
    use SoftDeletes, HasFactory;

    /**
     * @inheritdoc
     *
     * @var string
     */
    protected $table = 'projects-administrative-expenses';

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
     * Типы расходов
     */
    const TYPE_OTHER = 0; // Прочее
    const TYPE_TAX   = 1; // Налог
    const TYPE_RENT  = 2; // Аренда
}
