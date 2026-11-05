<?php declare(strict_types=1);

namespace App\Models\Courses;

use Illuminate\Support\Carbon;
use MongoDB\Laravel\Eloquent\Model;

/**
 * Сущность для хранения статусов урока пользователя
 */
class UserLesson extends Model
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
    protected $collection = 'course-user-lessons';

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
     * Статус урока
     *
     * @var int
     */
    protected int $status;

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
     * @param  string                         $moduleId
     * @return \App\Models\Courses\UserLesson
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
     * @param  string                         $lessonId
     * @return \App\Models\Courses\UserLesson
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
     * @param  int                           $userId
     * @return \App\Models\Courses\UserLesson
     */
    public function setUserId(int $userId): self
    {
        $this->setAttribute('userId', $userId);

        return $this;
    }

    /**
     * Возвращает статус урока
     *
     * @return int
     */
    public function getStatus(): int
    {
        return (int) $this->getAttribute('status');
    }

    /**
     * Устанавливает статус урока
     *
     * @param  int                            $status
     * @return \App\Models\Courses\UserLesson
     */
    public function setStatus(int $status): self
    {
        $this->setAttribute('status', $status);

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
