<?php declare(strict_types=1);

namespace App\DataHandlers\Project;

use App\DI;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;

class Fot
{
    /**
     * Формирует формулу для расчета налога
     *
     * @param  float                      $salaryFund
     * @param  array                      $tax
     * @param  int                        $index
     * @param  array                      $taxesResult
     * @param  array                      $diffDate
     * @param  int                        $countEmployees
     * @return array|string|string[]|null
     */
    public function createFormula(
        float $salaryFund,
        array $tax,
        int $index,
        array $taxesResult,
        array $diffDate,
        int $countEmployees
    )
    {
        $formula = null;

        if (isset($tax['formula'])) {
            $value   = $tax['value'];
            $socTax  = $tax['soc-tax'] ?? 0;
            $pension = $tax['pension'] ?? 0;

            if (isset($tax['after']) && isset($tax['after']['year'])) {
                $currentYear = Carbon::parse($diffDate['start'])->year;

                if ($currentYear >= $tax['after']['year']) {
                    $value = $tax['after']['value'];
                }
            }

            // Считаем сумму МРП
            $mrp = $this->calculateMrp($tax, $countEmployees);

            $search  = ['%value%', '%total%', '%soc-tax%', '%pension%', '%mrp%'];
            $replace = [$value, $salaryFund, $socTax, $pension, $mrp];

            preg_match_all('/\d/', $tax['formula'], $taxes);

            if (isset($taxes[0]) && count($taxes[0]) > 0) {
                foreach ($taxes[0] as $id) {
                    array_push($search, "%tax-{$id}%");
                    array_push($replace, $taxesResult[$id][$index]);
                }
            }

            $formula = str_replace($search, $replace, $tax['formula']);
        }

        return eval('return ' . $formula . ';');
    }

    /**
     * Считает сумму МРП на всех сотрудников
     *
     * @param  array $tax
     * @param  int   $countEmployees
     * @return float
     */
    private function calculateMrp(array $tax, int $countEmployees): float
    {
        $mrp = $tax['mrp'] ?? 0;

        if ($mrp > 0) {
            // Считаем сумму МРП
            return (float) ($mrp * config('projects.mrp') * ($countEmployees ?: 1));
        }

        return 1.0;
    }

    /**
     * Суммируем ежемесячную сумму выручки по продукции которая указана для расчета надбавки
     *
     * @param  \Illuminate\Database\Eloquent\Collection $incomes
     * @param  array                                    $detailIncome
     * @return array
     */
    public function getSumIncomes(Collection $incomes, array $detailIncome): array
    {
        $result = [];

        if ($incomes->count()) {
            foreach ($incomes as $income) {
                if (array_key_exists($income->id, $detailIncome)) {
                    $result[] = $detailIncome[$income->id];
                }
            }
        }

        return DI::getDH()->getProject()->getSumColumns($result);
    }

    /**
     * Данные по фонду оплаты труда Производственного Персонала
     *
     * @param  array $total
     * @param  array $taxes
     * @return array
     */
    public function payrollFundPP(array $total, array $taxes): array
    {
        if (count($total) == 0 || count($taxes) == 0) {
            return [];
        }

        return [
            'payrollFundPP'      => DI::getDH()->getProject()->getSumColumns([$total, $taxes]),
            'payrollFundPPTotal' => $total,
            'payrollFundPPTaxes' => $taxes,
        ];
    }
}
