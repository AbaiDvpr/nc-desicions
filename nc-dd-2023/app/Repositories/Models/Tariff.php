<?php declare(strict_types=1);

namespace App\Repositories\Models;

use App\Models\Tariff as TariffModel;
use App\Repositories\BaseRepository;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class Tariff extends BaseRepository
{
    /**
     * Время хранения тарифа в кеше
     */
    const CACHE_LIVE_TIME = 86400;

    /**
     * Возвращает тариф по ID
     *
     * @param  int                     $id
     * @return \App\Models\Tariff|null
     */
    public function getOne(int $id): ?TariffModel
    {
        return $this->getCache()->getCached(
            $this->getCacheKey($id),
            function () use ($id) {
                return TariffModel::find($id);
            },
            self::CACHE_LIVE_TIME,
            $this->getTags($id)
        );
    }

    /**
     * Возвращает последние 3 активных тарифа
     *
     * @param  int   $type
     * @return array
     */
    public function getLastThreeActive(int $type = TariffModel::TYPE_MONTHLY): array
    {
        $cacheKey = sprintf('getLastThreeActive:%d', $type);
        $func     = function () use ($type) {
            $records = (new TariffModel())
                ->where('good', true)
                ->where('default', false)
                ->where('type', $type)
                ->orderBy('price')
                ->limit(3)
                ->get();

            return collect($records)->map(function ($record) {
                return $record->getPresenter()->getPublicData();
            })->toArray();
        };

        return $this->getCache()->getCached(
            $this->getCacheKey($cacheKey),
            $func,
            self::CACHE_LIVE_TIME,
            $this->getTags($cacheKey)
        );
    }

    /**
     * Возвращает активные тарифы для поля select
     *
     * @return array|array[]
     */
    public function getLatestActiveForSelect(): array
    {
        $tariffs = [
            config('tariffs.types')[TariffModel::TYPE_MONTHLY] => [],
            config('tariffs.types')[TariffModel::TYPE_ANNUAL]  => [],
        ];

        collect(array_merge(
            [$this->getDefault()->getPresenter()->getPublicData()],
            $this->getLastThreeActive(),
            $this->getLastThreeActive(TariffModel::TYPE_ANNUAL),
        ))->map(function ($tariff) use (&$tariffs) {
            $groupKey                          = config('tariffs.types')[$tariff['type']];
            $tariffs[$groupKey][$tariff['id']] = $tariff['name'];
        })->toArray();

        return $tariffs;
    }

    /**
     * Обновляет данные тарифного плана
     *
     * @param  int   $id
     * @param  array $fields
     * @return bool
     */
    public function update(int $id, array $fields): bool
    {
        $tariff = $this->getOne($id);

        if (is_null($tariff)) {
            throw new ModelNotFoundException('Тариф не найден');
        }

        $this->getCache()->deleteByTags($this->getTags($tariff->id));

        return $tariff->update($fields);
    }

    /**
     * Удаление тарифного плана
     *
     * @param  \App\Models\Tariff $tariff
     * @return bool|null
     */
    public function delete(TariffModel $tariff): ?bool
    {
        $this->getCache()->deleteByTags($this->getTags($tariff->id));

        return $tariff->delete();
    }

    /**
     * Возвращает тариф "По умолчанию"
     *
     * @return \App\Models\Tariff|null
     */
    public function getDefault(): ?TariffModel
    {
        return $this->getCache()->getCached(
            $this->getCacheKey('default'),
            function () {
                return (new TariffModel())->where('default', true)->oldest()->first();
            },
            self::CACHE_LIVE_TIME,
            $this->getTags('default')
        );
    }

    /**
     * Возвращает набор тегов для кеша
     *
     * @param  ...$args
     * @return array
     */
    protected function getTags(...$args): array
    {
        return [
            get_class($this),
            get_class($this) . ':' . implode('_', $args)
        ];
    }

    /**
     * Возвращает список тарифов для фильтра
     *
     * @return array
     */
    public function getForFilter(): array
    {
        $tariffs = TariffModel::where('good', true)->get();

        if ($tariffs->count() == 0) {
            return [];
        }

        $tariffOptions = [];

        foreach ($tariffs as $tariff) {
            $tariffOptions[$tariff->id] = $tariff->name;
        }

        return $tariffOptions;
    }
}
