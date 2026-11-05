<?php declare(strict_types=1);

namespace App\Repositories\Courses;

use App\Models\Courses\Module as ModuleAlias;
use App\Models\Courses\RatingLesson;
use App\Models\Courses\UserLesson;
use App\Models\User;
use App\Repositories\BaseRepository;
use App\Models\Courses\Forms\Lesson as LessonForm;
use App\Models\Courses\Lesson as LessonModel;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Репозиторий для работы с уроками курсов
 */
class Lesson extends BaseRepository
{
    /**
     * Ключ кеша
     */
    const CACHE_KEY = 'course-lesson';

    /**
     * Время жизни кеша (1 день)
     */
    const CACHE_LIVE_TIME = 86400;

    /**
     * Возвращает урок по ID
     *
     * @param  string                   $id
     * @param  bool                     $useCache
     * @return \App\Models\Courses\Lesson|null
     */
    public function getOne(string $id, bool $useCache = true): ?LessonModel
    {
        $func = function() use ($id) {
            return (new LessonModel())->find($id);
        };

        if ($useCache) {
            return $this->getCache()->getCached(
                $this->getCacheKey(self::CACHE_KEY, $id),
                $func,
                self::CACHE_LIVE_TIME
            );
        }

        return $func();
    }

    /**
     * Получить уроки определенного модуля
     *
     * @param  string                                  $moduleId
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getByModule(string $moduleId): Collection
    {
        return (new LessonModel())
            ->where('good', true)
            ->where('moduleId', $moduleId)
            ->orderBy('order', 'ASC')
            ->get();
    }

    /**
     * Возвращает список заявок с пагинацией
     *
     * @param  int                                             $perPage
     * @return \Illuminate\Pagination\LengthAwarePaginator|null
     */
    public function getWithPaginate(int $perPage = 20): ?LengthAwarePaginator
    {
        return (new LessonModel())->orderBy('order', 'ASC')->paginate($perPage);
    }

    /**
     * Сохранение урока
     *
     * @param  \App\Models\Courses\Forms\Lesson $form
     * @param  string|null                      $id
     * @return \App\Models\Courses\Lesson
     * @throws \ErrorException
     */
    public function save(LessonForm $form, ?string $id = null): LessonModel
    {
        $lesson = $id ? $this->getOne($id) : new LessonModel();

        $lesson->setGood($form->getGood());
        $lesson->setTitle($form->getTitle());
        $lesson->setDescription($form->getDescription());
        $lesson->setLinkVideo($form->getLinkVideo());
        $lesson->setLinkAdditional($form->getLinkAdditional());
        $lesson->setModuleId($form->getModuleId());

        if ($id) {
            $this->getCache()->forget($this->getCacheKey(self::CACHE_KEY, $id));
        } else {
            $lesson->setOrder();
        }

        if (!$lesson->save()) {
            Log::error('[Courses] Ошибка сохранения урока', [
                'form' => $form->toArray()
            ]);

            throw new \ErrorException('Unable to save lesson');
        }

        return $lesson->refresh();
    }

    /**
     * Обновление порядка
     *
     * @param  \App\Models\Courses\Lesson $lesson
     * @param  int                        $order
     * @return bool
     */
    public function setOrder(LessonModel $lesson, int $order): bool
    {
        return DB::transaction(function () use ($lesson, $order) {
            $this->getCache()->forget($this->getCacheKey(self::CACHE_KEY, $lesson->getId()));

            return $lesson->update(['order' => $order]);
        });
    }

    /**
     * Получение списка завершенных уроков пользователя
     *
     * @param  \App\Models\User                         $user
     * @param  \App\Models\Courses\Module               $module
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getUserCompleted(User $user, ModuleAlias $module): Collection
    {
        return (new UserLesson())
            ->where('moduleId', $module->getId())
            ->where('userId', $user->getAuthIdentifier())
            ->latest()
            ->get();
    }

    /**
     * Проверяет, завершил ли пользователь урок
     *
     * @param  \App\Models\User           $user
     * @param  \App\Models\Courses\Module $module
     * @param  \App\Models\Courses\Lesson $lesson
     * @return bool
     */
    public function isCompletedLesson(User $user, ModuleAlias $module, LessonModel $lesson): bool
    {
        return (new UserLesson())
            ->where('moduleId', $module->getId())
            ->where('lessonId', $lesson->getId())
            ->where('userId', $user->getAuthIdentifier())
            ->exists();
    }

    /**
     * Сохраняет запись, что пользователь оценил урок
     *
     * @param  \App\Models\User           $user
     * @param  \App\Models\Courses\Module $module
     * @param  \App\Models\Courses\Lesson $lesson
     * @param  int                        $rate
     * @return bool
     * @throws \ErrorException
     */
    public function setRate(User $user, ModuleAlias $module, LessonModel $lesson, int $rate): bool
    {
        $rating = new RatingLesson();

        $rating->setUserId($user->getAuthIdentifier());
        $rating->setModuleId($module->getId());
        $rating->setLessonId($lesson->getId());
        $rating->setRate($rate);

        if (!$rating->save()) {
            Log::error('[Courses] Ошибка при сохранении оценки урока', [
                'userId'   => $user->getAuthIdentifier(),
                'moduleId' => $module->getId(),
                'lessonId' => $lesson->getId(),
                'rate'     => $rate,
            ]);

            throw new \ErrorException('Unable to save rate lesson');
        }

        return true;
    }

    /**
     * Возвращает среднюю оценку урока
     *
     * @param \App\Models\Courses\Lesson $lesson
     * @return float
     */
    public function getRating(LessonModel $lesson): float
    {
        return round((float) RatingLesson::where('lessonId', $lesson->getId())->avg('rate'), 1);
    }


    /**
     * Проголосовал ли пользователь за урок
     *
     * @param \App\Models\User           $user
     * @param \App\Models\Courses\Lesson $lesson
     * @return bool
     */
    public function isVoted(User $user, LessonModel $lesson): bool
    {
        return (new RatingLesson())
            ->where('userId', $user->getAuthIdentifier())
            ->where('lessonId', $lesson->getId())
            ->exists();
    }

    /**
     * Возвращает количество голосов урока
     *
     * @param \App\Models\Courses\Lesson $lesson
     * @return int
     */
    public function getVotes(LessonModel $lesson): int
    {
        return RatingLesson::where('lessonId', $lesson->getId())->count();
    }

    /**
     * Сохраняет запись, что пользователь завершил урок
     * 
     * @param  \App\Models\User               $user
     * @param  \App\Models\Courses\Module     $module
     * @param  \App\Models\Courses\Lesson     $lesson
     * @return \App\Models\Courses\UserLesson
     * @throws \ErrorException
     */
    public function setCompleted(User $user, ModuleAlias $module, LessonModel $lesson): UserLesson
    {
        $userLesson = new UserLesson();

        $userLesson->setUserId($user->getAuthIdentifier());
        $userLesson->setModuleId($module->getId());
        $userLesson->setLessonId($lesson->getId());

        if (!$userLesson->save()) {
            Log::error('[Courses] Ошибка при записи статуса "завершено" для урока', [
                'userId'   => $user->getAuthIdentifier(),
                'moduleId' => $module->getId(),
                'lessonId' => $lesson->getId(),
            ]);

            throw new \ErrorException('Unable to save lesson');
        }

        return $userLesson->refresh();
    }
}
