<?php declare(strict_types=1);

namespace App\DataHandlers\Company\Reports\Dds;

use App\DI;
use App\Models\Company\Dds as DdsModel;
use App\Models\Company\Reports\Fact\Opu as OpuReportModel;
use App\Models\Company\Reports\Fact\Balance as BalanceReportModel;
use Illuminate\Database\Eloquent\Collection;

/**
 * Обработчик данных для факта
 */
class Fact
{
    /**
     * Производит подсчёт факта
     *
     * @param  string     $uuid
     * @param  Collection $data
     * @return array
     */
    public function calculate(string $uuid, Collection $data): array
    {
        return collect($data)->map(function($item) use ($uuid) {
            /** @var \App\Models\Company\Dds $item */

            $companyRepo   = DI::getRepository()->getCompany();
            $period        = $item->getPeriod();
            $opuReport     = $companyRepo->getOpuReport()->getOneByPeriod($uuid, $period);
            $balanceReport = $companyRepo->getBalanceReport()->getOneByPeriod($uuid, $period);

            return [
                'companyUuid'                              => $uuid,
                'period'                                   => $period,
                'netProfit'                                => $this->getNetProfit($opuReport),
                'increaseInNetWorkingCapital'              => $this->getIncreaseInNetWorkingCapital($item, $opuReport),
                'cashFlowsFromOperatingActivities'         => $this->getCashFlowsFromOperatingActivities($item),
                'investments'                              => $this->getInvestments(),
                'cashFlowsFromInvestingActivities'         => $this->getCashFlowsFromInvestingActivities($item),
                'receiptOrRepaymentOfFinancialLiabilities' => $this->getReceiptOrRepaymentOfFinancialLiabilities(),
                'issueOfShares'                            => $this->getIssueOfShares(),
                'cashFlowsFromFinancingActivities'         => $this->getCashFlowsFromFinancingActivities($item),
                'totalChangeInCash'                        => $this->getTotalChangeInCash($item),
                'impactOfExchangeRatesAgainstTenge'        => $this->getImpactOfExchangeRatesAgainstTenge($item),
                'cashAtBeginningPeriod'                    => $this->getCashAtBeginningPeriod($item, $balanceReport),
                'cashAtEndPeriod'                          => $this->getCashAtEndPeriod($balanceReport),
            ];
        })->toArray();
    }

    /**
     * Чистая прибыль
     *
     * @param  \App\Models\Company\Reports\Fact\Opu|null $opuReport
     * @return float
     */
    public function getNetProfit(OpuReportModel|null $opuReport): float
    {
        return $opuReport ? $opuReport->getNetProfit() : 0;
    }

    /**
     * Прирост чистого оборотного капитала
     *
     * @param  \App\Models\Company\Dds                   $dds
     * @param  \App\Models\Company\Reports\Fact\Opu|null $opuReport
     * @return float
     */
    public function getIncreaseInNetWorkingCapital(DdsModel $dds, OpuReportModel|null $opuReport): float
    {
        return $this->getCashFlowsFromOperatingActivities($dds) - $this->getNetProfit($opuReport);
    }

    /**
     * Денежные потоки от операционной деятельности
     *
     * @param  \App\Models\Company\Dds $dds
     * @return float
     */
    public function getCashFlowsFromOperatingActivities(DdsModel $dds): float
    {
        return $dds->getByCode(DdsModel::NC_30);
    }

    /**
     * Инвестиции
     *
     * @return int
     */
    public function getInvestments(): int
    {
        return 0;
    }

    /**
     * Денежные потоки от инвестиционной деятельности
     *
     * @param  \App\Models\Company\Dds $dds
     * @return float
     */
    public function getCashFlowsFromInvestingActivities(DdsModel $dds): float
    {
        return $dds->getByCode(DdsModel::NC_80);
    }

    /**
     * Поступление/погашение финансовых обязательств
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
     * @param  \App\Models\Company\Dds $dds
     * @return float
     */
    public function getCashFlowsFromFinancingActivities(DdsModel $dds): float
    {
        return $dds->getByCode(DdsModel::NC_110);
    }

    /**
     * Итого изменение денежных средств
     *
     * @param  \App\Models\Company\Dds $dds
     * @return float
     */
    public function getTotalChangeInCash(DdsModel $dds): float
    {
        return collect([
            $this->getCashFlowsFromOperatingActivities($dds),
            $this->getCashFlowsFromInvestingActivities($dds),
            $this->getCashFlowsFromFinancingActivities($dds)
        ])->sum();
    }

    /**
     * Влияние обменных курсов валют к тенге
     *
     * @param  \App\Models\Company\Dds $dds
     * @return float
     */
    public function getImpactOfExchangeRatesAgainstTenge(DdsModel $dds): float
    {
        return $dds->getByCode(DdsModel::NC_120);
    }

    /**
     * Денежные средства на начало периода
     *
     * @param  \App\Models\Company\Dds                       $dds
     * @param  \App\Models\Company\Reports\Fact\Balance|null $balanceReport
     * @return float
     */
    public function getCashAtBeginningPeriod(DdsModel $dds, BalanceReportModel|null $balanceReport): float
    {
        return $this->getCashAtEndPeriod($balanceReport) - $this->getTotalChangeInCash($dds) - $this->getImpactOfExchangeRatesAgainstTenge($dds);
    }

    /**
     * Денежные средства на конец периода
     *
     * @param  \App\Models\Company\Reports\Fact\Balance|null $balanceReport
     * @return float
     */
    public function getCashAtEndPeriod(BalanceReportModel|null $balanceReport): float
    {
        return $balanceReport ? $balanceReport->getCash() : 0;
    }
}
