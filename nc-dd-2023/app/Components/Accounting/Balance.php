<?php declare(strict_types=1);

namespace App\Components\Accounting;

use App\Models\Accounting;
use App\Models\Company\Balance as BalanceModel;
use App\Models\Company\Dds;
use App\Models\Company\Opu;

class Balance extends Base
{
    /**
     * Обновляет фактические показатели Баланса
     *
     * @param  \App\Models\Accounting      $accounting
     * @param  \App\Models\Company\Opu     $opu
     * @param  \App\Models\Company\Dds     $dds
     * @return \App\Models\Company\Balance
     * @throws \Exception
     */
    public function update(Accounting $accounting, Opu $opu, Dds $dds): BalanceModel
    {
        $fact = $this->getBalanceFact($accounting);

        // (Активы) Краткосрочные Активы
        $fact->setByCode(BalanceModel::NC_10, $dds->getByCode(Dds::NC_160));
        $fact->setByCode(BalanceModel::NC_100, $fact->getShortTermAssets());

        // (Пассивы) Краткосрочные обязательства (до года)
        $fact->setByCode(BalanceModel::NC_222, $dds->getByCode(Dds::NC_100)); // ДДС 101 (должно быть)
        $fact->setByCode(BalanceModel::NC_300, $fact->getShortTermLiabilities());

        // (Пассивы) Долгосрочные обязательства (более года)
        $fact->setByCode(BalanceModel::NC_321, $fact->getByCode(BalanceModel::NC_321)); // заменить
        $fact->setByCode(BalanceModel::NC_400, $fact->getByCode(BalanceModel::NC_400)); // заменить

        // Уставный капитал
        $fact->setByCode(BalanceModel::NC_410, $fact->getByCode(BalanceModel::NC_410)); // заменить

        // Нераспределенная прибыль (накопленная)
        $fact->setByCode(BalanceModel::NC_414, $fact->getByCode(BalanceModel::NC_414)); // заменить

        // Капитал и резервы
        $fact->setByCode(BalanceModel::NC_500, $fact->getCapitalAndReserves());

        if (!$fact->save()) {
            throw new \Exception('Не удалось обновить факт ДДС');
        }

        return $fact->refresh();
    }
}