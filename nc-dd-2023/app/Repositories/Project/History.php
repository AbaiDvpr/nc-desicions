<?php declare(strict_types=1);

namespace App\Repositories\Project;

use App\DI;
use App\Models\User;
use App\Models\Project;
use App\Repositories\BaseRepository;
use App\Models\Project\History as HistoryModel;
use Illuminate\Support\Facades\Log;

/**
 * Репозиторий для работы с историями фин моделей
 */
class History extends BaseRepository
{
    /**
     * Время жизни кеша (1 день)
     */
    const CACHE_LIVE_TIME = 86400;

    /**
     * Возвращает историю фин модели
     *
     * @param  \App\Models\Project             $project
     * @return \App\Models\Project\History|null
     */
    public function getCurrent(Project $project): ?HistoryModel
    {
        $func = function () use ($project) {
            return (new HistoryModel())->where('uuid', $project->getUuid())->first();
        };

        return $this->getCache()->getCached(
            $this->getCacheKey($project->getUuid()),
            $func,
            self::CACHE_LIVE_TIME,
            $this->getCacheTags($project->getUserId())
        );
    }

    /**
     * Возвращает количество историй по текущему тарифу пользователя
     *
     * @param  \App\Models\User $user
     * @return int
     */
    public function getItemsCount(User $user): int
    {
        $func = function () use ($user) {
            return (new HistoryModel())->where('userTariffId', $user->getTariffId())->count();
        };

        return (int) $this->getCache()->getCached(
            $this->getCacheKey($user->getAuthIdentifier()),
            $func,
            self::CACHE_LIVE_TIME,
            $this->getCacheTags($user->getAuthIdentifier())
        );
    }

    /**
     * Создает историю фин модели
     *
     * @param  \App\Models\User                $user
     * @param  \App\Models\Project             $project
     * @return \App\Models\Project\History|null
     */
    public function create(User $user, Project $project): ?HistoryModel
    {
        $userTariff = DI::getRepository()->getUserTariff()->getOne($user->getTariffId());
        $history    = (new HistoryModel())
            ->setUuid($project->getUuid())
            ->setTariffId($userTariff->getTariffId())
            ->setUserTariffId($userTariff->getId());

        if (!$history->save()) {
            Log::error('[History] Ошибка при создании истории фин модели', [
                'uuid' => $project->getUuid(),
            ]);

            return null;
        }

        $this->clearCache($user);

        return $history->refresh();
    }

    /**
     * Увеличить корректировку
     *
     * @param  \App\Models\Project $project
     * @return bool
     */
    public function increaseCorrection(Project $project): bool
    {
        return (bool) $this->getCurrent($project)->increment('correction');
    }

    /**
     * Очищает кеш истории для пользователя
     *
     * @param  \App\Models\User $user
     * @return bool
     */
    public function clearCache(User $user): bool
    {
        return $this->getCache()->deleteByTags(
            $this->getCacheTags($user->getAuthIdentifier())
        );
    }

    /**
     * Теги кеша для истории
     *
     * @param  int   $id
     * @return array
     */
    public function getCacheTags(int $id): array
    {
        return [
            sprintf('App\Repositories\Project\History:%d', $id)
        ];
    }
}
