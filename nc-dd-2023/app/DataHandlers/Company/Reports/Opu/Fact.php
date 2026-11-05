<?php declare(strict_types=1);

namespace App\DataHandlers\Company\Reports\Opu;

use App\Models\Company\Opu as OpuModel;
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
            /** @var \App\Models\Company\Opu $item */

            return [
                'companyUuid'                  => $uuid,
                'period'                       => $item->getPeriod(),
                'income'                       => $this->getIncome($item),
                'costPrice'                    => $this->getCostPrice($item),
                'generalAndAdminExpenses'      => $this->getGeneralAndAdminExpenses($item),
                'profitBeforeTaxesAndPercents' => $this->getProfitBeforeTaxesAndPercents($item),
                'financialIncomeOrExpenses'    => $this->getFinancialIncomeOrExpenses($item),
                'otherIncomeOrExpenses'        => $this->getOtherIncomeOrExpenses($item),
                'exchangeDifference'           => $this->getExchangeDifference(),
                'profitBeforeTaxes'            => $this->getProfitBeforeTaxes($item),
                'incomeTax'                    => $this->getIncomeTax($item),
                'netProfit'                    => $this->getNetProfit($item),
            ];
        })->toArray();
    }

    /**
     * Возвращает выручку от реализации продукции
     *
     * @param  \App\Models\Company\Opu $opu
     * @return float
     */
    public function getIncome(OpuModel $opu): float
    {
        return $opu->getByCode(OpuModel::NC_10);
    }

    /**
     * Возвращает себестоимость
     *
     * @param  \App\Models\Company\Opu $opu
     * @return float
     */
    public function getCostPrice(OpuModel $opu): float
    {
        return $opu->getByCode(OpuModel::NC_11);
    }

    /**
     * Возвращает ОиАР
     *
     * @param  \App\Models\Company\Opu $opu
     * @return float
     */
    public function getGeneralAndAdminExpenses(OpuModel $opu): float
    {
        return $opu->getByCode(OpuModel::NC_13) + $opu->getByCode(OpuModel::NC_14);
    }

    /**
     * Возвращает прибыль до налогов и процентов
     *
     * @param  \App\Models\Company\Opu $opu
     * @return float
     */
    public function getProfitBeforeTaxesAndPercents(OpuModel $opu): float
    {
        return $this->getIncome($opu) + $this->getCostPrice($opu) + $this->getGeneralAndAdminExpenses($opu);
    }

    /**
     * Возвращает финансовые доходы/расходы
     *
     * @param  \App\Models\Company\Opu $opu
     * @return float
     */
    public function getFinancialIncomeOrExpenses(OpuModel $opu): float
    {
        return $opu->getByCode(OpuModel::NC_21) + $opu->getByCode(OpuModel::NC_22);
    }

    /**
     * Возвращает прочие доходы/расходы
     *
     * @param  \App\Models\Company\Opu $opu
     * @return float
     */
    public function getOtherIncomeOrExpenses(OpuModel $opu): float
    {
        return $opu->getByCode(OpuModel::NC_24) + $opu->getByCode(OpuModel::NC_25);
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
     * @param  \App\Models\Company\Opu $opu
     * @return float
     */
    public function getProfitBeforeTaxes(OpuModel $opu): float
    {
        return collect([
            $this->getProfitBeforeTaxesAndPercents($opu),
            $this->getFinancialIncomeOrExpenses($opu),
            $this->getOtherIncomeOrExpenses($opu),
            $this->getExchangeDifference()
        ])->sum();
    }

    /**
     * Возвращает налог на прибыль
     *
     * @param  \App\Models\Company\Opu $opu
     * @return float
     */
    public function getIncomeTax(OpuModel $opu): float
    {
        return $opu->getByCode(OpuModel::NC_101);
    }

    /**
     * Возвращает чистую прибыль
     *
     * @param  \App\Models\Company\Opu $opu
     * @return float
     */
    public function getNetProfit(OpuModel $opu): float
    {
        return $this->getProfitBeforeTaxes($opu) + $this->getIncomeTax($opu);
    }
}
