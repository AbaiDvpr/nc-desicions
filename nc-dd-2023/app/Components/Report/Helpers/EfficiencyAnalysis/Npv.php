<?php declare(strict_types=1);

namespace App\Components\Report\Helpers\EfficiencyAnalysis;

use App\DI;

trait Npv
{
    /**
     * Получает значение ячейки с годовыми значениями
     *
     * @param  array $records
     * @return array
     */
    protected function getTotalByYears(array $records): array
    {
        $return = [];

        if (count($this->diffDates) > 0 && count($records) > 0) {
            $i       = 1;
            $records = $this->removeNamedColumns([$records])[0];

            foreach ($this->diffDates as $diffDate) {
                if ($diffDate['firstDate'] && $diffDate['lastDate']) {
                    $return[] = $records[$i];

                    ++$i;
                }
                ++$i;
            }
        }

        return $return;
    }

    /**
     * Высчитывает значения для строки "Free Cash Flow"
     *
     * @param  $dates
     * @return array
     */
    public function getFreeCashFlow($dates): array
    {
        $this->npvFreeCashFlow = DI::getDH()->getProject()->getSumColumns([
            $this->removeNamedColumns([$dates['moneyMovement']], 3)[0],
            $this->removeNamedColumns([$dates['costs']], 3)[0]
        ]);

        return collect([$dates['freeCashFlow'], $this->npvFreeCashFlow])->collapse()->toArray();
    }

    /**
     * Собирает значения для строки "Free Cash Flow нарастающим итогом"
     *
     * @param  $dates
     * @return array
     */
    public function getFreeCashFlowTotal($dates): array
    {
        $this->npvFreeCashFlowTotal = $this->prepareFreeCashFlowTotal($dates);

        return collect([
            $dates['freeCashFlowTotal'],
            $this->npvFreeCashFlowTotal,
        ])->collapse()->toArray();
    }

    /**
     * Подготавливает данные для строки "Free Cash Flow нарастающим итогом"
     *
     * @param  array $dates
     * @return array
     */
    private function prepareFreeCashFlowTotal(array $dates): array
    {
        if (!isset($dates['freeCashFlow'])) {
            return [];
        }

        $iteration           = 0;
        $freeCashFlow        = $this->removeNamedColumns([$dates['freeCashFlow']], 3)[0];
        $freeCashFlowTotal[] = $freeCashFlow[0];

        foreach ($this->yearsPlaning as $yearPlan) {
            if ($iteration > 0) {
                $freeCashFlowTotal[] = $freeCashFlowTotal[$iteration - 1] + $freeCashFlow[$iteration];
            }

            $iteration++;
        }

        return $freeCashFlowTotal;
    }

    /**
     * Собирает значения для строки "Дисконтированный денежный поток"
     *
     * @param  $dates
     * @return array
     */
    public function getDiscountedCashFlow($dates): array
    {
        $this->prepareDiscountedCashFlow = $this->prepareDiscountedCashFlow($dates);

        return collect([
            $dates['discountedCashFlow'],
            $this->prepareDiscountedCashFlow,
        ])->collapse()->toArray();
    }

    /**
     * Формирует данные для заполнения строки "Дисконтированный денежный поток"
     *
     * @param  array $dates
     * @return array
     */
    private function prepareDiscountedCashFlow(array $dates): array
    {
        if (!isset($dates['freeCashFlow'])) {
            return [];
        }

        $freeCashFlowTotal = $this->removeNamedColumns([$dates['freeCashFlow']], 3)[0];
        $yearsPlaning      = collect($this->yearsPlaning)->values()->toArray();

        return collect($freeCashFlowTotal)->map(function ($freeCache, $yearIndex) use ($yearsPlaning) {
            return $freeCache * $yearsPlaning[$yearIndex]['discount'];
        })->values()->toArray();
    }

    /**
     * Собирает значения для строки "NPV проекта"
     *
     * @param  $dates
     * @return array
     */
    public function getNpvProject($dates): array
    {
        $this->npv = $this->prepareNpvProject($dates);

        return collect([$dates['npvProject'], $this->npv])->collapse()->toArray();
    }

    /**
     * Подготавливает данные для заполнения строки "NPV проекта"
     *
     * @param  array $dates
     * @return array
     */
    private function prepareNpvProject(array $dates): array
    {
        if (!isset($dates['discountedCashFlow'])) {
            return [];
        }

        $iteration           = 0;
        $discountedCashFlow  = $this->removeNamedColumns([$dates['discountedCashFlow']], 3)[0];
        $freeCashFlowTotal[] = $discountedCashFlow[0];

        foreach ($this->yearsPlaning as $yearPlan) {

            if ($iteration > 0) {
                $freeCashFlowTotal[] = $freeCashFlowTotal[$iteration - 1] + $discountedCashFlow[$iteration];
            }

            $iteration++;
        }

        return $freeCashFlowTotal;
    }
}
