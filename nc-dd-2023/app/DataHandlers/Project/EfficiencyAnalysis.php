<?php declare(strict_types=1);

namespace App\DataHandlers\Project;

use App\DI;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;

/**
 * DataHandler для расчётов анализа эффективности
 */
class EfficiencyAnalysis
{
    /**
     * Возвращает коэффициент для расчетов срока окупаемости за первый год
     *
     * @param  array $dates
     * @return float
     */
    public function getRatioForFirstYear(array $dates): float
    {
        $lastMonthInYear = collect($dates)->firstWhere(function ($date) {
            return $date['isLast'] === true;
        });

        // Первый месяц в первом году
        $startDate = Carbon::parse($lastMonthInYear['firstDate']);

        // Последний месяц в первом году
        $lastDate  = Carbon::parse($lastMonthInYear['lastDate']);

        // Разница в месяцах
        $diff = (int) round($lastDate->floatDiffInMonths($startDate));

        return round($diff / 12, 2);
    }

    /**
     * Высчитывает коэффициент для расчетов сроков окупаемости
     *
     * @param  array $dates
     * @param  array $cashFlowTotal
     * @param  array $operatingActivities
     * @param  array $investmentActivities
     * @return float
     */
    public function getCoefficient(
        array $dates,
        array $cashFlowTotal,
        array $operatingActivities,
        array $investmentActivities
    ): float
    {
        // сначала смотрим, вышел ли в первый год на окупаемость
        // if ($this->getCountNegativeValue($cashFlowTotal) < 1) {
        //     // возвращаем только коэффициент за первый год
        //     return $this->getRatioForFirstYear($dates);
        // }

        if (isset($investmentActivities['totalCashFlow']) && count($investmentActivities['totalCashFlow']) > 0) {
            $investmentActivities = DI::getDH()->getProject()->removeNamedColumns($investmentActivities);

            // Получаем "Итого денежный поток по инвестиционной деятельности"
            $investment = 0;

            if (!is_null(Arr::first($investmentActivities['totalCashFlow']))) {
                $investment = abs(Arr::first($investmentActivities['totalCashFlow']));
            }

            // Получаем "Итого денежный поток по операционной деятельности"
            $operating = Arr::last(DI::getDH()->getProject()->removeNamedColumns($operatingActivities));

            $months = $this->getCountMonthsToPayback($dates, $operating, $investment);

            return round($months / 12, 2);
        }

        // еще под вопросом, что по умолчанию возвращать
        return 1.0;
    }

    /**
     * Высчитывает кол-во месяцев до выхода на окупаемость
     *
     * @param  array $dates
     * @param  array $operating
     * @param  float $investment
     * @return int
     */
    protected function getCountMonthsToPayback(array $dates, array $operating, float $investment): int
    {
        // Общая сумма по операционной деятельности без учета годовых до выхода на окупаемость
        $totalOperating = 0;

        // Кол-во месяцев до выхода на окупаемость
        $monthsPayback  = 0;

        // порядковый номер вместе с итоговыми(годовыми) столбцами
        $index = 0;
        foreach ($dates as $date) {
            if (isset($operating[$index])) {
                $totalOperating += $operating[$index];

                if ($totalOperating >= $investment) {
                    continue;
                }

                if ($date['isLast'] === true) {
                    $index++;
                }
            }

            $index++;

            $monthsPayback++;
        }

        return $monthsPayback;
    }

    /**
     * Возвращает кол-во отрицательных значений в последовательности
     *
     * @param  array $sequence
     * @return int
     */
    public function getCountNegativeValue(array $sequence): int
    {
        return collect($sequence)->filter(function ($item) {
            return $item < 0;
        })->count();
    }
}
