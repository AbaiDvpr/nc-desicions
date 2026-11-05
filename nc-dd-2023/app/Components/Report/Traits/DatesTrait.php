<?php declare(strict_types=1);

namespace App\Components\Report\Traits;


use Carbon\Carbon;

trait DatesTrait
{
    /**
     * Формирует список годов планирования с кол-вом месяцев в год планирования
     *
     * @return void
     */
    private function getYearsPlaning(): void
    {
        $planning = $this->project->getPlanning();

        if (!empty($planning)) {
            $fromDate  = Carbon::parse($planning['from']);
            $toDate    = Carbon::parse($planning['to']);
            $inflation = 1;
            $tariff    = 1;

            for ($year = $fromDate->year; $year <= $toDate->year; $year++) {
                $firstYear       = $fromDate->year === $year;
                $lastYear        = $toDate->year === $year;
                $startDateInYear = Carbon::create($year, $firstYear ? $fromDate->month : 1);
                $endDateInYear   = Carbon::create($year, $lastYear ? $toDate->month : 12, 31);

                $this->yearsPlaning[$year] = [
                    'year'         => $year,
                    'monthsInYear' => (int) round($startDateInYear->floatDiffInRealMonths($endDateInYear)),
                    'fromDate'     => $startDateInYear->format('d.m.Y'),
                    'toDate'       => $endDateInYear->format('d.m.Y'),
                    'discount'     => $this->getDiscountFactor($fromDate->year, $year),
                    'inflation'    => $inflation,
                    'tariff'       => $tariff,
                ];

                $inflation = $inflation + ($inflation * config('projects.original.inflation.value'));
                $tariff    = $tariff + ($tariff * config('projects.original.tariff.value'));
            }
        }
    }

    /**
     * Обновляет данные по коэффициенту дисконтирования после его расчета на тисте "FinancingSheet"
     *
     * @return void
     */
    public function updateDiscountFactorAfterCalculating()
    {
        if (count($this->yearsPlaning) > 0) {
            $planning = $this->project->getPlanning();
            $fromDate = Carbon::parse($planning['from']);

            foreach ($this->yearsPlaning as $year => $data) {
                $this->yearsPlaning[$year]['discount'] = $this->getDiscountFactor($fromDate->year, $year);
            }
        }
    }

    /**
     * Просчитывает коэффициент дисконтирования по годам
     *
     * @param  int   $startYear
     * @param  int   $currentYear
     * @return float
     */
    private function getDiscountFactor(int $startYear, int $currentYear): float
    {
        $exponential = $currentYear - $startYear;

        return (float) pow(1/(1 + $this->report->discountPercent), $exponential);
    }

    /**
     * Вычисляет коэффициент инфляции до года начала планирования
     *
     * @param  int  $year
     * @return float
     */
    private function getDefaultInflation(int $year): float
    {
        $inflation   = 1;
        $currentYear = Carbon::now()->year;

        if (!$year || ($currentYear >= $year)) {
            return $inflation;
        }

        for ($i = $currentYear; $i < $year; $i++) {
            $inflation = $inflation + ($inflation * config('projects.original.inflation.value'));
        }

        return $inflation;
    }
}
