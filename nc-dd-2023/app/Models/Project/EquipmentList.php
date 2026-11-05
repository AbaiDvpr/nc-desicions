<?php declare(strict_types=1);

namespace App\Models\Project;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class EquipmentList extends Model
{
    use SoftDeletes, HasFactory;

    /**
     * @inheritdoc
     *
     * @var string
     */
    protected $table = 'projects-equipment-lists';

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
     * @return string
     */
    public function getUuid(): string
    {
        return (string) $this->getAttribute('uuid');
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return (string) $this->getAttribute('name');
    }

    /**
     * Возвращает кол-во закупаемого оборудования
     *
     * @return int
     */
    public function getCount(): int
    {
        return (int) $this->getAttribute('count');
    }

    /**
     * Возвращает цену оборудования за одну единицу
     *
     * @return int
     */
    public function getPriceOneUnit(): int
    {
        return (int) $this->getAttribute('priceOneUnit');
    }

    /**
     * Возвращает общую цену оборудования с учётом кол-ва и цены за одну единицу
     *
     * @return int
     */
    public function getPrice(): int
    {
        return (int) $this->getAttribute('price');
    }
}
