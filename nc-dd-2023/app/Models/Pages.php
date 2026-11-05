<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
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
 */
class Pages extends Model implements HasMedia
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
     * Field for translatable
     *
     * @var string[]
     */
    public $translatable = ['title', 'text'];

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
}
