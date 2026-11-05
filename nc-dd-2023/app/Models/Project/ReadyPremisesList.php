<?php declare(strict_types=1);

namespace App\Models\Project;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Модель данных о перечне приобретаемой недвижимости
 */
class ReadyPremisesList extends Model
{
    use SoftDeletes, HasFactory;

    /**
     * Тип записей
     */
    const REAL_ESTATE_TYPE = 1; // Приобретаемая недвижимость
    const SMR_TYPE         = 2; // Строительно-монтажные работы
    const OVERHAUL_TYPE    = 3; // Капитальный ремонт

    /**
     * @inheritdoc
     *
     * @var string
     */
    protected $table = 'projects-ready-premises-lists';

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
}
