<?php declare(strict_types=1);

namespace App\Components\Accounting;

use App\DI;
use App\Models\Accounting;
use App\Models\Company\Opu as OpuModel;
use App\Models\Company\Dds as DdsModel;
use App\Models\Company\Balance as BalanceModel;

class Base
{
    /**
     * Возвращает модель факта отчета ОПУ
     *
     * @param  \App\Models\Accounting $accounting
     * @return \App\Models\Company\Opu
     */
    public function getOpuFact(Accounting $accounting): OpuModel
    {
        $fact = DI::getRepository()->getCompany()->getOpu()->getFactByPeriod(
            $accounting->getCompanyUuid(),
            $accounting->getReceiptDate()
        );

        if (is_null($fact)) {
            $fact = new OpuModel();

            // Если не было найдено факта за определенный период,
            // то используем дату поступления денег как фактическую дату
            $fact->setPeriod($accounting->getReceiptDate()->startOfMonth()->format('Y-m-d'));
            $fact->setCompanyUuid($accounting->getCompanyUuid());
            $fact->setFillTypeHandheld();
        }

        return $fact;
    }

    /**
     * Возвращает модель факта отчета ОПУ
     *
     * @param  \App\Models\Accounting $accounting
     * @return \App\Models\Company\Dds
     */
    public function getDdsFact(Accounting $accounting): DdsModel
    {
        $fact = DI::getRepository()->getCompany()->getDds()->getFactByPeriod(
            $accounting->getCompanyUuid(),
            $accounting->getReceiptDate()
        );

        if (is_null($fact)) {
            $fact = new DdsModel();

            // Если не было найдено факта за определенный период,
            // то используем дату поступления денег как фактическую дату
            $fact->setPeriod($accounting->getReceiptDate()->startOfMonth()->format('Y-m-d'));
            $fact->setCompanyUuid($accounting->getCompanyUuid());
            $fact->setFillTypeHandheld();
        }

        return $fact;
    }

    /**
     * Возвращает модель факта отчета оп Балансу
     *
     * @param  \App\Models\Accounting      $accounting
     * @return \App\Models\Company\Balance
     */
    public function getBalanceFact(Accounting $accounting): BalanceModel
    {
        $fact = DI::getRepository()->getCompany()->getBalance()->getFactByPeriod(
            $accounting->getCompanyUuid(),
            $accounting->getReceiptDate()
        );

        if (is_null($fact)) {
            $fact = new BalanceModel();

            // Если не было найдено факта за определенный период,
            // то используем дату поступления денег как фактическую дату
            $fact->setPeriod($accounting->getReceiptDate()->startOfMonth()->format('Y-m-d'));
            $fact->setCompanyUuid($accounting->getCompanyUuid());
            $fact->setFillTypeHandheld();
        }

        return $fact;
    }
}