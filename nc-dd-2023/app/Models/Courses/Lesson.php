<?php declare(strict_types=1);

namespace App\Models\Courses;

use App\DI;
use App\Models\Presenters\Courses\Lesson\Data;
use Illuminate\Support\Carbon;
use MongoDB\Laravel\Eloquent\Model;

/**
 * Сущность для хранения списка урока курсов
 *
 * @property bool   $good
 * @property string $title
 * @property string $description
 * @property string $linkVideo
 * @property string $linkAdditional
 */
class Lesson extends Model
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
    protected $collection = 'course-lessons';

    /**
     * @inheritdoc
     *
     * @var array
     */
    protected $guarded = [];

    /**
     * Включен ли урок
     *
     * @var bool
     */
    protected bool $good;

    /**
     * Наименование урока
     *
     * @var string
     */
    protected string $title;

    /**
     * Описание урока
     *
     * @var string
     */
    protected string $description;

    /**
     * Ссылка на ролик
     *
     * @var string
     */
    protected string $linkVideo;

    /**
     * Ссылка на доп. материалы
     *
     * @var string
     */
    protected string $linkAdditional;

    /**
     * ID модуля
     *
     * @var string
     */
    protected string $moduleId;

    /**
     * Порядок урока
     *
     * @var int
     */
    protected int $order;

    /**
     * Презентер для публичных данных
     *
     * @var \App\Models\Presenters\Courses\Lesson\Data
     */
    protected $presenter;

    /**
     * Возвращает ID урока
     *
     * @return string
     */
    public function getId(): string
    {
        return (string) $this->getAttribute('_id');
    }

    /**
     * Включен ли урок для отображения
     *
     * @return bool
     */
    public function isGood(): bool
    {
        return (bool) $this->getAttribute('good');
    }

    /**
     * Включает или выключает урок для отображения
     *
     * @param  bool                       $good
     * @return \App\Models\Courses\Lesson
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
     * Устанавливает наименование урока
     *
     * @param  string                     $title
     * @return \App\Models\Courses\Lesson
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
     * Устанавливает описание урока
     *
     * @param  string                     $description
     * @return \App\Models\Courses\Lesson
     */
    public function setDescription(string $description): self
    {
        $this->setAttribute('description', $description);

        return $this;
    }

    /**
     * Возвращает ссылку на ролик
     *
     * @return string
     */
    public function getLinkVideo(): string
    {
        return (string) $this->getAttribute('linkVideo');
    }

    /**
     * Устанавливает ссылку на ролик
     *
     * @param  string                     $linkVideo
     * @return \App\Models\Courses\Lesson
     */
    public function setLinkVideo(string $linkVideo): self
    {
        $this->setAttribute('linkVideo', $linkVideo);

        return $this;
    }

    /**
     * Возвращает ссылку на доп. материалы
     *
     * @return string
     */
    public function getLinkAdditional(): string
    {
        return (string) $this->getAttribute('linkAdditional');
    }

    /**
     * Устанавливает ссылку на доп. материалы
     *
     * @param  string                     $linkAdditional
     * @return \App\Models\Courses\Lesson
     */
    public function setLinkAdditional(string $linkAdditional): self
    {
        $this->setAttribute('linkAdditional', $linkAdditional);

        return $this;
    }

    /**
     * Возвращает ID модуля
     *
     * @return string
     */
    public function getModuleId(): string
    {
        return (string) $this->getAttribute('moduleId');
    }

    /**
     * Устанавливает ID модуля
     *
     * @param  string                     $moduleId
     * @return \App\Models\Courses\Lesson
     */
    public function setModuleId(string $moduleId): self
    {
        $this->setAttribute('moduleId', $moduleId);

        return $this;
    }

    /**
     * Возвращает модуль урока
     *
     * @return \App\Models\Courses\Module|null
     */
    public function module(): ?Module
    {
        return DI::getRepository()->getCourses()->getModule()->getOne($this->getModuleId());
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
     * @return \App\Models\Courses\Lesson
     */
    public function setOrder(int $order = 0): self
    {
        $this->setAttribute('order', $order);

        return $this;
    }

    /**
     * Возвращает дату создания урока
     *
     * @return \Illuminate\Support\Carbon
     */
    public function getCreatedAt(): Carbon
    {
        return $this->created_at;
    }

    /**
     * Возвращает объект для работы с публичными данными
     *
     * @return \App\Models\Presenters\Courses\Lesson\Data
     */
    public function getPresenter(): Data
    {
        if (!$this->presenter) {
            $this->presenter = new Data($this);
        }

        return $this->presenter;
    }
}
