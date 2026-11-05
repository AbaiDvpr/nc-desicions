<?php declare(strict_types=1);

namespace App\Components\Report\Helpers\EfficiencyAnalysis;

trait Irr
{

    /**
     * Собирает значения для строки "NPV проекта"
     *
     * @param  $dates
     * @return array
     */
    public function getIrrNpvProject($dates): array
    {
        return collect([
            $dates['npvProject'],
            $this->prepareNpvProject($dates)
        ])->collapse()->toArray();
    }

    /**
     * Формируем массив с коэффициентами для расчета IRR
     *
     * @param  float $irr
     * @return int[]
     */
    protected function getIrrCoefficients(float $irr): array
    {
        $coefficients = [1];
        $index        = 0;

        foreach ($this->yearsPlaning as $yearPlan) {
            if ($index > 0) {
                $coefficients[] = $coefficients[$index - 1] / (1 + $irr);
            }

            $index++;
        }

        return $coefficients;
    }
}
