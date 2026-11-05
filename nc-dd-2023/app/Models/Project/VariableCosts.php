<?php

namespace App\Models\Project;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class VariableCosts extends Model
{
    use SoftDeletes, HasFactory;

    /**
     * @inheritdoc
     *
     * @var string
     */
    protected $table = 'projects-variable-costs';

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
     * Получение стоимости затрат
     *
     * @return float
     */
    public function getPrice(): float
    {
        return (float) $this->getAttributeValue('price');
    }

    /**
     * Возвращает ID записи из плана продаж
     *
     * @return int
     */
    public function getVsId(): int
    {
        return (int) $this->getAttributeValue('vs_id');
    }
}
