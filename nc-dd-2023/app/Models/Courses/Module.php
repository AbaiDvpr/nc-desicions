<?php declare(strict_types=1);

namespace App\Models\Courses;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use MongoDB\Laravel\Eloquent\Model;
use App\Models\Presenters\Courses\Module\Data;

/**
 * Сущность для хранения списка модулей курсов
 *
 * @property bool   $good
 * @property string $title
 * @property string $description
 * @property string $plan
 */
class Module extends Model
{
    /**
     * @inheritdoc
     *
     * @var string
     */
    protected $connection = 'mongodb';

    /**
     * @inheritdoc
     *
     * @var string
     */
    protected $collection = 'course-modules';

    /**
     * @inheritdoc
     *
     * @var array
     */
    protected $guarded = [];

    /**
     * Включен ли модуль
     *
     * @var bool
     */
    protected bool $good;

    /**
     * Наименование модуля
     *
     * @var string
     */
    protected string $title;

    /**
     * Описание модуля
     *
     * @var string
     */
    protected string $description;

    /**
     * План модуля
     *
     * @var string
     */
    protected string $plan;

    /**
     * Порядок модуля
     *
     * @var int
     */
    protected int $order;

    /**
     * Презентер для публичных данных
     *
     * @var \App\Models\Presenters\Courses\Module\Data
     */
    protected $presenter;

    /**
     * Возвращает ID модуля
     *
     * @return string
     */
    public function getId(): string
    {
        return (string) $this->getAttribute('_id');
    }

    /**
     * Включен ли модуль для отображения
     *
     * @return bool
     */
    public function isGood(): bool
    {
        return (bool) $this->getAttribute('good');
    }

    /**
     * Включает или выключает модуль для отображения
     *
     * @param  bool                       $good
     * @return \App\Models\Courses\Module
     */
    public function setGood(bool $good): self
    {
        $this->setAttribute('good', $good);

        return $this;
    }

    /**
     * Возвращает наименование модуля
     *
     * @return string
     */
    public function getTitle(): string
    {
        return (string) $this->getAttribute('title');
    }

    /**
     * Устанавливает наименование модуля
     *
     * @param  string                     $title
     * @return \App\Models\Courses\Module
     */
    public function setTitle(string $title): self
    {
        $this->setAttribute('title', $title);

        return $this;
    }

    /**
     * Возвращает описание модуля
     *
     * @return string
     */
    public function getDescription(): string
    {
        return (string) $this->getAttribute('description');
    }

    /**
     * Устанавливает описание модуля
     *
     * @param  string                     $description
     * @return \App\Models\Courses\Module
     */
    public function setDescription(string $description): self
    {
        $this->setAttribute('description', $description);

        return $this;
    }

    /**
     * Возвращает план модуля
     *
     * @return string
     */
    public function getPlan(): string
    {
        return (string) $this->getAttribute('plan');
    }

    /**
     * Устанавливает план модуля
     *
     * @param  string                     $plan
     * @return \App\Models\Courses\Module
     */
    public function setPlan(string $plan): self
    {
        $this->setAttribute('plan', $plan);

        return $this;
    }

    /**
     * Возвращает номер порядка
     *
     * @return int
     */
    public function getOrder(): int
    {
        return (int) $this->getAttribute('order');
    }

    /**
     * Устанавливает номер порядка
     *
     * @param  int                       $order
     * @return \App\Models\Courses\Module
     */
    public function setOrder(int $order = 0): self
    {
        $this->setAttribute('order', $order);

        return $this;
    }

    /**
     * Возвращает дату создания модуля
     *
     * @return \Illuminate\Support\Carbon
     */
    public function getCreatedAt(): Carbon
    {
        return $this->created_at;
    }

    /**
     * Возвращает список уроков модуля
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function getLessons(): HasMany
    {
        return $this->hasMany(Lesson::class, 'moduleId', '_id')
            ->where('good', true);
    }

    /**
     * Возвращает объект для работы с публичными данными
     *
     * @return \App\Models\Presenters\Courses\Module\Data
     */
    public function getPresenter(): Data
    {
        if (!$this->presenter) {
            $this->presenter = new Data($this);
        }

        return $this->presenter;
    }
}
