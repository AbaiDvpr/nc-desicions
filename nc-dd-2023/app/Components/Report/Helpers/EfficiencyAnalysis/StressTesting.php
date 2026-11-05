<?php declare(strict_types=1);

namespace App\Components\Report\Helpers\EfficiencyAnalysis;

use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Color;

trait StressTesting
{

    /**
     * Формирует и устанавливает первую таблицу по стресс тестированию
     *
     * @param  int $lastRow
     * @return int
     */
    public function getFirstTable(int $lastRow): int
    {
        $interestOnExpenses = config('projects.efficiencyAnalysis.interestOnExpenses');
        $interestOnIncome   = config('projects.efficiencyAnalysis.interestOnIncome');

        $tableData = $this->prepareFirstTable($interestOnExpenses, $interestOnIncome);

        foreach ($this->yearsPlaning as $year => $yearsPlan) {
            $tableData[0][] = null;
            $tableData[1][] = $year;
            $tableData[2][] = $yearsPlan['discount'];
        }

        $this->sheet->fromArray($tableData, null, 'A' . $lastRow, true);

        $lastRow    = $this->incrementRow($lastRow);
        $lastColumn = $this->sheet->getCellByColumnAndRow(count(head($tableData)), $lastRow)->getCoordinate();

        $this->setHeaderStyle('A' . ($lastRow - 1) . ':' . $lastColumn, $this->mainSubHeaderColor);

        for ($i = 0; $i <= count($tableData) - 2; $i++) {
            if ($i > 0) {
                $this->sheet->getStyle('B' . $lastRow . ':' . $this->sheet->getCell($lastColumn)
                        ->getColumn() . $lastRow)->applyFromArray([
                    'numberFormat' => [
                        'formatCode' => self::FORMAT_NUMBER
                    ],
                    'alignment'    => [
                        'horizontal' => Alignment::HORIZONTAL_RIGHT,
                    ],
                    'borders'      => [
                        'allBorders' => array(
                            'borderStyle' => Border::BORDER_THIN,
                            'color'       => [
                                'argb' => Color::COLOR_BLACK
                            ],
                        ),
                    ],
                ]);
            }

            $lastRow = $this->incrementRow($lastRow);
        }


        return $lastRow + 2;
    }

    /**
     * Рассчитываем таблицу стресс-тестирования с учетом процентов
     *
     * @param  float $interestOnExpenses // процент для расходов
     * @param  float $interestOnIncome   // процент для доходов
     * @return array
     */
    private function prepareFirstTable(float $interestOnExpenses, float $interestOnIncome): array
    {
        // Добавляем "Инвестиционные затраты"
        $totalCashFlow        = $this->getInvestmentCosts();
        $getRevenuesFromSales = $this->getRevenuesFromSales($interestOnIncome);
        $getExpenses          = $this->getExpenses($interestOnExpenses);
        $freeCashFlow         = $this->getFreeCashFlowForFirstTable($totalCashFlow, $getRevenuesFromSales, $getExpenses);
        $discountedCashFlow   = $this->getDiscountedCashFlowForFirstTable($freeCashFlow);
        $npv                  = $this->getNpvForFirstTable($discountedCashFlow);

        return [
            [($interestOnExpenses * 100) . '%'],  // процент на расходы
            [($interestOnIncome * 100) . '%'],    // процент на выручку
            ['коэффициент дисконтирования'],
            collect([['Инвестиционные затраты'], $totalCashFlow])->collapse()->values()->toArray(),
            $getRevenuesFromSales,
            $getExpenses,
            $freeCashFlow,
            collect([['Дисконтированный денежный поток'], $discountedCashFlow])->collapse()->values()->toArray(),
            collect([['NPV проекта'], $npv])->collapse()->values()->toArray(),
        ];
    }

    public function getSecondTable(int $lastRow)
    {
        $financingTotal   = $this->report->financingTotal['total'] ?? 1;
        $stressPercentage = config('projects.efficiencyAnalysis.stressPercentage');
        $tableData = [[
            '% изменения доходов',
            'NPV',
            'Р рентабельность проекта',
            '%изменения расходов',
            'NPV',
            'Р рентабельность проекта',
        ]];

        foreach ($stressPercentage as $percent) {
            $calculatedTableIncome = $this->prepareFirstTable(1, 1 + $percent);
            $calculatedTableExpenses = $this->prepareFirstTable(1 + $percent, 1);

            $npvIncome   = last(last($calculatedTableIncome));
            $npvExpenses = last(last($calculatedTableExpenses));

            $tableData[] = [
                ($percent * 100) . '%',
                $npvIncome,
                round(($npvIncome / $financingTotal * 100), 2) . '%',
                ($percent * 100) . '%',
                $npvExpenses,
                round(($npvExpenses / $financingTotal * 100), 2) . '%',
            ];
        }

        $this->sheet->fromArray($tableData, null, 'A' . $lastRow, true);
        $lastColumn = $this->sheet->getCellByColumnAndRow(count(head($tableData)), $lastRow)->getCoordinate();
        $this->setHeaderStyle('A' . $lastRow . ':' . $lastColumn, $this->mainSubHeaderColor);
        $this->sheet->getRowDimension($lastRow)->setRowHeight(40);
        $this->sheet->getStyle('A' . $lastRow . ':' . $lastColumn)->getAlignment()->setWrapText(true);
        $this->sheet->getStyle('A' . $lastRow . ':' . $lastColumn)->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);

        $lastRow = $this->incrementRow($lastRow);

        for ($i = 0; $i <= count($tableData) - 2; $i++) {
            $this->sheet->getStyle('A' . $lastRow . ':' . $this->sheet->getCell($lastColumn)
                    ->getColumn() . $lastRow)->applyFromArray([
                'numberFormat' => [
                    'formatCode' => self::FORMAT_NUMBER
                ],
                'alignment'    => [
                    'horizontal' => Alignment::HORIZONTAL_RIGHT,
                ],
                'borders'      => [
                    'allBorders' => array(
                        'borderStyle' => Border::BORDER_THIN,
                        'color'       => [
                            'argb' => Color::COLOR_BLACK
                        ],
                    ),
                ],
            ]);

            $lastRow = $this->incrementRow($lastRow);
        }

    }

    /**
     * Формирует массив "Инвестиционные затраты" для первой таблицы
     *
     * @return array
     */
    private function getInvestmentCosts(): array
    {
        $totalCashFlow = 0;

        if (array_key_exists('totalCashFlow', $this->report->investmentActivities)) {
            $totalCashFlow = $this->report->investmentActivities['totalCashFlow'];
        }

        return [$totalCashFlow[1] ?? 0];
    }

    /**
     * Формирует массив с выручкой от реализации для первой таблицы
     *
     * @param  $interestOnIncome
     * @return array
     */
    private function getRevenuesFromSales($interestOnIncome): array
    {
        $opiuIncomeTotalByYear = $this->getTotalByYears(
            $this->addNameToRow($this->report->opiuIncomeTotal, 'ИТОГО')
        );

        return collect([
            ['Выручка от реализации'],
            collect($opiuIncomeTotalByYear)->map(function ($value) use ($interestOnIncome) {
                return $value * $interestOnIncome;
            })->values()->toArray(),
        ])->collapse()->toArray();
    }

    /**
     * Формирует массив с расходами для первой таблицы
     *
     * @param  $interestOnExpenses
     * @return array
     */
    private function getExpenses($interestOnExpenses): array
    {
        $operatingActivitiesTotal = $this->getTotalByYears(
            $this->addNameToRow($this->report->operatingActivitiesTotal, 'ИТОГО')
        );

        return collect([
            ['Расходы'],
            collect($operatingActivitiesTotal)->map(function ($value) use ($interestOnExpenses) {
                return $value * $interestOnExpenses;
            })->values()->toArray(),
        ])->collapse()->toArray();
    }

    /**
     * Формирует массив с Free Cash Flow для первой таблицы
     *
     * @param  array $totalCashFlow
     * @param  array $getRevenuesFromSales
     * @param  array $getExpenses
     * @return array
     */
    private function getFreeCashFlowForFirstTable(
        array $totalCashFlow,
        array $getRevenuesFromSales,
        array $getExpenses
    ): array
    {
        $getExpenses          = $this->removeNamedColumns([$getExpenses])[0];
        $getRevenuesFromSales = $this->removeNamedColumns([$getRevenuesFromSales])[0];

        $freeCashFlow = collect($getExpenses)->map(function ($value, $index) use ($totalCashFlow, $getRevenuesFromSales) {
            return ($totalCashFlow[$index] ?? 0) + $getRevenuesFromSales[$index] - $value;
        })->toArray();

        return collect([
            ['Free Cash Flow'],
            $freeCashFlow
        ])->collapse()->values()->toArray();
    }

    /**
     * Формирует массив с "Дисконтированный денежный поток" для первой таблицы
     *
     * @param  array $freeCashFlow
     * @return array
     */
    private function getDiscountedCashFlowForFirstTable(array $freeCashFlow): array
    {
        $discountedCashFlow = [];
        $freeCashFlow       = $this->removeNamedColumns([$freeCashFlow])[0] ?? [];

        if (empty($freeCashFlow)) {
            return [];
        }

        $i = 0;
        foreach ($this->yearsPlaning as $year => $yearsPlan) {
            $discountedCashFlow[] = $freeCashFlow[$i] * $yearsPlan['discount'];

            $i = $this->incrementRow($i);
        }

        return $discountedCashFlow;
    }

    /**
     * Формирует массив с "NPV проекта" для первой таблицы
     *
     * @param  array $discountedCashFlow
     * @return array
     */
    private function getNpvForFirstTable(array $discountedCashFlow): array
    {
        if (empty($discountedCashFlow)) {
            return [];
        }

        $iteration = 0;
        $npv[]     = $discountedCashFlow[0];

        foreach ($this->yearsPlaning as $yearPlan) {

            if ($iteration > 0) {
                $npv[] = $npv[$iteration - 1] + $discountedCashFlow[$iteration];
            }

            $iteration++;
        }

        return $npv;
    }
}
