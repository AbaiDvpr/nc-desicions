<?php declare(strict_types=1);

namespace App\DataHandlers;

use Illuminate\Support\Carbon;

/**
 * DH для виджетов
 */
class Widget implements DataHandlerInterface
{
    /**
     * Возвращает отформатированные данные для отчетов
     *
     * @param  array                      $reports
     * @param  array                      $config
     * @param  \Illuminate\Support\Carbon $from
     * @param  \Illuminate\Support\Carbon $to
     * @return array
     */
    public function getDataForReport(array $reports, array $config, Carbon $from, Carbon $to): array
    {
        if (!count($reports)) {
            return ['periods' => [], 'data' => []];
        }

        $periods = $this->getRangePeriods($from, $to);
        $data    = $this->getFilledData($reports, $config, $from, $to);

        return ['periods' => $periods, 'data' => $data];
    }

    /**
     * Возвращает отформатированные данные
     *
     * @param  array                      $reports
     * @param  array                      $config
     * @param  \Illuminate\Support\Carbon $from
     * @param  \Illuminate\Support\Carbon $to
     * @return array
     */
    public function getData(array $reports, array $config, Carbon $from, Carbon $to): array
    {
        return $this->getFilledData($reports, $config, $from, $to);
    }

    /**
     * Возвращает конфиг с заполненными пустыми значениями
     *
     * @param  array $config
     * @param  array $periods
     * @return array
     */
    protected function getPreparedConfig(array $config, array $periods): array
    {
        return collect($config)->map(function($item) use ($periods) {
            $item['data'] = [];

            collect($periods)->map(function($period) use(&$item) {
                $item['data'][] = ['period' => $period, 'value' => 0];
            });

            return $item;
        })->toArray();
    }

    /**
     * Возвращает заполненные данные для графиков и таблиц
     *
     * @param  array                      $reports
     * @param  array                      $config
     * @param  \Illuminate\Support\Carbon $from
     * @param  \Illuminate\Support\Carbon $to
     * @return array
     */
    protected function getFilledData(array $reports, array $config, Carbon $from, Carbon $to): array
    {
        if (!count($reports)) {
            return [];
        }

        $data = $this->getPreparedConfig($config, $this->getRangePeriods($from, $to));

        foreach ($reports as $report) {
            $period = $report['period'];

            collect($report)->map(function($item, $key) use (&$data, $period) {
                if (array_key_exists($key, $data)) {
                    $index = collect($data[$key]['data'])->search(function (array $item) use ($period) {
                        return $item['period'] === $period;
                    });

                    if ($index !== false && isset($data[$key]['data'][$index])) {
                        $data[$key]['data'][$index]['value'] = round((float) $item);
                    }
                }
            });
        }

        return $data;
    }

    /**
     * Возвращает массив с диапазоном периодов
     *
     * @param  \Illuminate\Support\Carbon $from
     * @param  \Illuminate\Support\Carbon $to
     * @return array
     */
    protected function getRangePeriods(Carbon $from, Carbon $to): array
    {
        return collect($from->range($to, 1, 'month')->toArray())
            ->map(function ($month) {
                return $month->format('Y-m-d');
            })->toArray();
    }
}
