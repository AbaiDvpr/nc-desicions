<?php declare(strict_types=1);

namespace App\Repositories\Courses;

use App\Models\Courses\RatingLesson;
use App\Repositories\BaseRepository;
use App\Models\Courses\Forms\Module as ModuleForm;
use App\Models\Courses\Module as ModuleModel;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\Eloquent\Collection;

/**
 * Репозиторий для работы с модулями курсов
 */
class Module extends BaseRepository
{
    /**
     * Ключ кеша
     */
    const CACHE_KEY = 'course-module';

    /**
     * Время жизни кеша (1 день)
     */
    const CACHE_LIVE_TIME = 86400;

    /**
     * Возвращает модуль по ID
     *
     * @param  string                         $id
     * @param  bool                           $useCache
     * @return \App\Models\Courses\Module|null
     */
    public function getOne(string $id, bool $useCache = true): ?ModuleModel
    {
        $func = function() use ($id) {
            return (new ModuleModel())->find($id);
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
     * Возвращает список модулей с пагинацией
     *
     * @param  int                                             $perPage
     * @return \Illuminate\Pagination\LengthAwarePaginator|null
     */
    public function getWithPaginate(int $perPage = 20): ?LengthAwarePaginator
    {
        return (new ModuleModel())->orderBy('order', 'ASC')->paginate($perPage);
    }

    /**
     * Возвращает весь список модулей
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getAll(): Collection
    {
        return (new ModuleModel())
            ->where('good', true)
            ->orderBy('order', 'ASC')
            ->get();
    }

    /**
     * Возвращает весь список модулей для поля select
     *
     * @return array
     */
    public function getAllForSelect(): array
    {
        $modules = (new ModuleModel())
            ->where('good', true)
            ->orderBy('order', 'ASC')
            ->get();
        $data    = [];

        collect($modules)->map(function ($module) use (&$data) {
            $data[$module->id] = $module->title;
        });

        return $data;
    }

    /**
     * Сохранение модуля
     *
     * @param \App\Models\Courses\Forms\Module $form
     * @param string|null                      $id
     * @return \App\Models\Courses\Module
     * @throws \ErrorException
     */
    public function save(ModuleForm $form, ?string $id = null): ModuleModel
    {
        $module = $id ? $this->getOne($id) : new ModuleModel();

        $module->setGood($form->getGood());
        $module->setTitle($form->getTitle());
        $module->setDescription($form->getDescription());
        $module->setPlan($form->getPlan());

        if ($id) {
            $this->getCache()->forget($this->getCacheKey(self::CACHE_KEY, $id));
        } else {
            $module->setOrder();
        }

        if (!$module->save()) {
            Log::error('[Courses] Ошибка сохранения модуля', [
                'form' => $form->toArray()
            ]);

            throw new \ErrorException('Unable to save module');
        }

        return $module->refresh();
    }

    /**
     * Обновление порядка
     *
     * @param  \App\Models\Courses\Module $module
     * @param  int                        $order
     * @return bool
     */
    public function setOrder(ModuleModel $module, int $order): bool
    {
        return DB::transaction(function () use ($module, $order) {
            $this->getCache()->forget($this->getCacheKey(self::CACHE_KEY, $module->getId()));

            return $module->update(['order' => $order]);
        });
    }

    /**
     * Возвращает рейтинг модуля
     *
     * @param  \App\Models\Courses\Module $module
     * @return float
     */
    public function getRating(ModuleModel $module): float
    {
        return round((float) RatingLesson::where('moduleId', $module->getId())->avg('rate'), 1);
    }
}
