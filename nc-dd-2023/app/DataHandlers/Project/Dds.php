<?php declare(strict_types=1);

namespace App\DataHandlers\Project;

use App\DI;

/**
 * DataHandler для расчётов ДДС
 */
class Dds
{
    /**
     * Получает сумму "Итого выбытие по инвестиционной деятельности" с минусом по модулю
     *
     * @param  array $investmentActivities
     * @return array
     */
    public function getInvestmentActivitiesTotal(array $investmentActivities): array
    {
        if (count($investmentActivities) === 0) {
            return [];
        }

        $dhProject    = DI::getDH()->getProject();
        $withoutNames = $dhProject->removeNamedColumns($investmentActivities);

        $sumRecords = $dhProject->getSumColumns(collect($withoutNames)->map(function($item) {
            return collect($item)->map(function ($number) {
                return abs($number);
            });
        })->toArray());

        return collect($sumRecords)->map(function ($number) {
            return $number > 0 ? -$number : $number;
        })->toArray();
    }

    /**
     * Получить данные для строки "Дебиторская задолженность" при условии оборотного капитала
     *
     * @param  array $dates
     * @param  array $proceeds
     * @param  int   $receivables
     * @param  int   $schedule
     * @return array
     */
    public function getReceivables(
        array $dates = [],
        array $proceeds = [],
        int $receivables = 0,
        int $schedule = 1
    ): array
    {
        return $this->getTurnoverLine($dates, $proceeds, $receivables, $schedule);
    }

    /**
     * Получить данные для строки "Запасы" при условии оборотного капитала
     *
     * @param  array $dates
     * @param  array $proceeds
     * @param  int   $reserves
     * @param  int   $schedule
     * @return array
     */
    public function getReserves(array $dates = [], array $proceeds = [], int $reserves = 0, int $schedule = 1): array
    {
        return $this->getTurnoverLine($dates, $proceeds, $reserves, $schedule);
    }

    /**
     * Получить данные для строки "Кредиторская задолженность" при условии оборотного капитала
     *
     * @param  array $dates
     * @param  array $costPrice
     * @param  int   $reserves
     * @param  int   $schedule
     * @return array
     */
    public function getAccountsPayable(
        array $dates = [],
        array $costPrice = [],
        int $reserves = 0,
        int $schedule = 1
    ): array
    {
        return $this->getTurnoverLine($dates, $costPrice, $reserves, $schedule);
    }

    /**
     * Получить данные для строк по оборачиваемости
     *
     * @param  array $dates
     * @param  array $proceeds
     * @param  int   $receivables
     * @param  int   $schedule
     * @return array
     */
    private function getTurnoverLine(
        array $dates = [],
        array $proceeds = [],
        int $receivables = 0,
        int $schedule = 1
    ): array
    {
        if (count($proceeds) > 0 && count($dates) > 0) {
            $days  = DI::getDH()->getProject()->getDaysInMonths($schedule);
            $index = 0;

            foreach ($dates as $date) {
                $proceeds[$index] = $proceeds[$index] * $receivables / $days;

                if ($date['isLast'] === true) {
                    $index++;

                    $proceeds[$index] = 0;
                }

                $index++;
            }

            return $proceeds;
        }

        return [];
    }
}
