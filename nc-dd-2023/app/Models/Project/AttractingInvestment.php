<?php declare(strict_types=1);

namespace App\Models\Project;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property mixed gracePeriod
 * @property mixed loanTerm
 * @property mixed annualRate
 */
class AttractingInvestment extends Model
{
    use SoftDeletes, HasFactory;

    /**
     * @inheritdoc
     *
     * @var string
     */
    protected $table = 'projects-attracting-investments';

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
     * @var string[]
     */
    protected $casts = [
        'annuity' => 'boolean',
    ];

    /**
     * @inheritdoc
     *
     * @var array
     */
    protected $guarded = [];

    /**
     * Поля, которые определяют, подается проект на привлечение основных средств
     *
     * @var array
     */
    public const FIXED_ASSETS_FIELDS = [
        'smr',
        'overhaul',
        'readyPremises',
        'equipment',
    ];

    /**
     * Возвращает срок кредита в месяцах
     *
     * @return int
     */
    public function getLoanTerm(): int
    {
        return $this->getAttribute('loanTerm') ?? 6;
    }

    /**
     * Возвращает кол-во месяцев с учетом льготного периода
     *
     * @return int
     */
    public function getGracePeriod(): int
    {
        return $this->getAttribute('gracePeriod')
            ? ($this->getLoanTerm() - $this->getAttribute('gracePeriod'))
            : $this->getLoanTerm();
    }

    /**
     * Аннуитетный расчет
     *
     * @return bool
     */
    public function isAnnuity(): bool
    {
        return (bool) $this->getAttribute('annuity');
    }
}
