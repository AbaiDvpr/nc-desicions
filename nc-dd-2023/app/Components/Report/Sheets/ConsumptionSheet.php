<?php declare(strict_types=1);

namespace App\Components\Report\Sheets;

use App\Components\Report\Sheets\Interfaces\SheetInterface;
use App\DI;
use Carbon\Carbon;
use PhpOffice\PhpSpreadsheet\Style\Color;

/**
 * Класс для генерации листа "Расход"
 */
class ConsumptionSheet extends AbstractReportSheet implements SheetInterface
{
    /**
     * Номер текущего листа
     */
    const CURRENT_SHEET = 6;

    /**
     * Номер строки с которой необходимо начать заполнение
     */
    const START_ROW = 5;

    /**
     * Данные для вставки в таблицу
     *
     * @var array
     */
    protected $tableData = [];

    /**
     * Текущий активный лист
     *
     * @var \PhpOffice\PhpSpreadsheet\Worksheet\Worksheet
     */
    protected $sheet;

    /**
     * @inheritdoc
     *
     * @return \App\Components\Report\Sheets\ConsumptionSheet
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     */
    public function generate(): self
    {
        $this->setActiveSheet(self::CURRENT_SHEET);
        $this->setDatesToSheet('D2');

        $this->sheet = $this->getActiveSheet();
        $volumeSales = DI::getRepository()->getProject()->getVolumeSales($this->project->getUuid())->getAll();

        if ($volumeSales->count() > 0) {
            $this->addVolumeSalesToTable($volumeSales);
            $this->addFotToTable();
            $this->addExpensesToTable(
                DI::getRepository()->getProject()->getAdministrativeExpenses($this->project->getUuid())
            );
            $this->addExpensesToTable(
                DI::getRepository()->getProject()->getMarketingExpenses($this->project->getUuid())
            );
            $this->addVariableCostsOtherToTable();

            $tableData = $this->extractColData();
            $table     = collect($this->extractColNames())->map(function ($row, $i) use ($tableData) {
                return collect($row)->merge($tableData[$i])->toArray();
            })->toArray();

            $this->sheet->fromArray($table, null, 'A' . self::START_ROW, true);

            $this->applyStyleToSheet($table);
        }

        return $this;
    }

    /**
     * Добавить в таблицу данные по объему продаж с дисконтированием
     *
     * @param $volumeSales
     * @return bool|void
     */
    protected function addVolumeSalesToTable($volumeSales)
    {
        // Если не заполнена таблица с себестоимостью товара, то пропускаем этот шаг вообще
        if (count($this->report->costPriceTotal) === 0) {
            $this->report->opiuCostPrice = [];

            return true;
        }

        $salesStartDate = Carbon::parse($this->project->salesStartDate);

        foreach ($volumeSales as $index => $volumeSale) {
            $this->tableData[$index] = [
                ++$index,
                'Себестоимость ' . $volumeSale->name,
                $this->project->getCurrencyValue(),
            ];

            if (count($this->diffDates) > 0) {
                $i     = 0;
                $total = 0;
                foreach ($this->diffDates as $diffDate) {
                    $price = 0;

                    if ($salesStartDate->lte(Carbon::parse($diffDate['start']))) {
                        $price = $this->report->volumeSales[$index][$i] * ($this->report->costPriceTotal[$volumeSale->id] ?? 1);
                        $price = $this->getPriceByInflation($diffDate, $price);
                    }

                    $this->tableData[$index][] = $price;
                    $total                     = $price + $total;

                    if ($diffDate['firstDate'] && $diffDate['lastDate']) {
                        $this->tableData[$index][] = $total;
                        $total                     = 0;
                        ++$i;
                    }
                    ++$i;
                }
            }
        }

        $this->report->opiuCostPrice = DI::getDH()->getProject()->getSumColumns($this->tableData);
    }

    /**
     * Формирует строки по данным из "Переменные расходы"
     *
     * @return void
     */
    protected function addVariableCostsOtherToTable(): void
    {
        $records        = $this->project->variableCostsOther;
        $index          = count($this->tableData);
        $salesStartDate = Carbon::parse($this->project->salesStartDate);

        if (!is_null($records) && $records->count() > 0) {
            foreach ($records as $record) {
                $this->tableData[] = [
                    ++$index,
                    $record->title,
                    $this->project->getCurrencyValue(),
                ];

                if (count($this->diffDates) > 0) {
                    $i              = 0;
                    $total          = 0;
                    $indexForPeriod = 0;

                    foreach ($this->diffDates as $diffDate) {
                        $currentDate = Carbon::parse($diffDate['start']);
                        $price       = $this->getPriceByInflation(
                                $diffDate,
                                $this->getExpensesPriceWithInvestPeriod($diffDate, $record)
                            ) * ($diffDate['percent'] > 0 ? $diffDate['percent'] : 1);

                        $price = $this->getPriceWithPeriod($indexForPeriod, $record, $price);

                        $this->tableData[$index][] = $price;
                        $total                     = $price + $total;

                        if ($diffDate['firstDate'] && $diffDate['lastDate']) {
                            $this->tableData[$index][] = $total;
                            $total                     = 0;
                            ++$i;
                        }
                        ++$i;

                        $indexForPeriod++;

                        if (!$record->invest && $salesStartDate->gt($currentDate)) {
                            $indexForPeriod = 0;
                        }
                    }
                }

                // Заполняем данные по переменным расходам
                $this->setVariableCosts(array_merge(
                    [$record->title],
                    DI::getDH()->getProject()->withoutNds([
                        collect($this->tableData[$index])->splice(3)->toArray(),
                    ], !$this->project->isNds())[0]
                ));

                $this->report->expensesWithNds[] = array_merge(
                    [$record->title],
                    collect($this->tableData[$index])->splice(3)->toArray()
                );
            }
        }
    }

    /**
     * Добавить в таблицу с результатами, данные по налогам
     */
    protected function addFotToTable(): void
    {
        $salaryFundTotal = $this->report->salaryFundTotal;
        $salaryFundTaxes = $this->report->salaryFundTaxes;

        if (count($salaryFundTotal) > 0) {
            $this->tableData[] = array_merge([
                count($this->tableData) + 1,
                'ФОТ',
                $this->project->getCurrencyValue(),
            ], DI::getDH()->getProject()->getSumColumns([
                $this->report->salaryFundTotal ?? [],
                $this->report->vpSalaryFundTotal ?? [],
            ]));

            $this->report->opiuFotData[] = array_merge(['ФОТ АУП'], $this->report->salaryFundTotal ?? []);
        }

        if (count($salaryFundTaxes) > 0) {
            $this->tableData[] = array_merge([
                count($this->tableData) + 1,
                'ФОТ Налоги',
                $this->project->getCurrencyValue(),
            ], DI::getDH()->getProject()->getSumColumns([
                $this->report->salaryFundTaxes ?? [],
                $this->report->vpSalaryFundTaxes ?? [],
            ]));

            $this->report->opiuFotData[] = array_merge(['ФОТ АУП Налоги'], $this->report->salaryFundTaxes ?? []);
        }
    }

    /**
     * Добавить в таблицу данные по расходам
     *
     * @param $expenses
     */
    protected function addExpensesToTable($expenses)
    {
        $iteration      = count($this->tableData) + 1;
        $salesStartDate = Carbon::parse($this->project->salesStartDate);

        if ($expenses->count() > 0) {
            foreach ($expenses as $exp) {
                $this->tableData[$iteration] = [
                    $iteration,
                    $exp->name,
                    $this->project->getCurrencyValue(),
                ];

                if (count($this->diffDates) > 0) {
                    $costData       = [];
                    $total          = 0;
                    $indexForPeriod = 0;

                    foreach ($this->diffDates as $diffDate) {
                        $currentDate = Carbon::parse($diffDate['start']);
                        $price       = $this->getExpensesPriceWithInvestPeriod($diffDate, $exp);
                        $result      = $this->getPriceByInflation($diffDate, $price);

                        $result = $this->getPriceWithPeriod($indexForPeriod, $exp, $result);

                        $costData[] = $result;
                        $total      = $total + $result;

                        if ($diffDate['firstDate'] && $diffDate['lastDate']) {
                            $costData[] = $total;
                            $total      = 0;
                        }

                        $indexForPeriod++;

                        if (!$exp->invest && $salesStartDate->gt($currentDate)) {
                            $indexForPeriod = 0;
                        }
                    }

                    $this->tableData[$iteration] = array_merge($this->tableData[$iteration], $costData);
                }

                $this->report->expensesWithoutNds[] = array_merge(
                    [$exp->name],
                    DI::getDH()->getProject()->withoutNds([
                        collect($this->tableData[$iteration])->splice(3)->toArray(),
                    ], !$this->project->isNds())[0]
                );

                $this->report->expensesWithNds[] = array_merge(
                    [$exp->name],
                    collect($this->tableData[$iteration])->splice(3)->toArray()
                );

                ++$iteration;
            }
        }
    }

    /**
     * Отсекает у итоговой таблицы, первые столбцы в которых указаны названия строк
     * и добавляет строку с результатами
     *
     * @return array
     */
    protected function extractColData(): array
    {
        $table = collect($this->tableData)->map(function ($row) {
            return collect($row)->splice(3)->toArray();
        })->toArray();

        array_push($table, DI::getDH()->getProject()->getSumColumns($table));

        return $table;
    }

    /**
     * Отсекает у итоговой таблицы, первые столбцы в которых указаны названия строк
     * и добавляет строку с итогами
     *
     * @return array
     */
    protected function extractColNames(): array
    {
        return collect($this->tableData)->map(function ($row) {
            return collect($row)->splice(0, 3)->toArray();
        })->push(['Всего расходы', null, 'тенге'])->toArray();
    }

    /**
     * Применяет набор стилей к текущей таблицы
     *
     * @param array $table
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     */
    private function applyStyleToSheet(array $table = [])
    {
        $count    = count($table) + self::START_ROW - 1;
        $lastColl = $this->sheet->getCellByColumnAndRow(count(head($table)), $count)->getColumn();
        $lastCell = $this->sheet->getCellByColumnAndRow(count(head($table)), $count)->getCoordinate();

        $this->setSubHeader('Расходы', $lastColl, self::START_ROW - 1);
        $this->setBorders('A' . self::START_ROW . ':' . $lastCell, Color::COLOR_BLACK);
        $this->setResultStyle('A' . $count . ':' . $lastCell);
        $this->setAlignment('A' . self::START_ROW . ':A' . $count);
        $this->setAlignment('C' . self::START_ROW . ':C' . $count);
        $this->setStyleByResultColumn(self::START_ROW, $count);
        $this->sheet->getStyle('D' . self::START_ROW . ':' . $lastCell)->applyFromArray([
            'numberFormat' => [
                'formatCode' => self::FORMAT_NUMBER,
            ],
        ]);
        $this->sheet->mergeCells('A' . $count . ':B' . $count);
    }

    /**
     * Возвращает сумму расходов с учетом инвест периода
     * Используется только при получении административных расходов
     *
     * @param array                                      $diffDate
     * @param \App\Models\Project\AdministrativeExpenses $exp
     * @return int|mixed
     */
    private function getExpensesPriceWithInvestPeriod(array $diffDate, $exp)
    {
        $diProject = DI::getRepository()->getProject();
        $project   = $diProject->getOneByUuid($this->project->getUuid());

        if (is_null($project)) {
            return 0;
        }

        $withInvest     = $diProject->withInvestPeriod($project->getUuid());
        $salesStartDate = Carbon::parse($project->salesStartDate);
        $currentDate    = Carbon::parse($diffDate['start']);

        if ($withInvest) {
            if ($exp->invest) {
                return $exp->price;
            }

            if ($currentDate->lt($salesStartDate)) {
                return 0;
            }
        }

        return $exp->price;
    }

    /**
     * Получение суммы в зависимости от периода затрат
     *
     * @param int    $index
     * @param        $exp
     * @param float  $price
     * @return float
     */
    private function getPriceWithPeriod(int $index, $exp, float $price): float
    {
        // если период равен 0, то это единовременная выплата
        if ($exp->period == 0) {
            return $index == 0 ? $price : 0;
        }

        return ($index % $exp->period == 0) ? $price : 0;
    }
}
