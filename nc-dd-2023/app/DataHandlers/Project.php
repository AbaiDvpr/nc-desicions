<?php declare(strict_types=1);

namespace App\DataHandlers;

use App\DataHandlers\Project\Dds;
use App\DataHandlers\Project\EfficiencyAnalysis;
use App\DataHandlers\Project\Fot;
use App\DataHandlers\Project\Income;
use App\DataHandlers\Project\Opiu;
use Illuminate\Support\Arr;

/**
 * Базовый DataHandler для работы с проектами
 */
class Project implements DataHandlerInterface
{
    /**
     * Возвращает обработчик данных анализа эффективности
     *
     * @return \App\DataHandlers\Project\EfficiencyAnalysis
     */
    public function getEfficiencyAnalysis(): EfficiencyAnalysis
    {
        return new EfficiencyAnalysis();
    }

    /**
     * Возвращает обработчик данных ОПИУ
     *
     * @return \App\DataHandlers\Project\Opiu
     */
    public function getOpiu(): Opiu
    {
        return new Opiu();
    }

    /**
     * Возвращает обработчик данных ДДС
     *
     * @return \App\DataHandlers\Project\Dds
     */
    public function getDds(): Dds
    {
        return new Dds();
    }

    /**
     * Возвращает обработчик данных по доходам
     *
     * @return \App\DataHandlers\Project\Income
     */
    public function getIncome(): Income
    {
        return new Income();
    }

    /**
     * Возвращает обработчик данных по ФОТ
     *
     * @return \App\DataHandlers\Project\Fot
     */
    public function getFot(): Fot
    {
        return new Fot();
    }

    /**
     * Суммирует значения по столбцам массива
     *
     * @param  array $records
     * @return array
     */
    public function getSumColumns(array $records = []): array
    {
        $result = [];

        // Суммируем по столбцам
        if (count($records) > 0) {
            $firstRowCols = collect($records)->max();

            if (count($firstRowCols)) {
                foreach ($firstRowCols as $indexCol => $firstRowCol) {
                    $result[$indexCol] = 0;

                    foreach ($records as $record) {
                        if (isset($record[$indexCol]) && is_numeric($result[$indexCol]) && is_numeric($record[$indexCol])) {
                            $result[$indexCol] = $result[$indexCol] + $record[$indexCol];
                        }
                    }
                }
            }
        }

        return $result;
    }

    /**
     * Удаляет "countCols" столбцов с названиями для дальнейших манипуляций
     *
     * @param  array $records
     * @param  int   $countCols
     * @return array
     */
    public function removeNamedColumns(array $records, int $countCols = 1): array
    {
        return collect($records)->map(function ($record) use ($countCols) {
            return collect($record)->splice($countCols)->values();
        })->toArray();
    }

    /**
     * Возвращает массив элементы которого умножаются на -1 (минус один)
     *
     * @param  array $records
     * @return array
     */
    public function getNegativeValues(array $records = []): array
    {
        $records = collect($records);

        if ($records->count() > 0) {
            $result = $records->map(function ($value) {
                return -1 * $value;
            });

            return $result->toArray();
        }

        return [];
    }

    /**
     * Вычисляет строку "Изменение в оборотном капитале"
     *
     * @param  array $dates
     * @param  array $capitals
     * @return array
     */
    public function getChangeInWorkingCapital(array $dates = [], array $capitals = []): array
    {
        $result = [];

        if (count($dates) > 0 && count($capitals) > 0) {
            $index       = 0;
            $totalSum    = 0; // общая сумма без учета годовых столбцов
            $totalByYear = 0;

            foreach ($dates as $date) {
                $result[$index] = $capitals[$index] - $totalSum;

                $totalSum = $totalSum + ($result[$index] ?? 0);
                $totalByYear = $totalByYear + ($result[$index] ?? 0);

                if ($date['isLast'] === true) {
                    $index++;

                    $result[$index] = $totalByYear;
                    $totalByYear    = 0;
                }

                $index++;
            }

        }

        return $result;
    }

    /**
     * Возвращает кол-во рабочих дней в месяце в зависимости от рабочей недели
     *
     * @param  int $schedule
     * @return int
     */
    public function getDaysInMonths(int $schedule = 1): int
    {
        return Arr::get(config('projects.original.daysInMonths.value'), $schedule);
    }

    /**
     * Отнимает процент НДС
     *
     * @param  array $records
     * @param  bool  $isNds
     * @return array
     */
    public function withoutNds(array $records, bool $isNds = false): array
    {
        if (count($records) === 0) {
            return [];
        }

        if ($isNds) {
            return $records;
        }

        return collect($records)->map(function ($values) {
            if (is_array($values)) {
                return collect($values)->map(function ($value) {
                    if ($value === 0 || is_string($value)) {
                        return $value;
                    }

                    return $value - ($value * config('projects.nds'));
                })->toArray();
            }

            return $values;
        })->toArray();
    }
}
