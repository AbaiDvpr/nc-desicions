<?php declare(strict_types=1);

namespace App\Components\Report\Sheets;

use App\Components\Report\Sheets\Interfaces\SheetInterface;
use App\DI;
use App\Models\Project\SalaryFund;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Color;

/**
 * Лист ФОТ (Фонд Оплаты Труда)
 */
class FotSheet extends AbstractReportSheet implements SheetInterface
{
    /**
     * Номер текущего листа
     */
    const CURRENT_SHEET = 4;

    /**
     * Строка с которой необходимо начать запись в файл
     */
    const START_ROW = 4;

    /**
     * Текущий активный лист
     *
     * @var \PhpOffice\PhpSpreadsheet\Worksheet\Worksheet
     */
    protected $sheet;

    /**
     * @inheritdoc
     *
     * @return \App\Components\Report\Sheets\FotSheet
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     */
    public function generate(): self
    {
        $this->setActiveSheet(self::CURRENT_SHEET);
        $this->sheet = $this->getActiveSheet();
        $startRow    = self::START_ROW;
        $projectFot  = DI::getRepository()->getProject()->getFot($this->project->getUuid());

        $this->setDatesToSheet('F1', 6, 1);

        $managerialStaffPayroll = $projectFot->getManagerialStaff();

        if ($managerialStaffPayroll->count() > 0) {
            // @var $this->report->salaryFundTotal
            $startRow = $this->setSalaryFund($managerialStaffPayroll, $startRow);

            // @var $this->report->salaryFundTaxes
            $startRow = $this->fillTaxes($startRow, $managerialStaffPayroll->sum('count'));
        }

        $productionStaffSalary = $projectFot->getIndustrial();

        if ($productionStaffSalary->count() > 0) {
            $startRow = $this->setSalaryFund(
                $productionStaffSalary,
                $startRow + 4,
                'vpSalaryFundTotal',
                'ФОТ ПП',
                true
            );

            $this->fillTaxes(
                $startRow,
                $productionStaffSalary->sum('count'),
                'vpSalaryFundTotal',
                'vpSalaryFundTaxes',
                'ФОТ налоги ПП'
            );
        }

        return $this;
    }

    /**
     * Заполнить таблицу "Фонд заработной платы"
     *
     * @param  Collection                          $salaryFunds
     * @param  int                                 $startRow
     * @param  string                              $salaryFundTotal
     * @param  string                              $tableName
     * @return int
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     */
    protected function setSalaryFund(
        Collection $salaryFunds,
        int $startRow,
        string $salaryFundTotal = 'salaryFundTotal',
        string $tableName = 'ФОТ АУП',
        bool $withPlannedCapacity = false
    ): int
    {
        $salesStartDate = $this->project->salesStartDate;
        $salaryFundData = [];

        foreach ($salaryFunds as $iteration => $salaryFund) {
            $this->sheet->setCellValue('A' . $startRow, ++$iteration);
            $this->sheet->setCellValue('B' . $startRow, $salaryFund->name);
            $this->sheet->setCellValue('C' . $startRow, $salaryFund->salaryPerMonth);
            $this->sheet->setCellValue('D' . $startRow, $salaryFund->count);
            $this->sheet->setCellValue('E' . $startRow, $salaryFund->percent ? $salaryFund->percent . '%' : null);
            $this->sheet->getStyle('E' . $startRow)->applyFromArray([
                'borders' => [
                    'right' => [
                        'borderStyle' => Border::BORDER_THIN,
                        'color'       => [
                            'argb' => Color::COLOR_BLACK
                        ],
                    ],
                ]
            ]);

            if (count($this->diffDates) > 0) {
                $total = 0;
                $index = 0;

                foreach ($this->diffDates as $diffDate) {
                    $startDate = Carbon::parse($diffDate['start']);

                    if (!$salaryFund->invest && Carbon::parse($salesStartDate)->gt($startDate)) {
                        $salaryFundData[$iteration][] = 0;
                        $total                        = 0;
                    } else {
                        $persons = $salaryFund->count;

                        // Для ПП учитываем и процент выхода на плановый объем продаж
                        if (true === $withPlannedCapacity) {
                            $percent = $diffDate['percent'];

                            if ($percent === 0) {
                                $percent = 1;
                            }

                            $persons = (int) ceil($persons * $percent);
                        }

                        $totalSum = $this->getSalaryWithInterest($salaryFund, (int) $persons, $index, $startDate->year);

                        $salaryFundData[$iteration][] = $totalSum;
                        $total                        = $total + $totalSum;
                    }

                    ++$index;

                    if ($diffDate['firstDate'] && $diffDate['lastDate']) {
                        $salaryFundData[$iteration][] = $total;
                        $total                        = 0;

                        ++$index;
                    }
                }
            }

            $startRow++;
        }

        $this->report->{$salaryFundTotal} = DI::getDH()->getProject()->getSumColumns($salaryFundData);
        $salaryFundData[]                 = $this->report->{$salaryFundTotal};

        $this->sheet->setCellValue('A' . $startRow, 'ИТОГО')->mergeCells('A'. $startRow . ':E' . $startRow);
        $this->setTableValues($salaryFunds, $salaryFundData, $startRow);

        $getCell = $this->sheet->getCellByColumnAndRow(count(last($salaryFundData)) + 5, $startRow);
        $count   = $startRow - count($salaryFundData);

        $this->setStyleForResultRow('A' . $startRow . ':' . $getCell->getCoordinate());

        $this->setSubHeader($tableName, $getCell->getColumn(), $count, 'empty');

        $this->setStyleByResultColumn($startRow - $salaryFunds->count(), $startRow);

        return $startRow;
    }

    /**
     * Получить сумму с учетом процента.
     * Высчитывает процент надбавки к ЗП от общей выручки или от определенной продукции
     *
     * @param  \App\Models\Project\SalaryFund $salaryFund
     * @param  int                            $persons
     * @param  int                            $index
     * @return float
     */
    protected function getSalaryWithInterest(SalaryFund $salaryFund, int $persons, int $index, int $year): float
    {
        $totalIncome    = $this->report->totalIncome;
        $persons        = ($persons > 0) ? $persons : 1;
        $tariff         = $this->yearsPlaning[$year]['tariff'] ?? 1;
        $salaryPerMonth = $salaryFund->salaryPerMonth * $tariff * $persons;

        if ($salaryFund->percent) {
            $percent = $salaryFund->percent / 100;
            $income  = $totalIncome[$index] ?? 1;
            $incomes = $salaryFund->getIncomes();

            if (!is_null($incomes)) {
                $sumIncomes = DI::getDH()->getProject()->getFot()->getSumIncomes($incomes, $this->report->detailIncome);

                if (count($sumIncomes) > 0) {
                    $income = $sumIncomes[$index] ?? 1;
                }
            }

            if ($income > 1) {
                $percentSum     = $income * $percent;
                $salaryPerMonth = $salaryPerMonth + $percentSum;
            }

            return $salaryPerMonth;
        }

        return $salaryPerMonth;
    }

    /**
     * Заполнить таблицу с налогами
     *
     * @param  int                                 $row
     * @param  int                                 $countEmployees
     * @param  string                              $salaryFundTotal
     * @param  string                              $salaryFundTax
     * @param  string                              $tableName
     * @return int
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     */
    protected function fillTaxes(
        int $row = 10,
        int $countEmployees,
        string $salaryFundTotal = 'salaryFundTotal',
        string $salaryFundTax = 'salaryFundTaxes',
        string $tableName = 'ФОТ налоги АУП',
    ): int
    {
        $rowCounter     = 1;
        $row            = $row + 3;
        $taxesResult    = [];

        foreach ($this->taxes as $taxID => $tax) {
            $this->sheet->setCellValue('A' . $row, $rowCounter++);
            $this->sheet->setCellValue('B' . $row, $tax['name'])->mergeCells('B' .$row . ':E' . $row);

            if (count($this->diffDates) > 0) {
                $iteration = 0;
                $total     = 0;

                foreach ($this->diffDates as $diffDate) {
                    $result = 0;

                    if (isset($this->report->{$salaryFundTotal}[$iteration]) && $this->report->{$salaryFundTotal}[$iteration] > 0) {
                        $result = DI::getDH()->getProject()->getFot()->createFormula(
                            $this->report->{$salaryFundTotal}[$iteration],
                            $tax,
                            $iteration,
                            $taxesResult,
                            $diffDate,
                            $countEmployees
                        );
                    }

                    $taxesResult[$taxID][] = $result;
                    $total                 = $total + $result;

                    if ($diffDate['firstDate'] && $diffDate['lastDate']) {
                        $taxesResult[$taxID][] = $total;
                        $total = 0;

                        ++$iteration;
                    }

                    ++$iteration;
                }
            }

            $row++;
        }

        $this->report->{$salaryFundTax} = DI::getDH()->getProject()->getSumColumns($taxesResult);
        $taxesResult[]                  = $this->report->{$salaryFundTax};

        $this->sheet->setCellValue('B' . $row, 'ИТОГО')->mergeCells('B'. $row . ':E' . $row);
        $this->sheet->fromArray($taxesResult, null, 'F' . ($row - count($taxesResult) + 1), true);

        $lastColl = $this->sheet->getCellByColumnAndRow(count(last($taxesResult)) + 5, $row)->getColumn();
        $lastCell = $this->sheet->getCellByColumnAndRow(count(last($taxesResult)) + 5, $row)->getCoordinate();
        $count    = $row - count($taxesResult);

        $this->setResultStyle('A' . $row . ':' . $lastCell);
        $this->setSubHeader($tableName, $lastColl, $count);
        $this->setBorders('A' . $count . ':' . $lastCell, Color::COLOR_BLACK);
        $this->sheet->getStyle('F' . $count . ':' . $lastCell)->applyFromArray([
            'numberFormat' => [
                'formatCode' => self::FORMAT_NUMBER
            ]
        ]);
        $this->setStyleByResultColumn($count, $count + count($taxesResult));
        $this->setAlignment('A' . $count . ':A' . $row);

        return $row;
    }

    /**
     * Заполнить таблицу значениями и установить стили
     *
     * @param  Collection $volumeSales
     * @param  array      $volumeSaleValues
     * @param  int        $startRow
     * @return $this
     */
    protected function setTableValues(
        Collection $volumeSales,
        array $volumeSaleValues = [],
        int $startRow = self::START_ROW
    ): self
    {
        $count = $volumeSales->count();

        $this->setAlignment('A'.($startRow - $count).':A'. $startRow);
        $this->setAlignment('C'.($startRow - $count).':E'. $startRow);

        $this->sheet->fromArray($volumeSaleValues, '0', 'F' . ($startRow - $count), true);

        $lastCell = $this->sheet->getCellByColumnAndRow(count(last($volumeSaleValues)) + 5, $startRow)->getCoordinate();

        $this->sheet->getStyle('C' . ($startRow - $count) . ':' . $lastCell)->applyFromArray([
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_RIGHT,
            ],
            'numberFormat' => [
                'formatCode' => self::FORMAT_NUMBER
            ]
        ]);

        return $this;
    }
}
