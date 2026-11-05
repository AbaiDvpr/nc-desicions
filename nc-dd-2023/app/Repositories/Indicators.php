<?php declare(strict_types=1);

namespace App\Repositories;

use App\Models\Indicator as IndicatorModel;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Репозиторий для работы с показателями
 */
class Indicators extends BaseRepository
{
    /**
     * Ключ для показателей
     */
    const INDICATORS_CACHE_KEY = 'WEEKLY-INDICATORS';

    /**
     * Возвращает показатель по ID
     *
     * @param  int                        $id
     * @param  bool                       $useCache
     * @return \App\Models\Indicator|null
     */
    public function getById(int $id, bool $useCache = true): ?IndicatorModel
    {
        $indicators = function () use ($id) {
            return IndicatorModel::findOrFail($id);
        };

        if ($useCache) {
            return $this->getCache()->getCached(
                $this->getCacheKey(self::INDICATORS_CACHE_KEY, $id),
                $indicators,
                mt_rand(3600, 14400)
            );
        }

        return $indicators();
    }

    /**
     * Возвращает список показателей
     *
     * @return \Illuminate\Pagination\LengthAwarePaginator|null
     */
    public function getLatest(): ?LengthAwarePaginator
    {
        return IndicatorModel::latest()->paginate(20);
    }

    /**
     * Возвращает последний показатель
     *
     * @return \App\Models\Indicator|null
     */
    public function getLatestOne(): ?IndicatorModel
    {
        return IndicatorModel::latest()->first();
    }

    /**
     * Сохраняет массив показателей в базу
     *
     * @param  array $data
     * @return \App\Models\Indicator|null
     */
    public function storeFromArray(array $data): ?IndicatorModel
    {
        try {
            return DB::transaction(function () use ($data) {
                return IndicatorModel::create($data);
            }, 2);
        } catch (\Exception $exception) {
            Log::error('[Indicators] Ошибка сохранение данных', [
                'exception' => $exception
            ]);
        }

        return null;
    }

    /**
     * Обновляет показатель
     *
     * @param  int   $id
     * @param  array $data
     * @return bool|null
     */
    public function updateFromArray(int $id, array $data): ?bool
    {
        $indicator = IndicatorModel::find($id);

        if ($indicator) {
            return $indicator->update($data);
        }

        return null;
    }

    /**
     * Удаляет показатели по ID
     *
     * @param  int  $id
     * @return bool|null
     */
    public function removeById(int $id): ?bool
    {
        $indicator = IndicatorModel::find($id);

        if ($indicator) {
            return $indicator->delete();
        }

        return null;
    }
}
