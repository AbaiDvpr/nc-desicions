<?php declare(strict_types=1);

namespace App\Models\Courses;

use Illuminate\Support\Carbon;
use MongoDB\Laravel\Eloquent\Model;

/**
 * Сущность для хранения рейтинга урока
 */
class RatingLesson extends Model
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
    protected $collection = 'course-rating-lessons';

    /**
     * @inheritdoc
     *
     * @var array
     */
    protected $guarded = [];

    /**
     * ID модуля
     *
     * @var string
     */
    protected string $moduleId;

    /**
     * ID урока
     *
     * @var string
     */
    protected string $lessonId;

    /**
     * ID пользователя
     *
     * @var int
     */
    protected int $userId;

    /**
     * Оценка урока
     *
     * @var int
     */
    protected int $rate;

    /**
     * Возвращает ID записи
     *
     * @return string
     */
    public function getId(): string
    {
        return (string) $this->getAttribute('_id');
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
     * @param  string                          $moduleId
     * @return \App\Models\Courses\RatingLesson
     */
    public function setModuleId(string $moduleId): self
    {
        $this->setAttribute('moduleId', $moduleId);

        return $this;
    }

    /**
     * Возвращает ID урока
     *
     * @return string
     */
    public function getLessonId(): string
    {
        return (string) $this->getAttribute('lessonId');
    }

    /**
     * Устанавливает ID урока
     *
     * @param  string                          $lessonId
     * @return \App\Models\Courses\RatingLesson
     */
    public function setLessonId(string $lessonId): self
    {
        $this->setAttribute('lessonId', $lessonId);

        return $this;
    }

    /**
     * Возвращает ID пользователя
     *
     * @return int
     */
    public function getUserId(): int
    {
        return (int) $this->getAttribute('userId');
    }

    /**
     * Устанавливает ID пользователя
     *
     * @param  int                             $userId
     * @return \App\Models\Courses\RatingLesson
     */
    public function setUserId(int $userId): self
    {
        $this->setAttribute('userId', $userId);

        return $this;
    }

    /**
     * Возвращает оценку урока
     *
     * @return int
     */
    public function getRate(): int
    {
        return (int) $this->getAttribute('rate');
    }

    /**
     * Устанавливает оценку уроку
     *
     * @param  int                             $rate
     * @return \App\Models\Courses\RatingLesson
     */
    public function setRate(int $rate): self
    {
        $this->setAttribute('rate', $rate);

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
}