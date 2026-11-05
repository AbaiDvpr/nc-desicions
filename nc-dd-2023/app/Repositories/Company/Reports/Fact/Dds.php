<?php declare(strict_types=1);

namespace App\Repositories\Company\Reports\Fact;

use App\Models\Company\Filters\ReportParams;
use App\Models\Company\Reports\Fact\Dds as DdsReportModel;
use App\Repositories\BaseRepository;
use App\Repositories\Company\Reports\Plan\Dds as DdsPlan;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Репозиторий отчета ДДС
 */
class Dds extends BaseRepository
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
                $report = DdsReportModel::where('companyUuid', $uuid)->where('period', $item['period']);

                if ($report->exists()) {
                    $report->update($item);
                } else {
                    DB::transaction(function () use ($item) {
                        return DdsReportModel::insert($item);
                    }, 2);
                }
            }

            $this->getCache()->forget($this->getCacheKey(self::CACHE_KEY, $uuid));

            return true;
        } catch (\Exception $exception) {
            Log::error('[DdsReport] Ошибка сохранение данных', [
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
            return DdsReportModel::where('companyUuid', $uuid)->orderBy('period', 'ASC')->get();
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
     * @return \App\Models\Company\Reports\Fact\Dds|null
     */
    public function getOneByPeriod(string $uuid, string $period): ?DdsReportModel
    {
        return DdsReportModel::where('companyUuid', $uuid)->where('period', $period)->first();
    }

    /**
     * Возвращает последний отчет по периоду
     *
     * @param  string                                   $uuid
     * @return \App\Models\Company\Reports\Fact\Dds|null
     */
    public function getForIndicator(string $uuid): ?DdsReportModel
    {
        return DdsReportModel::where('companyUuid', $uuid)->orderBy('period', 'DESC')->first();
    }

    /**
     * Возвращает отчет по параметрам фильтра
     *
     * @param  \App\Models\Company\Filters\ReportParams $params
     * @return array
     */
    public function getFromParams(ReportParams $params): array
    {
        $report = DdsReportModel::where('companyUuid', $params->getCompanyUuid())
            ->whereBetween('period', $params->getPeriod());

        $records = $report->orderBy('period', 'ASC')->get();

        return $records->toArray();
    }

    /**
     * Сущность для работы с планами ДДС
     *
     * @return \App\Repositories\Company\Reports\Plan\DDs
     */
    public function getPlan(): DdsPlan
    {
        if (!$this->plan) {
            $this->plan = new DdsPlan();
        }

        return $this->plan;
    }
}
