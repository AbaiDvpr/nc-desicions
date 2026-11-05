<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Image\Exceptions\InvalidManipulation;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Spatie\Translatable\HasTranslations;

/**
 * @method static whereAlias(string $slug)
 * @method static find(int $id)
 * @method static findOrFail(int $param)
 * @method static create(array $array)
 * @method static where(string $string, $id)
 * @method static whereGood(bool $true)
 * @method static whereDraft(false $false)
 */
class News extends Model implements HasMedia
{
    use HasFactory, SoftDeletes, HasTranslations, InteractsWithMedia;

    /**
     * Имя коллекции для Обложки
     */
    const COVER_COLLECTION_NAME = 'cover-image';

    /**
     * @inheritdoc
     *
     * @var array
     */
    protected $guarded = [];

    /**
     * ID категории новости
     *
     * @var int
     */
    protected int $categoryId;

    /**
     * Черновик или нет
     *
     * @var bool
     */
    protected bool $draft;

    /**
     * Field for translatable
     *
     * @var string[]
     */
    public $translatable = ['title', 'short', 'full'];

    /**
     * @param Media|null $media
     * @throws InvalidManipulation
     */
    public function registerMediaConversions(Media $media = null): void
    {
        $this->addMediaConversion('smallThumb')
            ->width(70)->height(70)->sharpen(10)
            ->performOnCollections('*')
            ->nonQueued();

        $this->addMediaConversion('mediumThumb')
            ->width(300)->height(300)->sharpen(10)
            ->performOnCollections('*')
            ->nonQueued();

        $this->addMediaConversion('largeThumb')
            ->width(600)->height(600)->sharpen(15)
            ->performOnCollections('*')
            ->nonQueued();
    }

    /**
     * @return int
     */
    public function getCategoryId(): int
    {
        return $this->getAttributeValue('category_id');
    }

    /**
     * @param  int              $categoryId
     * @return \App\Models\News
     */
    public function setCategoryId(int $categoryId): self
    {
        $this->setAttribute('category_id', $categoryId);

        return $this;
    }

    /**
     * @return bool
     */
    public function getDraft(): bool
    {
        return (bool) $this->getAttributeValue('draft');
    }

    /**
     * @param  bool             $draft
     * @return \App\Models\News
     */
    public function setDraft(bool $draft): self
    {
        $this->setAttribute('draft', $draft);

        return $this;
    }

    /**
     * Получить категории новости
     *
     * @return BelongsTo
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Categories::class, 'category_id', 'id');
    }
}
