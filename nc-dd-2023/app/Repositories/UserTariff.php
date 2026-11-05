<?php declare(strict_types=1);

namespace App\Repositories;

use App\Models\User as UserModel;
use App\Models\Tariff as TariffModel;
use App\Models\UserTariff as UserTariffModel;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Репозиторий пользовательских тарифов
 */
class UserTariff extends BaseRepository
{
    /**
     * Время жизни кеша (1 день)
     */
    const CACHE_LIVE_TIME = 86400;

    /**
     * Возвращает тариф по ID
     *
     * @param  int                        $id
     * @return \App\Models\UserTariff|null
     */
    public function getOne(int $id): ?UserTariffModel
    {
        $func = function () use ($id) {
            return UserTariffModel::find($id);
        };

        return $this->getCache()->getCached(
            $this->getCacheKey($id),
            $func,
            self::CACHE_LIVE_TIME
        );
    }

    /**
     * Применяет пользователю указанный тариф
     *
     * @param  \App\Models\User      $user
     * @param  \App\Models\Tariff    $tariff
     * @return \App\Models\User|null
     */
    public function apply(UserModel $user, TariffModel $tariff): ?UserModel
    {
        return DB::transaction(function () use ($user, $tariff) {
            $userTariff = (new UserTariffModel())
                ->setUserId($user->getAuthIdentifier())
                ->setTariffId($tariff->getId())
                ->setType($tariff->getType())
                ->setName($tariff->getTranslations('name'))
                ->setPrice($tariff->getPrice())
                ->setDescription($tariff->getTranslations('description'))
                ->setCompany($tariff->getCompany()->toArray())
                ->setProject($tariff->getProject());

            if (!$tariff->isDefault()) {
                $userTariff->setExpire();
            }

            if (!$userTariff->save()) {
                Log::error('[UserTariff] Ошибка при создании записи', [
                    'tariff' => $tariff->getLogData(),
                ]);

                return null;
            }

            $userTariff = $userTariff->refresh();

            $user->setTariffId($userTariff->getId());

            if (!$user->save()) {
                Log::error('[User] Ошибка при обновлении тарифа пользователя', [
                    'user'   => $user->getLogData(),
                    'tariff' => $tariff->getLogData(),
                ]);

                return null;
            }

            return $user->refresh();
        }, 2);
    }

    /**
     * Очищает кеш тарифа
     *
     * @param \App\Models\UserTariff $userTariff
     * @return bool
     */
    public function clearCache(UserTariffModel $userTariff): bool
    {
        return $this->getCache()->forget(
            $this->getCacheKey((string) $userTariff->getId())
        );
    }
}
