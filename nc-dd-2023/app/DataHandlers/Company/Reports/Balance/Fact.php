<?php declare(strict_types=1);

namespace App\DataHandlers\Company\Reports\Balance;

use App\Models\Company\Balance as BalanceModel;
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
            /** @var \App\Models\Company\Balance $item */

            return [
                'companyUuid'          => $uuid,
                'period'               => $item->getPeriod(),
                'longTermAssets'       => $this->getLongTermAssets($item),
                'netWorkingCapital'    => $this->getNetWorkingCapital(),
                'cash'                 => $this->getCash($item),
                'stocks'               => $this->getStocks($item),
                'otherAssets'          => $this->getOtherAssets($item),
                'totalAssets'          => $this->getTotalAssets($item),
                'authorizedCapital'    => $this->getAuthorizedCapital($item),
                'retainedEarnings'     => $this->getRetainedEarnings($item),
                'creditsAndLoans'      => $this->getCreditsAndLoans($item),
                'shortTermLiabilities' => $this->getShortTermLiabilities($item),
                'otherLiabilities'     => $this->getOtherLiabilities($item),
                'totalLiabilities'     => $this->getTotalLiabilities($item),
            ];
        })->toArray();
    }

    /**
     * Возвращает долгосрочные активы
     *
     * @param  \App\Models\Company\Balance $balance
     * @return float
     */
    public function getLongTermAssets(BalanceModel $balance): float
    {
        return $balance->getByCode(BalanceModel::NC_200);
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
     * @param  \App\Models\Company\Balance $balance
     * @return float
     */
    public function getCash(BalanceModel $balance): float
    {
        return $balance->getByCode(BalanceModel::NC_10);
    }

    /**
     * Возвращает запасы
     *
     * @param  \App\Models\Company\Balance $balance
     * @return float
     */
    public function getStocks(BalanceModel $balance): float
    {
        return $balance->getByCode(BalanceModel::NC_20);
    }

    /**
     * Возвращает прочие активы
     *
     * @param  \App\Models\Company\Balance $balance
     * @return float
     */
    public function getOtherAssets(BalanceModel $balance): float
    {
        $operandOne = collect([
            $balance->getByCode(BalanceModel::NC_100),
            $balance->getByCode(BalanceModel::NC_101),
            $balance->getByCode(BalanceModel::NC_200),
        ])->sum();
        $operandTwo = collect([
            $this->getLongTermAssets($balance),
            $this->getNetWorkingCapital(),
            $this->getCash($balance),
            $this->getStocks($balance),
        ])->sum();

        return $operandOne - $operandTwo;
    }

    /**
     * Возвращает итоговые активы
     *
     * @param  \App\Models\Company\Balance $balance
     * @return float
     */
    public function getTotalAssets(BalanceModel $balance): float
    {
        return collect([
            $this->getLongTermAssets($balance),
            $this->getNetWorkingCapital(),
            $this->getCash($balance),
            $this->getStocks($balance),
            $this->getOtherAssets($balance)
        ])->sum();
    }

    /**
     * Возвращает уставный капитал
     *
     * @param  \App\Models\Company\Balance $balance
     * @return float
     */
    public function getAuthorizedCapital(BalanceModel $balance): float
    {
        return $balance->getByCode(BalanceModel::NC_410);
    }

    /**
     * Возвращает нераспределенную прибыль
     *
     * @param  \App\Models\Company\Balance $balance
     * @return float
     */
    public function getRetainedEarnings(BalanceModel $balance): float
    {
        return $balance->getByCode(BalanceModel::NC_414);
    }

    /**
     * Возвращает кредиты и займы
     *
     * @param  \App\Models\Company\Balance $balance
     * @return float
     */
    public function getCreditsAndLoans(BalanceModel $balance): float
    {
        return collect([
            $balance->getByCode(BalanceModel::NC_310),
            $balance->getByCode(BalanceModel::NC_311),
            $balance->getByCode(BalanceModel::NC_313)
        ])->sum();
    }

    /**
     * Возвращает краткосрочные обязательства
     *
     * @param  \App\Models\Company\Balance $balance
     * @return float
     */
    public function getShortTermLiabilities(BalanceModel $balance): float
    {
        return $balance->getByCode(BalanceModel::NC_300);
    }

    /**
     * Возвращает прочие обязательства
     *
     * @param  \App\Models\Company\Balance $balance
     * @return float
     */
    public function getOtherLiabilities(BalanceModel $balance): float
    {
        $operandOne = collect([
            $balance->getByCode(BalanceModel::NC_300),
            $balance->getByCode(BalanceModel::NC_301),
            $balance->getByCode(BalanceModel::NC_400),
            $balance->getByCode(BalanceModel::NC_500),
        ])->sum();
        $operandTwo = collect([
            $this->getAuthorizedCapital($balance),
            $this->getRetainedEarnings($balance),
            $this->getCreditsAndLoans($balance),
            $this->getShortTermLiabilities($balance),
        ])->sum();

        return $operandOne - $operandTwo;

    }

    /**
     * Возвращает итоговые пассивы
     *
     * @param  \App\Models\Company\Balance $balance
     * @return float
     */
    public function getTotalLiabilities(BalanceModel $balance): float
    {
        return collect([
            $this->getAuthorizedCapital($balance),
            $this->getRetainedEarnings($balance),
            $this->getCreditsAndLoans($balance),
            $this->getShortTermLiabilities($balance),
            $this->getOtherLiabilities($balance)
        ])->sum();
    }
}
