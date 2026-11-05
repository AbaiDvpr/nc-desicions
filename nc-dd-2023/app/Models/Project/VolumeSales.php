<?php declare(strict_types=1);

namespace App\Models\Project;

use App\Http\Controllers\Frontend\Cabinet\Projects\Form\Fields\VolumeSales as ConfigVolumeSales;
use App\Models\Project;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @method static whereIn(string $string, mixed $services)
 */
class VolumeSales extends Model
{
    use SoftDeletes, HasFactory;

    /**
     * @inheritdoc
     *
     * @var string
     */
    protected $table = 'projects-volume-sales';

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
        'seasonal' => 'array',
    ];

    /**
     * Возвращает идентификатора записи
     *
     * @return int
     */
    public function getId(): int
    {
        return (int) $this->getAttributeValue('id');
    }

    /**
     * Возвращает кол-во продаж в месяц
     *
     * @return int
     */
    public function getCount(): int
    {
        return (int) $this->getAttributeValue('count');
    }

    /**
     * Вернуть проект
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class, 'uuid', 'uuid');
    }

    /**
     * Получить записи себестоимости
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function getVariableCoast(): HasMany
    {
        return $this->hasMany(VariableCosts::class, 'vs_id', 'id');
    }

    /**
     * Вернуть название выбранного unit's
     *
     * @return string
     */
    public function getUnitValue(): string
    {
        $unitValues = ConfigVolumeSales::DEFAULT_INPUT['units']['values'];

        return $unitValues[$this->units] ?? '';
    }
}
