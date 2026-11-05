<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Translatable\HasTranslations;

/**
 * @method static find(int $id)
 * @method static findOrFail(int $param)
 * @method static create(array $array)
 * @method static whereGood(bool $true)
 * @method static whereSlug(string $param)
 */
class Categories extends Model
{
    use HasFactory, SoftDeletes, HasTranslations;

    /**
     * @inheritdoc
     *
     * @var array
     */
    protected $guarded = [];

    /**
     * Field for translatable
     *
     * @var string[]
     */
    public $translatable = ['title', 'description'];

    /**
     * @return int
     */
    public function getId(): int
    {
        return (int) $this->getAttributeValue('id');
    }

    /**
     * Получить все новости категории
     *
     * @return HasMany
     */
    public function news(): HasMany
    {
        return $this->hasMany(News::class, 'category_id', 'id');
    }
}
