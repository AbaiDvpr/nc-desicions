<?php declare(strict_types=1);

namespace App\Repositories\Company\Reports\Plan;

use App\DI;
use App\Models\Company\Filters\ReportParams;
use App\Models\Company\Reports\Plan\Balance as BalancePlanModel;
use App\Repositories\BaseRepository;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Репозиторий плана Баланса
 */
class Balance extends BaseRepository
{
    /**
     * Ключ кеша для отчетов
     */
    const CACHE_KEY = 'DAILY_REPORT_PLAN';

    /**
     * Время жизни кеша (1 день)
     */
    const CACHE_LIVE_TIME = 86400;

    /**
     * Производит подсчёт плана относительно показателя и сохраняет
     *
     * @param  int $indicatorId
     * @return bool
     */
    public function calculate(int $indicatorId): bool
    {
        $companyRepo   = DI::getRepository()->getCompany();
        $indicator     = $companyRepo->getIndicators()->getOne($indicatorId);
        $uuid          = $indicator->getCompanyUuid();
        $balanceReport = $companyRepo->getBalanceReport()->getList($uuid);

        if ($balanceReport->count()) {
            $balancePlan = DI::getDH()->getCompany()->getBalance()->getPlan()->calculate($uuid, $indicator, $balanceReport);

            return $this->createOrUpdate($uuid, $balancePlan);
        }

        return false;
    }

    /**
     * Создает/обновляет план
     *
     * @param  string $uuid
     * @param  array  $data
     * @return bool
     */
    public function createOrUpdate(string $uuid, array $data): bool
    {
        try {
            foreach ($data as $item) {
                $report = BalancePlanModel::where('companyUuid', $uuid)
                    ->where('indicatorId', $item['indicatorId'])
                    ->where('period', $item['period']);

                if ($report->exists()) {
                    $report->update($item);
                } else {
                    DB::transaction(function () use ($item) {
                        return BalancePlanModel::insert($item);
                    }, 2);
                }

                $this->getCache()->forget($this->getCacheKey(
                    self::CACHE_KEY,
                    $uuid,
                    $item['indicatorId'])
                );
            }

            return true;
        } catch (\Exception $exception) {
            Log::error('[BalanceReportPlan] Ошибка сохранение данных', [
                'exception' => $exception
            ]);
        }

        return false;
    }

    /**
     * Возвращает план по uuid компании и id показателя
     *
     * @param  string                                  $uuid
     * @param  int                                     $indicatorId
     * @param  bool                                    $useCache
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getByIndicator(string $uuid, int $indicatorId, bool $useCache = true): Collection
    {
        $func = function() use ($uuid, $indicatorId) {
            return BalancePlanModel::where('companyUuid', $uuid)
                ->where('indicatorId', $indicatorId)
                ->orderBy('period', 'ASC')
                ->get();
        };

        if ($useCache) {
            return $this->getCache()->getCached(
                $this->getCacheKey(self::CACHE_KEY, $uuid, $indicatorId),
                $func,
                self::CACHE_LIVE_TIME
            );
        }

        return $func();
    }

    /**
     * Возвращает план по uuid компании и периоду
     *
     * @param  string                                       $uuid
     * @param  string                                       $period
     * @return \App\Models\Company\Reports\Fact\Balance|null
     */
    public function getOneByPeriod(string $uuid, string $period): ?BalancePlanModel
    {
        return BalancePlanModel::where('companyUuid', $uuid)->where('period', $period)->first();
    }

    /**
     * Возвращает отчет по параметрам фильтра
     *
     * @param  \App\Models\Company\Filters\ReportParams $params
     * @return array
     */
    public function getFromParams(ReportParams $params): array
    {
        $report = BalancePlanModel::where('companyUuid', $params->getCompanyUuid())
            ->whereBetween('period', $params->getPeriod())
            ->where('indicatorId', $params->getIndicatorId());

        $records = $report->orderBy('period', 'ASC')->get();

        return $records->toArray();
    }
}
