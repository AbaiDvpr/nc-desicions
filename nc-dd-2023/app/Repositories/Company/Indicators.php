<?php declare(strict_types=1);

namespace App\Repositories\Company;

use App\DI;
use App\Models\Company\Reports\Plan\Balance as BalancePlanModel;
use App\Models\Company\Reports\Plan\Dds as DdsPlanModel;
use App\Models\Company\Reports\Plan\Opu as OpuPlanModel;
use App\Repositories\Company\Reports\Plan\Opu as OpuPlanRepo;
use App\Repositories\Company\Reports\Plan\Dds as DdsPlanRepo;
use App\Repositories\Company\Reports\Plan\Balance as BalancePlanRepo;
use App\Repositories\BaseRepository;
use App\Models\Company\Indicator as IndicatorModel;
use App\Models\Company\Forms\Indicator as IndicatorForm;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Репозиторий показателей компании
 */
class Indicators extends BaseRepository
{
    /**
     * Ключ кеша
     */
    const CACHE_KEY = 'COMPANY-INDICATORS';

    /**
     * Время жизни кеша (1 день)
     */
    const CACHE_LIVE_TIME = 86400;

    /**
     * Возвращает список показателей
     *
     * @param string                                        $uuid
     * @return \Illuminate\Database\Eloquent\Collection|null
     */
    public function getList(string $uuid): ?Collection
    {
        try {
            return IndicatorModel::where('companyUuid', $uuid)->orderBy('created_at', 'ASC')->get();
        } catch (\Throwable $throwable) {
            Log::error('[CompanyIndicators] Ошибка при получении записей', [
                'exception' => $throwable
            ]);
        }

        return null;
    }

    /**
     * Возвращает показатель по ID
     *
     * @param int                                $id
     * @param bool                               $useCache
     * @return \App\Models\Company\Indicator|null
     */
    public function getOne(int $id, bool $useCache = true): ?IndicatorModel
    {
        $func = function () use ($id) {
            return IndicatorModel::find($id);
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
     * Создает показатель
     *
     * @param  \App\Models\Company\Forms\Indicator $form
     * @return array|null
     */
    public function create(IndicatorForm $form): ?IndicatorModel
    {
        return DB::transaction(function () use ($form) {
            $indicator = new IndicatorModel();

            $indicator->setCompanyUuid($form->getCompanyUuid());
            $indicator->setProportionVariableCosts($this->getProportionVariableCosts($form->getCompanyUuid()));
            $indicator->setNeedCash($this->getNeedCash($form->getCompanyUuid()));

            if (!$indicator->save()) {
                Log::error('[CompanyIndicators] Ошибка при создании записи', [
                    'form' => $form->toArray()
                ]);

                return null;
            }

            return $indicator->refresh();
        }, 2);
    }

    /**
     * Сохраняет показатель
     *
     * @param  \App\Models\Company\Indicator       $indicator
     * @param  \App\Models\Company\Forms\Indicator $form
     * @return bool
     */
    public function save(IndicatorModel $indicator, IndicatorForm $form): bool
    {
        $id = $indicator->getId();

        return DB::transaction(function () use ($indicator, $id, $form) {
            $indicator->setProportionVariableCosts($form->getProportionVariableCosts());
            $indicator->setLongTermAssets($form->getLongTermAssets());
            $indicator->setWorkingCapital($form->getWorkingCapital());
            $indicator->setNeedCash($form->getNeedCash());
            $indicator->setInterestRateOnLoans($form->getInterestRateOnLoans());
            $indicator->setIncomeTaxRate($form->getIncomeTaxRate());
            $indicator->setDividendPayoutRatio($form->getDividendPayoutRatio());
            $indicator->setInflationIndex($form->getInflationIndex());
            $indicator->setRevenueGrowthRate($form->getRevenueGrowthRate());
            $indicator->setLoanVolume($form->getLoanVolume());
            $indicator->setAcquisitionAssets($form->getAcquisitionAssets());

            $isSaved = $indicator->save();

            if (!$isSaved) {
                Log::error('[CompanyIndicators] Ошибка при сохранении записи', [
                    'form' => $form->toArray()
                ]);

                return false;
            }

            $this->getCache()->forget($this->getCacheKey(self::CACHE_KEY, $id));
            $this->calculatePlans($id);

            return $isSaved;
        }, 2);
    }

    /**
     * Удаляет показатель
     *
     * @param  \App\Models\Company\Indicator $indicator
     * @return bool
     */
    public function delete(IndicatorModel $indicator): bool
    {
        return DB::transaction(function () use ($indicator) {
            $this->clearPlans($indicator->getCompanyUuid(), $indicator->getId());

            $this->getCache()->forget($this->getCacheKey(self::CACHE_KEY, $indicator->getId()));

            return (bool) $indicator->delete();
        }, 2);
    }

    /**
     * Производит подсчёт плана ОПУ, ДДС и Баланса
     * TODO: Переделать
     *
     * @param  int  $id
     * @return void
     */
    public function calculatePlans(int $id): void
    {
        $companyRepo = DI::getRepository()->getCompany();

        $companyRepo->getOpuReport()->getPlan()->calculate($id);
        $companyRepo->getDdsReport()->getPlan()->calculate($id);
        $companyRepo->getBalanceReport()->getPlan()->calculate($id);
    }

    /**
     * Очищает планы ОПУ, ДДС и Баланса
     *
     * @param  string $companyUuid
     * @param  int    $id
     * @return void
     */
    public function clearPlans(string $companyUuid, int $id): void
    {
        $opuPlan     = OpuPlanModel::where('companyUuid', $companyUuid)->where('indicatorId', $id);
        $ddsPlan     = DdsPlanModel::where('companyUuid', $companyUuid)->where('indicatorId', $id);
        $balancePlan = BalancePlanModel::where('companyUuid', $companyUuid)->where('indicatorId', $id);

        $this->getCache()->forget(
            $this->getCacheKey(OpuPlanRepo::CACHE_KEY, $companyUuid, $id)
        );
        $this->getCache()->forget(
            $this->getCacheKey(DdsPlanRepo::CACHE_KEY, $companyUuid, $id)
        );
        $this->getCache()->forget(
            $this->getCacheKey(BalancePlanRepo::CACHE_KEY, $companyUuid, $id)
        );

        $opuPlan->delete();
        $ddsPlan->delete();
        $balancePlan->delete();
    }

    /**
     * Вычисляет процентное значение доли переменных затрат
     *
     * @param  string $uuid
     * @return float
     */
    public function getProportionVariableCosts(string $uuid): float
    {
        $opuReport               = DI::getRepository()->getCompany()->getOpuReport()->getForIndicator($uuid);
        $proportionVariableCosts = 0;

        if ($opuReport) {
            $proportionVariableCosts = abs($opuReport->getCostPrice()) / abs($opuReport->getIncome()) * 100;
        }

        return round($proportionVariableCosts, 3);
    }

    /**
     * Вычисляет процентное значение потребности в денежных средствах
     *
     * @param  string $uuid
     * @return float
     */
    public function getNeedCash(string $uuid): float
    {
        $opuReport     = DI::getRepository()->getCompany()->getOpuReport()->getForIndicator($uuid);
        $balanceReport = DI::getRepository()->getCompany()->getBalanceReport()->getForIndicator($uuid);
        $needCash      = 0;

        if ($opuReport && $balanceReport) {
            $needCash = abs($balanceReport->getCash()) / abs($opuReport->getIncome()) * 100;
        }

        return round($needCash, 3);
    }
}
