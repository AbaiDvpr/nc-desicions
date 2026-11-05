<?php declare(strict_types=1);

namespace App\Repositories;

use App\Models\Rates as RatesModel;

/**
 * Репозиторий для работы с курсами валют
 */
class Rates extends BaseRepository
{
    /**
     * Сущность курсов валют
     *
     * @var \App\Models\Rates
     */
    protected $rate;

    /**
     * Общий тэг для всех крусов на каждый день
     */
    const RATES_CACHE_TAG = 'EXCHANGE_RATES';

    /**
     * Конструктор
     */
    public function __construct()
    {
        $this->rate = new RatesModel();
    }

    /**
     * Получает курсы валют на определенную дату
     *
     * @param  string                       $relevant
     * @param  bool                         $useCache
     * @return \App\Models\Rates|null
     */
    public function findByDate(string $relevant = '', bool $useCache = true): ?RatesModel
    {
        $rates = function () use ($relevant) {
            return $this->rate->whereRelevant($relevant)->first();
        };

        if ($useCache) {
            return $this->getCache()->getCached(
                $this->getCacheKey('DAILY-COURSES', $relevant),
                $rates,
                mt_rand(3600, 14400),
                self::RATES_CACHE_TAG
            );
        }

        return $rates();
    }
}
