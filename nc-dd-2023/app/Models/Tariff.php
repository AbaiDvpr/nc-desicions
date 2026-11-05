<?php declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\AsCollection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Collection;
use JetBrains\PhpStorm\ArrayShape;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Spatie\Translatable\HasTranslations;
use Illuminate\Database\Eloquent\Model;
use App\Models\Presenters\Tariff\Data as TariffData;

/**
 * Модель для работы с тарифами
 *
 * @method static first()
 * @method static whereGood(bool $true)
 */
class Tariff extends Model implements HasMedia
{
    use HasFactory, SoftDeletes, HasTranslations, InteractsWithMedia;

    /**
     * Имя коллекции для Обложки
     */
    const COVER_COLLECTION_NAME = 'cover-image';

    /**
     * Типы тарифов
     */
    const TYPE_MONTHLY = 1; // Месячный
    const TYPE_ANNUAL  = 2; // Годовой

    /**
     * @inheritdoc
     *
     * @var array
     */
    protected $guarded = [];

    /**
     * Field for translatable
     *
     * @var array
     */
    public array $translatable = ['name', 'description'];

    /**
     * @inheritdoc
     *
     * @var string[]
     */
    protected $casts = [
        'company' => AsCollection::class,
    ];

    /**
     * Презентер для данных компании
     *
     * @var \App\Models\Presenters\Tariff\Data
     */
    protected $presenter;

    /**
     * Список проектов использующих данный тариф
     *
     * @return HasMany
     */
    protected function projects(): HasMany
    {
        return $this->hasMany(Project::class);
    }

    /**
     * Возвращает идентификатор записи
     *
     * @return int
     */
    public function getId(): int
    {
        return (int) $this->getAttributeValue('id');
    }

    /**
     * Является ли тариф тарифом "по умолчанию"
     *
     * @return bool
     */
    public function isDefault(): bool
    {
        return (bool) $this->getAttributeValue('default');
    }

    /**
     * Возвращает тип тарифа
     *
     * @return int
     */
    public function getType(): int
    {
        return (int) $this->getAttribute('type');
    }

    /**
     * Возвращает наименование тарифа согласно текущей локализации
     *
     * @return string
     */
    public function getName(): string
    {
        return (string) $this->getAttributeValue('name');
    }

    /**
     * Возвращает наименование тарифа согласно текущей локализации
     *
     * @return string
     */
    public function getDescription(): string
    {
        return (string) $this->getAttributeValue('description');
    }

    /**
     * Возвращает кол-во корректировок по тарифу
     *
     * @return int
     */
    public function getCorrection(): int
    {
        return (int) $this->getAttributeValue('correction');
    }

    /**
     * Возвращает кол-во проектов для расчета по тарифу
     *
     * @return int
     */
    public function getMaxProjects(): int
    {
        return (int) $this->getAttributeValue('maxProjects');
    }

    /**
     * Возвращает цену по тарифу
     *
     * @return float
     */
    public function getPrice(): float
    {
        return (float) $this->getAttributeValue('price');
    }

    /**
     * Возвращает параметры по ограничениям для "Управленческого учёта"
     *
     * @return \Illuminate\Support\Collection
     */
    public function getCompany(): Collection
    {
        return $this->getAttributeValue('company');
    }

    /**
     * Возвращает параметры по ограничениям для "Финансовых моделей"
     *
     * @return array
     */
    public function getProject(): array
    {
        return [
            'count'      => (int) $this->getAttributeValue('maxProjects'),
            'correction' => (int) $this->getAttributeValue('correction'),
        ];
    }

    /**
     * Возвращает данные для записи в лог
     *
     * @return array
     */
    #[ArrayShape(['id' => "int", 'price' => "float"])]
    public function getLogData(): array
    {
        return [
            'id'    => $this->getId(),
            'price' => $this->getPrice(),
        ];
    }

    /**
     * Возвращает объект для работы с данными пользователя
     *
     * @return \App\Models\Presenters\Tariff\Data
     */
    public function getPresenter(): TariffData
    {
        if (!$this->presenter) {
            $this->presenter = new TariffData($this);
        }

        return $this->presenter;
    }

    /**
     * @param  Media|null $media
     * @throws \Spatie\Image\Exceptions\InvalidManipulation
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
     * Является ли тариф ежемесячным
     *
     * @return bool
     */
    public function isMonthly(): bool
    {
        return $this->getType() === self::TYPE_MONTHLY;
    }

    /**
     * Является ли тариф годовым
     *
     * @return bool
     */
    public function isAnnual(): bool
    {
        return $this->getType() === self::TYPE_ANNUAL;
    }
}
