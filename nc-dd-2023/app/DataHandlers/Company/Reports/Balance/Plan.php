<?php declare(strict_types=1);

namespace App\DataHandlers\Company\Reports\Balance;

use App\DI;
use App\Models\Company\Indicator;
use App\Models\Company\Reports\Fact\Balance as BalanceReportModel;
use App\Models\Company\Reports\Plan\Opu as OpuPlanModel;
use App\Models\Company\Reports\Plan\Dds as DdsPlanModel;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;

/**
 * Обработчик данных для плана
 */
class Plan
{
    /**
     * Производит подсчёт плана
     *
     * @param  string                        $uuid
     * @param  \App\Models\Company\Indicator $indicator
     * @param  Collection                    $reports
     * @return array
     */
    public function calculate(string $uuid, Indicator $indicator, Collection $reports): array
    {
        return collect($reports)->map(function($report) use ($uuid, $indicator) {
            /** @var \App\Models\Company\Reports\Fact\Balance $report */

            $companyRepo = DI::getRepository()->getCompany();
            $period      = $this->getPeriod($report);
            $opuPlan     = $companyRepo->getOpuReport()->getPlan()->getOneByPeriod($uuid, $period);
            $ddsPlan     = $companyRepo->getDdsReport()->getPlan()->getOneByPeriod($uuid, $period);

            return [
                'companyUuid'          => $uuid,
                'indicatorId'          => $indicator->getId(),
                'period'               => $period,
                'longTermAssets'       => $this->getLongTermAssets($report, $ddsPlan),
                // 'netWorkingCapital'    => $this->getNetWorkingCapital(),
                'cash'                 => $this->getCash($ddsPlan),
                'stocks'               => $this->getStocks($report),
                'otherAssets'          => $this->getOtherAssets($report),
                'totalAssets'          => $this->getTotalAssets($report, $ddsPlan),
                'authorizedCapital'    => $this->getAuthorizedCapital($report),
                'retainedEarnings'     => $this->getRetainedEarnings($report, $opuPlan),
                'creditsAndLoans'      => $this->getCreditsAndLoans($report),
                'shortTermLiabilities' => $this->getShortTermLiabilities($report, $opuPlan, $ddsPlan),
                'otherLiabilities'     => $this->getOtherLiabilities($report),
                'totalLiabilities'     => $this->getTotalLiabilities($report, $opuPlan, $ddsPlan),
            ];
        })->toArray();
    }

    /**
     * Возвращает период
     *
     * @param  \App\Models\Company\Reports\Fact\Balance $report
     * @return string
     */
    public function getPeriod(BalanceReportModel $report): string
    {
        return Carbon::parse($report->getPeriod())->addYear()->format('Y-m-d');
    }

    /**
     * Возвращает долгосрочные активы
     *
     * @param  \App\Models\Company\Reports\Fact\Balance  $report
     * @param  \App\Models\Company\Reports\Plan\Dds|null $ddsPlan
     * @return float
     */
    public function getLongTermAssets(BalanceReportModel $report, DdsPlanModel|null $ddsPlan): float
    {
        return $ddsPlan ? $report->getLongTermAssets() + $ddsPlan->getInvestments() : $report->getLongTermAssets();
    }

    /**
     * Возвращает чистый оборотный капитал
     *
     * @return int
     */
    public function getNetWorkingCapital(): int
    {
        return 0;
    }

    /**
     * Возвращает денежные средства
     *
     * @param  \App\Models\Company\Reports\Plan\Dds|null $ddsPlan
     * @return float
     */
    public function getCash(DdsPlanModel|null $ddsPlan): float
    {
        return $ddsPlan ? $ddsPlan->getCashAtEndPeriod() : 0;
    }

    /**
     * Возвращает запасы
     *
     * @param  \App\Models\Company\Reports\Fact\Balance $report
     * @return float
     */
    public function getStocks(BalanceReportModel $report): float
    {
        return $report->getStocks();
    }

    /**
     * Возвращает прочие активы
     *
     * @param  \App\Models\Company\Reports\Fact\Balance $report
     * @return float
     */
    public function getOtherAssets(BalanceReportModel $report): float
    {
        return $report->getOtherAssets();
    }

    /**
     * Возвращает итоговые активы
     *
     * @param  \App\Models\Company\Reports\Fact\Balance  $report
     * @param  \App\Models\Company\Reports\Plan\Dds|null $ddsPlan
     * @return float
     */
    public function getTotalAssets(BalanceReportModel $report, DdsPlanModel|null $ddsPlan): float
    {
        return collect([
            $this->getLongTermAssets($report, $ddsPlan),
            $this->getCash($ddsPlan),
            $this->getStocks($report),
            $this->getOtherAssets($report)
        ])->sum();
    }

    /**
     * Возвращает уставный капитал
     *
     * @param  \App\Models\Company\Reports\Fact\Balance $report
     * @return float
     */
    public function getAuthorizedCapital(BalanceReportModel $report): float
    {
        return $report->getAuthorizedCapital();
    }

    /**
     * Возвращает нераспределенную прибыль
     *
     * @param  \App\Models\Company\Reports\Fact\Balance  $report
     * @param  \App\Models\Company\Reports\Plan\Opu|null $opuPlan
     * @return float
     */
    public function getRetainedEarnings(BalanceReportModel $report, OpuPlanModel|null $opuPlan): float
    {
        return $report->getRetainedEarnings() + ($opuPlan ? $opuPlan->getNetProfit() : 0);
    }

    /**
     * Возвращает кредиты и займы
     *
     * @param  \App\Models\Company\Reports\Fact\Balance $report
     * @return float
     */
    public function getCreditsAndLoans(BalanceReportModel $report): float
    {
        return $report->getCreditsAndLoans();
    }

    /**
     * Возвращает краткосрочные обязательства
     *
     * @param  \App\Models\Company\Reports\Fact\Balance  $report
     * @param  \App\Models\Company\Reports\Plan\Opu|null $opuPlan
     * @param  \App\Models\Company\Reports\Plan\Dds|null $ddsPlan
     * @return float
     */
    public function getShortTermLiabilities(
        BalanceReportModel $report,
        OpuPlanModel|null $opuPlan,
        DdsPlanModel|null $ddsPlan
    ): float
    {
        $totalAssets       = $this->getTotalAssets($report, $ddsPlan);
        $authorizedCapital = $this->getAuthorizedCapital($report);
        $retainedEarnings  = $this->getRetainedEarnings($report, $opuPlan);
        $creditsAndLoans   = $this->getCreditsAndLoans($report);
        $otherLiabilities  = $this->getOtherLiabilities($report);

        return $totalAssets - $authorizedCapital - $retainedEarnings - $creditsAndLoans - $otherLiabilities;
    }

    /**
     * Возвращает прочие обязательства
     *
     * @param  \App\Models\Company\Reports\Fact\Balance $report
     * @return float
     */
    public function getOtherLiabilities(BalanceReportModel $report): float
    {
        return $report->getOtherLiabilities();
    }

    /**
     * Возвращает итоговые пассивы
     *
     * @param  \App\Models\Company\Reports\Fact\Balance  $report
     * @param  \App\Models\Company\Reports\Plan\Opu|null $opuPlan
     * @param  \App\Models\Company\Reports\Plan\Dds|null $ddsPlan
     * @return float
     */
    public function getTotalLiabilities(
        BalanceReportModel $report,
        OpuPlanModel|null $opuPlan,
        DdsPlanModel|null $ddsPlan
    ): float
    {
        return collect([
            $this->getAuthorizedCapital($report),
            $this->getRetainedEarnings($report, $opuPlan),
            $this->getCreditsAndLoans($report),
            $this->getShortTermLiabilities($report, $opuPlan, $ddsPlan),
            $this->getOtherLiabilities($report)
        ])->sum();
    }
}
