<?php declare(strict_types=1);

namespace App\DataHandlers\Company\Reports\Dds;

use App\DI;
use App\Models\Company\Indicator;
use App\Models\Company\Reports\Fact\Dds as DdsReportModel;
use App\Models\Company\Reports\Plan\Opu as OpuPlanModel;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;

class Plan
{
    /**
     * Производит подсчёт плана
     *
     * @param  string                                   $uuid
     * @param  \App\Models\Company\Indicator            $indicator
     * @param  \Illuminate\Database\Eloquent\Collection $reports
     * @return array
     */
    public function calculate(string $uuid, Indicator $indicator, Collection $reports): array
    {
        return collect($reports)->map(function($report) use ($uuid, $indicator) {
            /** @var \App\Models\Company\Reports\Fact\Dds $report */

            $period  = $this->getPeriod($report);
            $opuPlan = DI::getRepository()->getCompany()->getOpuReport()->getPlan()->getOneByPeriod($uuid, $period);

            return [
                'companyUuid'                              => $uuid,
                'indicatorId'                              => $indicator->getId(),
                'period'                                   => $period,
                'netProfit'                                => $this->getNetProfit($opuPlan),
                'increaseInNetWorkingCapital'              => $this->getIncreaseInNetWorkingCapital($report, $indicator),
                'cashFlowsFromOperatingActivities'         => $this->getCashFlowsFromOperatingActivities($opuPlan, $report, $indicator),
                'investments'                              => $this->getInvestments($indicator),
                'cashFlowsFromInvestingActivities'         => $this->getCashFlowsFromInvestingActivities($indicator),
                'receiptOrRepaymentOfFinancialLiabilities' => $this->getReceiptOrRepaymentOfFinancialLiabilities(),
                // 'issueOfShares'                            => $this->getIssueOfShares(),
                'cashFlowsFromFinancingActivities'         => $this->getCashFlowsFromFinancingActivities(),
                'totalChangeInCash'                        => $this->getTotalChangeInCash($opuPlan, $report, $indicator),
                'impactOfExchangeRatesAgainstTenge'        => $this->getImpactOfExchangeRatesAgainstTenge(),
                'cashAtBeginningPeriod'                    => $this->getCashAtBeginningPeriod($report),
                'cashAtEndPeriod'                          => $this->getCashAtEndPeriod($opuPlan, $report, $indicator),
            ];
        })->toArray();
    }

    /**
     * Возвращает период
     *
     * @param  \App\Models\Company\Reports\Fact\Dds $report
     * @return string
     */
    public function getPeriod(DdsReportModel $report): string
    {
        return Carbon::parse($report->getPeriod())->addYear()->format('Y-m-d');
    }

    /**
     * Чистая прибыль
     *
     * @param  \App\Models\Company\Reports\Plan\Opu|null $opuPlan
     * @return float
     */
    public function getNetProfit(OpuPlanModel|null $opuPlan): float
    {
        return $opuPlan ? $opuPlan->getNetProfit() : 0;
    }

    /**
     * Прирост чистого оборотного капитала
     *
     * @param  \App\Models\Company\Reports\Fact\Dds $report
     * @param  \App\Models\Company\Indicator        $indicator
     * @return float
     */
    public function getIncreaseInNetWorkingCapital(DdsReportModel $report, Indicator $indicator): float
    {
        return collect([
            $report->getIncreaseInNetWorkingCapital(),
            $report->getIncreaseInNetWorkingCapital() / 100 * $indicator->getRevenueGrowthRate()
        ])->sum();
    }

    /**
     * Денежные потоки от операционной деятельности
     *
     * @param  \App\Models\Company\Reports\Plan\Opu|null $opuPlan
     * @param  \App\Models\Company\Reports\Fact\Dds      $report
     * @param  \App\Models\Company\Indicator             $indicator
     * @return float
     */
    public function getCashFlowsFromOperatingActivities(
        OpuPlanModel|null $opuPlan,
        DdsReportModel $report,
        Indicator $indicator
    ): float
    {
        return $this->getNetProfit($opuPlan) + $this->getIncreaseInNetWorkingCapital($report, $indicator);
    }

    /**
     * Инвестиции
     *
     * @param  \App\Models\Company\Indicator $indicator
     * @return float
     */
    public function getInvestments(Indicator $indicator): float
    {
        return -$indicator->getAcquisitionAssets();
    }

    /**
     * Денежные потоки от инвестиционной деятельности
     *
     * @param  \App\Models\Company\Indicator $indicator
     * @return float
     */
    public function getCashFlowsFromInvestingActivities(Indicator $indicator): float
    {
        return $this->getInvestments($indicator);
    }

    /**
     * Поступление/погашение финансовых обязательств
     *
     * TODO: Подумать, идет обращение в Баланс + вводные
     *
     * @return int
     */
    public function getReceiptOrRepaymentOfFinancialLiabilities(): int
    {
        return 0;
    }

    /**
     * Эмиссия акций
     *
     * @return int
     */
    public function getIssueOfShares(): int
    {
        return 0;
    }

    /**
     * Денежные потоки от финансовой деятельности
     *
     * @return int
     */
    public function getCashFlowsFromFinancingActivities(): int
    {
        return $this->getReceiptOrRepaymentOfFinancialLiabilities();
    }

    /**
     * Итого изменение денежных средств
     *
     * @param  \App\Models\Company\Reports\Plan\Opu|null $opuPlan
     * @param  \App\Models\Company\Reports\Fact\Dds      $report
     * @param  \App\Models\Company\Indicator             $indicator
     * @return float
     */
    public function getTotalChangeInCash(
        OpuPlanModel|null $opuPlan,
        DdsReportModel $report,
        Indicator $indicator
    ): float
    {
        return collect([
            $this->getCashFlowsFromOperatingActivities($opuPlan, $report, $indicator),
            $this->getCashFlowsFromInvestingActivities($indicator),
            $this->getCashFlowsFromFinancingActivities()
        ])->sum();
    }

    /**
     * Влияние обменных курсов валют к тенге
     *
     * @return int
     */
    public function getImpactOfExchangeRatesAgainstTenge(): int
    {
        return 0;
    }

    /**
     * Денежные средства на начало периода
     *
     * @param  \App\Models\Company\Reports\Fact\Dds $report
     * @return float
     */
    public function getCashAtBeginningPeriod(DdsReportModel $report): float
    {
        return $report->getCashAtEndPeriod();
    }

    /**
     * Денежные средства на конец периода
     *
     * @param  \App\Models\Company\Reports\Plan\Opu|null $opuPlan
     * @param  \App\Models\Company\Reports\Fact\Dds      $report
     * @param  \App\Models\Company\Indicator             $indicator
     * @return float
     */
    public function getCashAtEndPeriod(
        OpuPlanModel|null $opuPlan,
        DdsReportModel $report,
        Indicator $indicator
    ): float
    {
        return $this->getCashAtBeginningPeriod($report) + $this->getTotalChangeInCash($opuPlan, $report, $indicator);
    }
}
