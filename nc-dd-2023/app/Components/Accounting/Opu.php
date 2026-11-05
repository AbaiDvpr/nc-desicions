<?php declare(strict_types = 1);
namespace App\Components\Accounting;

use App\Models\Accounting;
use App\Models\Company\Opu as OpuModel;

/**
 * Работы с обновлением фактических показателей опу на основе ручного ввода
 */
class Opu extends Base
{
    /**
     * Обновляет фактические показатели согласно ручному вводу
     *
     * @param  \App\Models\Accounting  $accounting
     * @return \App\Models\Company\Opu
     * @throws \Exception
     */
    public function update(Accounting $accounting): OpuModel
    {
        $fact = $this->getOpuFact($accounting);

        $fact->setByCode(OpuModel::NC_10, $this->getRevenue($fact, $accounting));
        $fact->setByCode(OpuModel::NC_11, $this->getCostPrice($fact, $accounting));
        $fact->setByCode(OpuModel::NC_13, $this->getImplementationCosts($fact, $accounting));
        $fact->setByCode(OpuModel::NC_14, $this->getAdministrativeExpenses($fact, $accounting));
        $fact->setByCode(OpuModel::NC_24, $this->getOtherIncome($fact, $accounting));
        $fact->setByCode(OpuModel::NC_25, $this->getOtherOutcome($fact, $accounting));
        $fact->setByCode(OpuModel::NC_300, $this->getNetIncomeOrLoss($fact));

        if (!$fact->save()) {
            throw new \Exception('Не удалось обновить факт ОПУ');
        }

        return $fact->refresh();
    }

    /**
     * Рассчитывает берет текущую выручки и прибавляет сумму из ручного ввода
     *
     * @param  \App\Models\Company\Opu $fact
     * @param  \App\Models\Accounting  $accounting
     * @return float
     */
    protected function getRevenue(OpuModel $fact, Accounting $accounting): float
    {
        $factRevenue = $fact->getByCode(OpuModel::NC_10);

        if ($accounting->getPresenter()->isIncome()) {
            return $factRevenue + $accounting->getPrice();
        }

        return $factRevenue;
    }

    /**
     * Рассчитывает себестоимость для фактического показателя за месяц
     *
     * @param  \App\Models\Company\Opu $fact
     * @param  \App\Models\Accounting  $accounting
     * @return float
     */
    protected function getCostPrice(OpuModel $fact, Accounting $accounting): float
    {
        $factCostPrice = $fact->getByCode(OpuModel::NC_11);

        // Для добавления статьи затрат в себестоимость учитываем,
        // только если статья является "производственными расходами"
        if ($accounting->getPresenter()->isOutcomeProductionCost()) {
            // Так как это расходная часть, то делаем число отрицательным
            $price = $accounting->getPrice() < 0 ? $accounting->getPrice() : -1 * $accounting->getPrice();

            return $factCostPrice + $price;
        }

        return $factCostPrice;
    }

    /**
     * Считает сумму для коммерческих расходов
     *
     * @param  \App\Models\Company\Opu $fact
     * @param  \App\Models\Accounting  $accounting
     * @return float
     */
    protected function getImplementationCosts(OpuModel $fact, Accounting $accounting): float
    {
        $implementationCost = $fact->getByCode(OpuModel::NC_13);

        if ($accounting->getPresenter()->isOutcomeImplementationCost()) {
            // Так как это расходная часть, то делаем число отрицательным
            $price = $accounting->getPrice() < 0 ? $accounting->getPrice() : -1 * $accounting->getPrice();

            return $implementationCost + $price;
        }

        return $implementationCost;
    }

    /**
     * Считает сумму для административных расходов
     *
     * @param  \App\Models\Company\Opu $fact
     * @param  \App\Models\Accounting  $accounting
     * @return float
     */
    protected function getAdministrativeExpenses(OpuModel $fact, Accounting $accounting): float
    {
        $administrative = $fact->getByCode(OpuModel::NC_14);

        if ($accounting->getPresenter()->isOutcomeAdministrativeExpenses()) {
            // Так как это расходная часть, то делаем число отрицательным
            $price = $accounting->getPrice() < 0 ? $accounting->getPrice() : -1 * $accounting->getPrice();

            return $administrative + $price;
        }

        return $administrative;
    }

    /**
     * Считает сумму для "прочие доходы"
     *
     * @param  \App\Models\Company\Opu $fact
     * @param  \App\Models\Accounting  $accounting
     * @return float
     */
    protected function getOtherIncome(OpuModel $fact, Accounting $accounting): float
    {
        $administrative = $fact->getByCode(OpuModel::NC_24);

        if ($accounting->getPresenter()->isIncomeOther()) {
            return $administrative + $accounting->getPrice();
        }

        return $administrative;
    }

    /**
     * Считает сумму для "прочие расходы"
     *
     * @param  \App\Models\Company\Opu $fact
     * @param  \App\Models\Accounting  $accounting
     * @return float
     */
    protected function getOtherOutcome(OpuModel $fact, Accounting $accounting): float
    {
        $administrative = $fact->getByCode(OpuModel::NC_25);

        // Не уверен на сколько правильно садить "налоги и сборы" полностью в "прочие расходы" (пока так оставляю)
        if ($accounting->getPresenter()->isOutcomeOther() || $accounting->getPresenter()->isOutcomeTaxesFees()) {
            return $administrative - $accounting->getPrice();
        }

        return $administrative;
    }

    /**
     * Высчитывает "Чистую прибыль (убыток)"
     *
     * @param  \App\Models\Company\Opu $fact
     * @return float
     */
    protected function getNetIncomeOrLoss(OpuModel $fact): float
    {
        // TODO: должны вычитаться еще "налоги и сборы" но нифига не понятно
        return $fact->getOperatingProfit() +
               $fact->getByCode(OpuModel::NC_24) -
               $fact->getByCode(OpuModel::NC_25);
    }
}
