<?php declare(strict_types=1);

namespace App\Repositories\Models;

use App\Jobs\Frontend\Feedback\FeedbackAdminSend;
use App\Models\Feedback as FeedbackModel;
use App\Models\Feedback\Form\Feedback as FeedbackForm;
use App\Repositories\BaseRepository;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Репозиторий для работы с формой Обратной связи
 */
class Feedback extends BaseRepository
{
    /**
     * Ключ кеша
     */
    const CACHE_KEY = 'feedbacks';

    /**
     * Время жизни кеша (1 день)
     */
    const CACHE_LIVE_TIME = 86400;

    /**
     * Возвращает одну заявку по ID
     *
     * @param  int                      $id
     * @param  bool                     $useCache
     * @return \App\Models\Feedback|null
     */
    public function getOne(int $id, bool $useCache = true): ?FeedbackModel
    {
        $func = function() use ($id) {
            return FeedbackModel::find($id);
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
     * Возвращает список заявок с пагинацией
     *
     * @param  int                                             $perPage
     * @return \Illuminate\Pagination\LengthAwarePaginator|null
     */
    public function getWithPaginate(int $perPage = 20): ?LengthAwarePaginator
    {
        return FeedbackModel::latest()->paginate($perPage);
    }

    /**
     * Создает заявку
     *
     * @param \App\Models\Feedback\Form\Feedback $form
     * @return bool
     */
    public function storeFromArray(FeedbackForm $form): bool
    {
        try {
            $feedback = DB::transaction(function () use ($form) {
                $feedback = new FeedbackModel();

                $feedback
                    ->setName($form->getName())
                    ->setPhone($form->getPhone())
                    ->setLang($form->getLang())
                    ->setIp($form->getIp());

                if (!$feedback->save()) {
                    Log::error('[Feedback] Ошибка при сохранении новой заявки', $form->toArray());

                    return null;
                }

                return $feedback;
            }, 2);

            if ($feedback) {
                FeedbackAdminSend::dispatch($feedback->id);

                return true;
            }
        } catch (\Throwable $throwable) {
            Log::error('[Feedback] Ошибка при сохранении новой заявки', [
                'exception' => $throwable,
                'data'      => $form->toArray(),
            ]);
        }

        return false;
    }

    /**
     * Обновляет заявку
     *
     * @param  int   $id
     * @param  array $data
     * @return bool
     */
    public function update(int $id, array $data): bool
    {
        $feedback = $this->getOne($id);

        if (is_null($feedback)) {
            throw new ModelNotFoundException('Заявка не найдена');
        }

        return DB::transaction(function () use ($feedback, $data) {
            $this->getCache()->forget($this->getCacheKey(self::CACHE_KEY, $feedback->getId()));

            return $feedback->update($data);
        });
    }

    /**
     * Удаляет заявку
     *
     * @param  int $id
     * @return bool
     */
    public function delete(int $id): bool
    {
        $feedback = $this->getOne($id);

        if (is_null($feedback)) {
            throw new ModelNotFoundException('Заявка не найдена');
        }

        return DB::transaction(function () use ($feedback) {
            $this->getCache()->forget($this->getCacheKey(self::CACHE_KEY, $feedback->getId()));

            return (bool) $feedback->delete();
        });
    }
}
