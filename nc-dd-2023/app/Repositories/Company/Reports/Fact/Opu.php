<?php declare(strict_types=1);

namespace App\Repositories\Company\Reports\Fact;

use App\Models\Company\Filters\ReportParams;
use App\Models\Company\Reports\Fact\Opu as OpuReportModel;
use App\Repositories\BaseRepository;
use App\Repositories\Company\Reports\Plan\Opu as OpuPlan;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Репозиторий отчета ОПУ
 */
class Opu extends BaseRepository
{
    /**
     * @var $plan
     */
    protected $plan;

    /**
     * Ключ кеша для отчетов
     */
    const CACHE_KEY = 'DAILY_REPORT';

    /**
     * Время жизни кеша (1 день)
     */
    const CACHE_LIVE_TIME = 86400;

    /**
     * Создает/обновляет отчет
     *
     * @param  string $uuid
     * @param  array  $data
     * @return bool
     */
    public function createOrUpdate(string $uuid, array $data): bool
    {
        try {
            foreach ($data as $item) {
                $report = OpuReportModel::where('companyUuid', $uuid)->where('period', $item['period']);

                if ($report->exists()) {
                    $report->update($item);
                } else {
                    DB::transaction(function () use ($item) {
                        return OpuReportModel::insert($item);
                    }, 2);
                }
            }

            $this->getCache()->forget($this->getCacheKey(self::CACHE_KEY, $uuid));

            return true;
        } catch (\Exception $exception) {
            Log::error('[OpuReport] Ошибка сохранение данных', [
                'exception' => $exception
            ]);
        }

        return false;
    }

    /**
     * Возвращает отчет по uuid компании
     *
     * @param  string                                  $uuid
     * @param  bool                                    $useCache
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getList(string $uuid, bool $useCache = true): Collection
    {
        $func = function() use ($uuid) {
            return OpuReportModel::where('companyUuid', $uuid)->orderBy('period', 'ASC')->get();
        };

        if ($useCache) {
            return $this->getCache()->getCached(
                $this->getCacheKey(self::CACHE_KEY, $uuid),
                $func,
                self::CACHE_LIVE_TIME
            );
        }

        return $func();
    }

    /**
     * Возвращает отчет по uuid компании и периоду
     *
     * @param  string                                   $uuid
     * @param  string                                   $period
     * @return \App\Models\Company\Reports\Fact\Opu|null
     */
    public function getOneByPeriod(string $uuid, string $period): ?OpuReportModel
    {
        return OpuReportModel::where('companyUuid', $uuid)->where('period', $period)->first();
    }

    /**
     * Возвращает последний отчет по периоду
     *
     * @param  string                                   $uuid
     * @return \App\Models\Company\Reports\Fact\Opu|null
     */
    public function getForIndicator(string $uuid): ?OpuReportModel
    {
        return OpuReportModel::where('companyUuid', $uuid)->orderBy('period', 'DESC')->first();
    }

    /**
     * Возвращает отчет по параметрам фильтра
     *
     * @param  \App\Models\Company\Filters\ReportParams $params
     * @return array
     */
    public function getFromParams(ReportParams $params): array
    {
        $report = OpuReportModel::where('companyUuid', $params->getCompanyUuid())
            ->whereBetween('period', $params->getPeriod());

        $records = $report->orderBy('period', 'ASC')->get();

        return $records->toArray();
    }

    /**
     * Сущность для работы с планами ОПУ
     *
     * @return \App\Repositories\Company\Reports\Plan\Opu
     */
    public function getPlan(): OpuPlan
    {
        if (!$this->plan) {
            $this->plan = new OpuPlan();
        }

        return $this->plan;
    }
}
