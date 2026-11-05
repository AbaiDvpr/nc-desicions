<?php declare(strict_types = 1);
namespace App\Components\Accounting;

use App\DI;
use App\Models\Accounting;
use App\Models\Company\Dds as DdsModel;
use Illuminate\Support\Carbon;

class Dds extends Base
{
    /**
     * Обновляет фактические показатели согласно ручному вводу
     *
     * @param  \App\Models\Accounting  $accounting
     * @return \App\Models\Company\Dds
     * @throws \Exception
     */
    public function update(Accounting $accounting): DdsModel
    {
        $fact = $this->getDdsFact($accounting);

        // Собираем данные по поступлениям денежных средств 010
        $fact->setByCode(DdsModel::NC_10, $this->getRevenue($fact, $accounting));
        $fact->setByCode(DdsModel::NC_11, $this->getBasic($fact, $accounting));
        $fact->setByCode(DdsModel::NC_12, $this->getOtherRevenue($fact, $accounting));
        $fact->setByCode(DdsModel::NC_13, $this->getAdvances($fact, $accounting));
        $fact->setByCode(DdsModel::NC_14, $this->getContracts($fact, $accounting));
        $fact->setByCode(DdsModel::NC_15, $this->getRemunerationReceived($fact, $accounting));
        $fact->setByCode(DdsModel::NC_16, $this->getOther($fact, $accounting));

        // Собираем данные по выплатам от операционной деятельности
        $fact->setByCode(DdsModel::NC_20, $this->getAdministrativePayments($fact, $accounting));
        $fact->setByCode(DdsModel::NC_21, $this->getProductionPayments($fact, $accounting));
        $fact->setByCode(DdsModel::NC_30, $fact->getCashFlow());

        // Собираем данные по инвестиционной деятельности
        $fact->setByCode(DdsModel::NC_40, $this->getIncomeInvestmentActivities($fact, $accounting));
        $fact->setByCode(DdsModel::NC_60, $this->getOutcomeInvestmentActivities($fact, $accounting));
        $fact->setByCode(DdsModel::NC_80, $fact->getCashFlowFromInvestingActivities());

        // Собираем данные по финансовой деятельности
        $fact->setByCode(DdsModel::NC_90, $this->getIncomeFinancialActivities($fact, $accounting));
        $fact->setByCode(DdsModel::NC_100, $this->getOutcomeFinancialActivities($fact, $accounting));
        $fact->setByCode(DdsModel::NC_110, $fact->getCashFlowFromFinancingActivities());
        $fact->setByCode(DdsModel::NC_140, $fact->getTotalCashFlow());

        // Суммы на начало и конец периода
        $fact->setByCode(DdsModel::NC_150, $this->getCashAtBeginningPeriod($fact));
        $fact->setByCode(DdsModel::NC_160, $this->getCashAtEndPeriod($fact));

        if (!$fact->save()) {
            throw new \Exception('Не удалось обновить факт ДДС');
        }

        return $fact->refresh();
    }

    /**
     * Возвращает общую сумму поступлений (010)
     *
     * @param  \App\Models\Company\Dds $fact
     * @param  \App\Models\Accounting  $accounting
     * @return float
     */
    protected function getRevenue(DdsModel $fact, Accounting $accounting): float
    {
        $current = $fact->getByCode(DdsModel::NC_10);

        if ($accounting->getPresenter()->isIncomeOperating()) {
            return $current + $accounting->getPrice();
        }

        return $current;
    }

    /**
     * Возвращает сумму реализации товаров и услуг (011)
     *
     * @param  \App\Models\Company\Dds $fact
     * @param  \App\Models\Accounting  $accounting
     * @return float
     */
    protected function getBasic(DdsModel $fact, Accounting $accounting): float
    {
        $current = $fact->getByCode(DdsModel::NC_11);

        if ($accounting->getPresenter()->isIncomeOperating() && $accounting->getPresenter()->isIncomeBasic()) {
            return $current + $accounting->getPrice();
        }

        return $current;
    }

    /**
     * Возвращает сумму прочей выручки (012)
     *
     * @param  \App\Models\Company\Dds $fact
     * @param  \App\Models\Accounting  $accounting
     * @return float
     */
    protected function getOtherRevenue(DdsModel $fact, Accounting $accounting): float
    {
        $current = $fact->getByCode(DdsModel::NC_12);

        if ($accounting->getPresenter()->isIncomeOperating() && $accounting->getPresenter()->isIncomeOtherRevenue()) {
            return $current + $accounting->getPrice();
        }

        return $current;
    }

    /**
     * Возвращает сумму "авансы, полученные от покупателей, заказчиков" (013)
     *
     * @param  \App\Models\Company\Dds $fact
     * @param  \App\Models\Accounting  $accounting
     * @return float
     */
    protected function getAdvances(DdsModel $fact, Accounting $accounting): float
    {
        $current = $fact->getByCode(DdsModel::NC_13);

        if ($accounting->getPresenter()->isIncomeOperating() && $accounting->getPresenter()->isIncomeAdvances()) {
            return $current + $accounting->getPrice();
        }

        return $current;
    }

    /**
     * Возвращает сумму "поступления по договорам страхования" (014)
     *
     * @param  \App\Models\Company\Dds $fact
     * @param  \App\Models\Accounting  $accounting
     * @return float
     */
    protected function getContracts(DdsModel $fact, Accounting $accounting): float
    {
        $current = $fact->getByCode(DdsModel::NC_14);

        if ($accounting->getPresenter()->isIncomeOperating() && $accounting->getPresenter()->isIncomeContracts()) {
            return $current + $accounting->getPrice();
        }

        return $current;
    }

    /**
     * Возвращает сумму "полученные вознаграждения" (015)
     *
     * @param  \App\Models\Company\Dds $fact
     * @param  \App\Models\Accounting  $accounting
     * @return float
     */
    protected function getRemunerationReceived(DdsModel $fact, Accounting $accounting): float
    {
        $current   = $fact->getByCode(DdsModel::NC_15);
        $presenter = $accounting->getPresenter();

        if ($presenter->isIncomeOperating() && $presenter->isIncomeRemunerationReceived()) {
            return $current + $accounting->getPrice();
        }

        return $current;
    }

    /**
     * Возвращает сумму "прочие поступления" (016)
     *
     * @param  \App\Models\Company\Dds $fact
     * @param  \App\Models\Accounting  $accounting
     * @return float
     */
    protected function getOther(DdsModel $fact, Accounting $accounting): float
    {
        $current   = $fact->getByCode(DdsModel::NC_16);
        $presenter = $accounting->getPresenter();

        if ($presenter->isIncomeOperating() && $presenter->isIncomeOther()) {
            return $current + $accounting->getPrice();
        }

        return $current;
    }

    /**
     * Возвращает сумму "Выплаты по операционной деятельности" (020)
     *
     * @param  \App\Models\Company\Dds $fact
     * @param  \App\Models\Accounting  $accounting
     * @return float
     */
    protected function getAdministrativePayments(DdsModel $fact, Accounting $accounting): float
    {
        $current   = $fact->getByCode(DdsModel::NC_20);
        $presenter = $accounting->getPresenter();

        if (
            $presenter->isOutcomeOperating() && (
                $presenter->isOutcomeAdministrativeExpenses() ||
                $presenter->isOutcomeProductionCost() ||
                $presenter->isOutcomeImplementationCost()
            )
        ) {
            return $current + $accounting->getPrice();
        }

        return $current;
    }

    /**
     * Возвращает сумму "Производственные выплаты" (021)
     *
     * @param  \App\Models\Company\Dds $fact
     * @param  \App\Models\Accounting  $accounting
     * @return float
     */
    protected function getProductionPayments(DdsModel $fact, Accounting $accounting): float
    {
        $current   = $fact->getByCode(DdsModel::NC_21);
        $presenter = $accounting->getPresenter();

        if ($presenter->isOutcomeOperating() && $presenter->isOutcomeProductionCost()) {
            return $current + $accounting->getPrice();
        }

        return $current;
    }

    /**
     * Возвращает сумму "Поступления от инвестиционной деятельности" (040)
     *
     * @param  \App\Models\Company\Dds $fact
     * @param  \App\Models\Accounting  $accounting
     * @return float
     */
    protected function getIncomeInvestmentActivities(DdsModel $fact, Accounting $accounting): float
    {
        $current = $fact->getByCode(DdsModel::NC_40);

        if ($accounting->getPresenter()->isIncomeInvestment()) {
            $current = $current + $accounting->getPrice();
        }

        return $current;
    }

    /**
     * Возвращает сумму "Выплаты от инвестиционной деятельности" (060)
     *
     * @param  \App\Models\Company\Dds $fact
     * @param  \App\Models\Accounting  $accounting
     * @return float
     */
    protected function getOutcomeInvestmentActivities(DdsModel $fact, Accounting $accounting): float
    {
        $current = $fact->getByCode(DdsModel::NC_60);

        if ($accounting->getPresenter()->isOutcomeInvestment()) {
            $current = $current + $accounting->getPrice();
        }

        return $current;
    }

    /**
     * Возвращает сумму "Поступления от финансовой деятельности" (090)
     *
     * @param  \App\Models\Company\Dds $fact
     * @param  \App\Models\Accounting  $accounting
     * @return float
     */
    protected function getIncomeFinancialActivities(DdsModel $fact, Accounting $accounting): float
    {
        $current = $fact->getByCode(DdsModel::NC_90);

        if ($accounting->getPresenter()->isIncomeFinancial()) {
            $current = $current + $accounting->getPrice();
        }

        return $current;
    }

    /**
     * Возвращает сумму "Выплаты по финансовой деятельности" (100)
     *
     * @param  \App\Models\Company\Dds $fact
     * @param  \App\Models\Accounting  $accounting
     * @return float
     */
    protected function getOutcomeFinancialActivities(DdsModel $fact, Accounting $accounting): float
    {
        $current = $fact->getByCode(DdsModel::NC_100);

        if ($accounting->getPresenter()->isOutcomeFinancial()) {
            $current = $current + $accounting->getPrice();
        }

        return $current;
    }

    /**
     * Возвращает сумму "Денежные средства в начале периода" (150)
     *
     * @param  \App\Models\Company\Dds $fact
     * @return float
     */
    public function getCashAtBeginningPeriod(DdsModel $fact): float
    {
        $prevMonth  = Carbon::parse($fact->getPeriod())->subMonth()->startOfMonth();
        $prevPeriod = DI::getRepository()->getCompany()->getDds()->getFactByPeriod($fact->getCompanyUuid(), $prevMonth);
        $current    = 0;

        if (!is_null($prevPeriod)) {
            $current = $prevPeriod->getByCode(DdsModel::NC_160);
        }

        return (float) $current;
    }

    /**
     * Возвращает сумму "Денежные средства в конце периода" (160)
     *
     * @param  \App\Models\Company\Dds $fact
     * @return float
     */
    public function getCashAtEndPeriod(DdsModel $fact): float
    {
        return $fact->getByCode(DdsModel::NC_140) + $fact->getByCode(DdsModel::NC_150);
    }
}