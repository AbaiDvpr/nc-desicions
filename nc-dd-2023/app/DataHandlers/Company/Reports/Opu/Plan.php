<?php declare(strict_types=1);

namespace App\DataHandlers\Company\Reports\Opu;

use App\Models\Company\Indicator;
use App\Models\Company\Reports\Fact\Opu as OpuReportModel;
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
            /** @var \App\Models\Company\Reports\Fact\Opu $report */

            return [
                'companyUuid'                  => $uuid,
                'indicatorId'                  => $indicator->getId(),
                'period'                       => $this->getPeriod($report),
                'income'                       => $this->getIncome($report, $indicator),
                'costPrice'                    => $this->getCostPrice($report, $indicator),
                'generalAndAdminExpenses'      => $this->getGeneralAndAdminExpenses($report),
                'profitBeforeTaxesAndPercents' => $this->getProfitBeforeTaxesAndPercents($report, $indicator),
                'financialIncomeOrExpenses'    => $this->getFinancialIncomeOrExpenses(),
                'otherIncomeOrExpenses'        => $this->getOtherIncomeOrExpenses($report, $indicator),
                'exchangeDifference'           => $this->getExchangeDifference(),
                'profitBeforeTaxes'            => $this->getProfitBeforeTaxes($report, $indicator),
                'incomeTax'                    => $this->getIncomeTax($report, $indicator),
                'netProfit'                    => $this->getNetProfit($report, $indicator),
            ];
        })->toArray();
    }

    /**
     * Возвращает период
     *
     * @param  \App\Models\Company\Reports\Fact\Opu $report
     * @return string
     */
    public function getPeriod(OpuReportModel $report): string
    {
        return Carbon::parse($report->getPeriod())->addYear()->format('Y-m-d');
    }

    /**
     * Возвращает выручку от реализации продукции
     *
     * @param  \App\Models\Company\Reports\Fact\Opu $report
     * @param  \App\Models\Company\Indicator        $indicator
     * @return float
     */
    public function getIncome(OpuReportModel $report, Indicator $indicator): float
    {
        return $report->getIncome() + ($report->getIncome() / 100 * $indicator->getRevenueGrowthRate());
    }

    /**
     * Возвращает себестоимость
     *
     * @param  \App\Models\Company\Reports\Fact\Opu $report
     * @param  \App\Models\Company\Indicator        $indicator
     * @return float
     */
    public function getCostPrice(OpuReportModel $report, Indicator $indicator): float
    {
        return -$this->getIncome($report, $indicator) / 100 * $indicator->getProportionVariableCosts();
    }

    /**
     * Возвращает ОиАР
     *
     * @param  \App\Models\Company\Reports\Fact\Opu $report
     * @return float
     */
    public function getGeneralAndAdminExpenses(OpuReportModel $report): float
    {
        return $report->getGeneralAndAdminExpenses();
    }

    /**
     * Возвращает прибыль до налогов и процентов
     *
     * @param  \App\Models\Company\Reports\Fact\Opu $report
     * @param  \App\Models\Company\Indicator        $indicator
     * @return float
     */
    public function getProfitBeforeTaxesAndPercents(OpuReportModel $report, Indicator $indicator): float
    {
        return collect([
            $this->getIncome($report, $indicator),
            $this->getCostPrice($report, $indicator),
            $this->getGeneralAndAdminExpenses($report)
        ])->sum();
    }

    /**
     * Возвращает финансовые доходы/расходы
     *
     * @return int
     */
    public function getFinancialIncomeOrExpenses(): int
    {
        return 0;
    }

    /**
     * Возвращает прочие доходы/расходы
     *
     * @param  \App\Models\Company\Reports\Fact\Opu $report
     * @param  \App\Models\Company\Indicator        $indicator
     * @return float
     */
    public function getOtherIncomeOrExpenses(OpuReportModel $report, Indicator $indicator): float
    {
        return collect([
            $report->getOtherIncomeOrExpenses(),
            $report->getOtherIncomeOrExpenses() / 100 * $indicator->getInflationIndex()
        ])->sum();
    }

    /**
     * Возвращает курсовую разницу
     *
     * @return int
     */
    public function getExchangeDifference(): int
    {
        return 0;
    }

    /**
     * Возвращает прибыль до налогов
     *
     * @param  \App\Models\Company\Reports\Fact\Opu $report
     * @param  \App\Models\Company\Indicator        $indicator
     * @return float
     */
    public function getProfitBeforeTaxes(OpuReportModel $report, Indicator $indicator): float
    {
        return collect([
            $this->getProfitBeforeTaxesAndPercents($report, $indicator),
            $this->getFinancialIncomeOrExpenses(),
            $this->getOtherIncomeOrExpenses($report, $indicator),
            $this->getExchangeDifference()
        ])->sum();
    }

    /**
     * Возвращает налог на прибыль
     *
     * @param  \App\Models\Company\Reports\Fact\Opu $report
     * @param  \App\Models\Company\Indicator        $indicator
     * @return float
     */
    public function getIncomeTax(OpuReportModel $report, Indicator $indicator): float
    {
        $profitBeforeTaxes = $this->getProfitBeforeTaxes($report, $indicator);

        return $profitBeforeTaxes > 0
            ? $profitBeforeTaxes + ($profitBeforeTaxes / 100 * $indicator->getIncomeTaxRate())
            : 0;
    }

    /**
     * Возвращает чистую прибыль
     *
     * @param  \App\Models\Company\Reports\Fact\Opu $report
     * @param  \App\Models\Company\Indicator        $indicator
     * @return float
     */
    public function getNetProfit(OpuReportModel $report, Indicator $indicator): float
    {
        return $this->getProfitBeforeTaxes($report, $indicator) + $this->getIncomeTax($report, $indicator);
    }
}
