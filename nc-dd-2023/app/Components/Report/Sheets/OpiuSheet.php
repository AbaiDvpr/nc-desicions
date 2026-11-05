<?php declare(strict_types=1);

namespace App\Components\Report\Sheets;

use App\Components\Report\Helpers\Calculate;
use App\Components\Report\Sheets\Interfaces\SheetInterface;
use App\DI;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Color;
use PhpOffice\PhpSpreadsheet\Style\Fill;

/**
 * Лист ОПИУ
 */
class OpiuSheet extends AbstractReportSheet implements SheetInterface
{
    /**
     * Номер текущего листа
     */
    const CURRENT_SHEET = 7;

    /**
     * Данные по ФОТ и другим расходам
     *
     * @var array
     */
    protected $fotProceedsTable = [];

    /**
     * "Маржинальный доход" и "Маржа/выручка"
     *
     * @var array
     */
    protected $marginalIncome = [];

    /**
     * Таблица с выручкой
     *
     * @var array
     */
    protected $proceedsTable = [];

    /**
     * Данные по амортизации
     *
     * @var array
     */
    protected $depreciation = [];

    /**
     * Обслуживание долга из графика платежей по месяцам
     *
     * @var array
     */
    protected $getDebtService = [];

    /**
     * Текущий активный лист
     *
     * @var \PhpOffice\PhpSpreadsheet\Worksheet\Worksheet
     */
    protected $sheet;

    /**
     * Массив строк которые нужно преобразовать к формату процента
     *
     * @var array
     */
    protected $percentRow = [];

    /**
     * Массив с названиями элементов переменных расходов
     *
     * @var array
     */
    private array $variableCostNames = [];

    /**
     * Сумма переменных расходов по месяцам
     *
     * @var array
     */
    private array $variableCostTotalSum = [];

    /**
     * @inheritdoc
     *
     * @return \App\Components\Report\Sheets\OpiuSheet
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     */
    public function generate(): self
    {
        $this->setActiveSheet(self::CURRENT_SHEET);
        $this->sheet = $this->getActiveSheet();
        $count = 3;

        $this->setDatesToSheet('B1', 2, 1);

        $this->setProceedsTable();
        $fotNames            = DI::getDH()->getProject()->getIncome()->getNames($this->project->getUuid());
        $this->proceedsTable = $this->setNamesToProceedsTable(array_merge($fotNames, $this->variableCostNames));

        $this->sheet->fromArray($this->proceedsTable, null, 'A3', true);

        $count    = $count + count($this->proceedsTable) - 1;
        $lastCell = $this->sheet->getCellByColumnAndRow(count(head($this->proceedsTable)), $count)->getCoordinate();
        $this->percentRow[] = 'B' . $count . ':' . $lastCell;

        $fotWithExpensesWithoutNds = array_merge($this->report->opiuFotData, $this->report->expensesWithoutNds);
        $count                     = $this->incrementRow($count, 2);
        $this->sheet->fromArray($fotWithExpensesWithoutNds, null, 'A' . $count, true);

        $count  = $count + count($fotWithExpensesWithoutNds) + 1;
        $ebitda = $this->getEbitda();
        $this->sheet->fromArray($ebitda, null, 'A' . $count, true);

        $count    = $count + count($ebitda) - 1;
        $lastCell = $this->sheet->getCellByColumnAndRow(count(head($ebitda)), $count)->getCoordinate();
        $this->percentRow[] = 'B' . $count . ':' . $lastCell;

        $count = $this->incrementRow($count, 2);
        $this->depreciation = $this->getDepreciation($ebitda);
        $this->sheet->fromArray($this->depreciation, null, 'A' . $count, true);

        $count    = $count + count($this->depreciation) - 1;
        $lastCell = $this->sheet->getCellByColumnAndRow(count(last($this->depreciation)), $count)->getCoordinate();
        $this->percentRow[] = 'B' . $count . ':' . $lastCell;

        // Формируем массив с "Обслуживание долга" с общей суммой по годам
        $this->getDebtService();

        $count = $this->incrementRow($count, 2);

        $this->sheet->fromArray($this->getDebtService, null, 'A' . $count, true);

        $count    = $count + count($this->getDebtService) - 1;
        $lastCell = $this->sheet->getCellByColumnAndRow(count(head($this->getDebtService)), $count)->getCoordinate();
        $this->percentRow[] = 'B' . $count . ':' . $lastCell;

        $this->getRowIncomeTax();

        $count = $this->incrementRow($count, 2);
        $this->sheet->fromArray($this->report->incomeTax, null, 'A' . $count, true);
        $count = $count + count($this->report->incomeTax) + 1;

        $this->prepareNetProfit();

        $this->sheet->fromArray($this->report->netProfit, null, 'A' . $count, true);

        $count    = $count + count($this->report->netProfit) - 1;
        $lastCell = $this->sheet->getCellByColumnAndRow(count(head($this->report->netProfit)), $count)->getCoordinate();
        $this->percentRow[] = 'B' . $count . ':' . $lastCell;

        // При необходимости получаем дивиденды
        $this->getDividends();

        if (count($this->report->dividends) > 0) {
            $count = $count + 1;

            $this->report->dividends = $this->addNameToRow($this->report->dividends, 'Выплата дивидендов');

            $this->sheet->fromArray($this->report->dividends, null, 'A' . $count, true);
        }

        // Отчет о прибыли
        $count           = $count + 3;
        $incomeStatement = $this->getIncomeStatement();

        $this->sheet->fromArray($incomeStatement, null, 'A' . $count, true);

        $count = $count + count($incomeStatement);
        $lastCell           = $this->sheet->getCellByColumnAndRow(count(last($incomeStatement)), $count)->getCoordinate();

        $this->sheet->getStyle('B3:' . $lastCell)->applyFromArray([
            'numberFormat' => [
                'formatCode' => '#,##0'
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_RIGHT,
            ],
        ]);
        $this->sheet->getStyle('A3:A' . $count)->applyFromArray([
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_LEFT,
            ],
            'borders' => [
                'allBorders' => array(
                    'borderStyle' => Border::BORDER_THIN,
                    'color'       => [
                        'argb' => Color::COLOR_BLACK
                    ],
                ),
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'color'    => [
                    'argb' => 'FFEEEEEE'
                ]
            ],
        ]);

        // Устанавливаем стили для строк с процентами
        $this->setPercentageFormatRow();

        return $this;
    }

    /**
     * Добавляем данные для Выручки (1 секция данных)
     *
     * @return void
     */
    protected function setProceedsTable(): void
    {
        $dhProject = DI::getDH()->getProject();

        $this->report->proceedsTable['totalIncome'] = $this->report->totalIncome;
        $this->report->proceedsTable                = $dhProject->withoutNds(array_merge(
            $this->report->proceedsTable,
            $this->report->detailIncome
        ), !$this->project->isNds());

        $this->report->opiuIncome[] = $this->report->totalIncome;
        if (!is_null($this->project->getOtherIncome())) {
            $this->report->proceedsTable['otherIncome'] = $dhProject->withoutNds($this->getOtherIncomeTable(), !$this->project->isNds());
            $this->report->opiuIncome[]                 = $this->report->proceedsTable['otherIncome'];
        }

        if (count($this->report->opiuCostPrice) > 0) {
            $opiuCostPrice = collect($this->report->opiuCostPrice)->splice(3)->toArray();

            $this->report->proceedsTable['opiuCostPrice'] = $dhProject->withoutNds([$opiuCostPrice], !$this->project->isNds())[0];
        }

        $payrollFundPP = DI::getDH()->getProject()->getFot()->payrollFundPP(
            $this->report->vpSalaryFundTotal,
            $this->report->vpSalaryFundTaxes
        );

        if (!empty($payrollFundPP)) {
            $this->report->proceedsTable = array_merge(
                $this->report->proceedsTable,
                $payrollFundPP
            );
        }

        $this->attachVariableCosts();

        // перенести сюда Переменные расходы

        $this->marginalIncome                  = $this->getMarginalIncome();
        $this->report->proceedsTable['income'] = $this->marginalIncome['income'];
        $this->report->proceedsTable['margin'] = $this->marginalIncome['margin'];
    }

    /**
     * Получаем данные по "Дополнительный доход" с учетом инфляции
     *
     * @return array
     */
    private function getOtherIncomeTable(): array
    {
        $table = [];

        if (count($this->diffDates) > 0) {
            $i     = 0;
            $total = 0;
            foreach ($this->diffDates as $diffDate) {
                $price = $this->getPriceByInflation($diffDate, $this->project->getOtherIncome());

                $table[] = $price;
                $total   = $price + $total;

                if ($diffDate['firstDate'] && $diffDate['lastDate']) {
                    $table[] = $total;
                    $total   = 0;
                    ++$i;
                }
                ++$i;
            }
        }

        return $table;
    }

    /**
     * Получение данных для строк "Маржинальный доход" и "Маржа/выручка"
     *
     * @return array
     */
    private function getMarginalIncome(): array
    {
        $income = [];
        $margin = [];

        $otherIncome = 0;
        for ($i = 0; $i < count($this->report->proceedsTable['totalIncome']); $i++) {
            $totalIncome   = $this->report->proceedsTable['totalIncome'][$i];
            $opiuCostPrice = $this->report->proceedsTable['opiuCostPrice'][$i] ?? 0;
            $payrollFundPP = 0;
            $variableCost  = $this->variableCostTotalSum[$i] ?? 0;

            if (isset($this->report->proceedsTable['otherIncome'])) {
                $otherIncome = $this->report->proceedsTable['otherIncome'][$i];
            }

            if (isset($this->report->proceedsTable['payrollFundPP'])) {
                $payrollFundPP = $this->report->proceedsTable['payrollFundPP'][$i] ?? 0;
            }

            $income[$i] = $totalIncome + $otherIncome - $opiuCostPrice - $payrollFundPP - $variableCost;
            $margin[$i] = ($totalIncome > 0) ? $income[$i] / $this->getTotalAndAdditionalIncome($i) : 0;
        }

        return [
            'income' => $income,
            'margin' => $margin, // Маржа/Выручка
        ];
    }

    /**
     * Получение значения EBITDA
     *
     * @return array
     */
    private function getEbitda(): array
    {
        $fotData = DI::getDH()->getProject()->getSumColumns($this->removeNamedColumns(array_merge(
            $this->report->opiuFotData,
            $this->report->expensesWithoutNds
        )));

        $this->report->ebitda['EBITDA'] = collect($this->marginalIncome['income'])
            ->map(function ($income, $index) use ($fotData) {
                return $income - ($fotData[$index] ?? 0);
            })->toArray();

        $this->report->ebitda['PROCEEDS'] = collect($this->report->ebitda['EBITDA'])->map(function ($ebitda, $index) {
            $totalIncome = $this->getTotalAndAdditionalIncome($index);

            if (in_array(0, [$ebitda, $totalIncome])) {
                return 0;
            }

            return $ebitda / $totalIncome;
        })->toArray();

        array_unshift($this->report->ebitda['EBITDA'], 'EBITDA');
        array_unshift($this->report->ebitda['PROCEEDS'], 'EBITDA/выручка');

        return $this->report->ebitda;
    }

    /**
     * Возвращает данные для амортизации (4 секция)
     *
     * @param  array $ebitda
     * @return array
     */
    private function getDepreciation(array $ebitda = []): array
    {
        $ebit = collect($ebitda['EBITDA'])->forget(0)->values()->map(function ($ebt, $index) {
            return $ebt - ($this->report->amortization[$index] ?? 0);
        })->toArray();

        $proceeds = collect($ebit)->map(function ($ebit, $index) {
            $totalIncome = $this->getTotalAndAdditionalIncome($index);

            if (in_array(0, [$ebit, $totalIncome])) {
                return 0;
            }

            return $ebit / $totalIncome;
        })->toArray();

        return [
            'AMORTIZATION' => Arr::collapse([['Амортизация'], $this->report->amortization]),
            'EBIT'         => Arr::collapse([['EBIT'], $ebit]),
            'PROCEEDS'     => Arr::collapse([['EBIT/выручка'], $proceeds]),
        ];
    }

    /**
     * Добавляет названия строк к данным по выручке и марже
     *
     * @param  array $incomeNames
     * @return array
     */
    private function setNamesToProceedsTable(array $incomeNames = []): array
    {
        $opiuRowNames = array_merge([
            'totalIncome'        => 'Выручка',
            'otherIncome'        => 'Дополнительный доход',
            'opiuCostPrice'      => 'Себестоимость',
            'payrollFundPP'      => 'ФОТ ПП итого',
            'payrollFundPPTotal' => '     ФОТ ПП',
            'payrollFundPPTaxes' => '     ФОТ ПП налоги',
            'income'             => 'Маржинальный доход',
            'margin'             => 'Маржа/выручка',
        ], $incomeNames);

        if (count($this->report->opiuCostPrice) === 0) {
            unset($opiuRowNames['opiuCostPrice']);
        }

        return collect($this->report->proceedsTable)->map(function ($item, $index) use ($opiuRowNames) {
            return array_merge(
                [$opiuRowNames[$index] ?? 'No name row'],
                $item
            );
        })->toArray();
    }

    /**
     * Формирует массив "Обслуживание долга"
     *
     * @return void
     */
    private function getDebtService(): void
    {
        $graphData = $this->summarizeGraphsPayments('D');

        if (!empty($graphData)) {
            $totalByYear      = 0;
            $getDebtService[] = 'Обслуживание долга';

            foreach ($this->diffDates as $diffDate) {
                $scheduleByMonth = 0;

                if (Arr::exists($graphData, $diffDate['start'])) {
                    $scheduleByMonth = $graphData[$diffDate['start']]['D'];
                }

                $totalByYear      = $totalByYear + $scheduleByMonth;
                $getDebtService[] = $scheduleByMonth;

                if ($diffDate['firstDate'] && $diffDate['lastDate']) {
                    $getDebtService[] = $totalByYear;

                    $totalByYear = 0;
                }
            }

            $this->getDebtService['debt'] = $getDebtService;
        }

        $this->getProfitBeforeTax();
    }

    /**
     * Добавляет к "Обслуживанию долга" следующие строки:
     * - Прибыль до налогообложения (EBT)
     * - EBT/выручка
     *
     * @return void
     */
    private function getProfitBeforeTax(): void
    {
        if (Arr::exists($this->depreciation, 'EBIT')) {
            $depreciationEbits = collect($this->depreciation['EBIT'])->splice(1)->values()->toArray();
            $proceedsTable     = $this->removeNamedColumns($this->proceedsTable);
            $getDebtService    = $this->removeNamedColumns($this->getDebtService);
            $debt              = array_key_exists('debt', $getDebtService) ? $getDebtService['debt'] : [];

            foreach ($depreciationEbits as $item => $depreciationEbit) {
                $beforeTax = $depreciationEbit - ($debt[$item] ?? 0);

                $this->getDebtService['beforeTax'][$item] = $beforeTax;

                if ($beforeTax > 0) {
                    $totalIncome = $proceedsTable['totalIncome'][$item] ?? 1;

                    $this->getDebtService['proceeds'][$item] = $beforeTax / ($totalIncome >= 1 ? $totalIncome : 1);
                } else {
                    $this->getDebtService['proceeds'][$item] = 0;
                }
            }

            $this->getDebtService['beforeTax'] = $this->addNameToRow(
                $this->getDebtService['beforeTax'] ?? [],
                'Прибыль до налогообложения (EBT)'
            );
            $this->getDebtService['proceeds']  = $this->addNameToRow($this->getDebtService['proceeds'] ?? [], 'EBT/выручка');
        }
    }

    /**
     * Формирует строку "Налог на прибыль"
     *
     * @return void
     */
    private function getRowIncomeTax(): void
    {
        $beforeTax = $this->removeNamedColumns([$this->getDebtService['beforeTax']])[0] ?? [];
        $calculate = new Calculate($this->project);
        $percent   = $calculate->calculateTax();
        $incomeTax = [];

        if (!empty($beforeTax)) {
            $index = 0;
            foreach ($this->diffDates as $diffDate) {
                $currentYear = Carbon::parse($diffDate['start'])->year;

                if ($currentYear >= $calculate->calculateYearForTax()) {
                    if (isset($beforeTax[$index]) && $beforeTax[$index] > 0) {
                        $incomeTax[$index] = $beforeTax[$index] * $percent;
                    } else {
                        $incomeTax[$index] = 0;
                    }

                    if ($diffDate['firstDate'] && $diffDate['lastDate']) {
                        $index++;

                        if (isset($beforeTax[$index]) && $beforeTax[$index] > 0) {
                            $incomeTax[$index] = $beforeTax[$index] * $percent;
                        } else {
                            $incomeTax[$index] = 0;
                        }
                    }
                } else {
                    $incomeTax[$index] = 0;

                    if ($diffDate['firstDate'] && $diffDate['lastDate']) {
                        $index++;

                        $incomeTax[$index] = 0;
                    }
                }

                $index++;
            }

            if (count($incomeTax) > 0) {
                $this->report->incomeTax[] = $this->addNameToRow($incomeTax, 'Налог на прибыль');
            }
        }
    }

    /**
     * Собирает данные по "Чистая прибыль" и "Чистая прибыль/выручка"
     */
    private function prepareNetProfit(): void
    {
        if (empty($this->report->incomeTax)) {
            return;
        }

        $debtService = $this->removeNamedColumns($this->getDebtService)['beforeTax'] ?? [];
        $incomeTax   = $this->removeNamedColumns([$this->report->incomeTax[0]])[0] ?? [];

        if (!empty($debtService) && !empty($incomeTax)) {
            foreach ($debtService as $key => $value) {
                $profit = $value - ($incomeTax[$key] ?? 0);
                $this->report->netProfit['PROFIT'][$key] = $profit;

                $totalIncome = $this->getTotalAndAdditionalIncome($key);

                if ($profit > 0 && $totalIncome > 0) {
                    $this->report->netProfit['PERCENT'][$key] = $profit / $totalIncome;
                } else {
                    $this->report->netProfit['PERCENT'][$key] = 0;
                }
            }

            $this->report->netProfit['PROFIT']  = $this->addNameToRow($this->report->netProfit['PROFIT'], 'Чистая прибыль');
            $this->report->netProfit['PERCENT'] = $this->addNameToRow($this->report->netProfit['PERCENT'], 'Чистая прибыль/выручка');
        }
    }

    /**
     * Устанавливает формат ячеек процента для строк
     *
     * @return void
     */
    private function setPercentageFormatRow(): void
    {
        if (count($this->percentRow) > 0) {
            foreach ($this->percentRow as $row) {
                $this->setPercentFormat($row);
            }
        }
    }

    /**
     * Получение дивидендов
     *
     * @return void
     */
    protected function getDividends(): void
    {
        $investment = $this->project->attractingInvestment;

        if (!is_null($investment) && $this->project->isDividends()) {
            $maxDividendsDate = $this->getMaxDividendsDate($investment->investorTerm);
            $profit           = $this->removeNamedColumns([$this->report->netProfit['PROFIT']])[0];
            $percent          = $investment->investorDividend / 100 + 1;

            if (count($this->diffDates) > 0) {
                $i     = 0;
                $total = 0;

                foreach ($this->diffDates as $diffDate) {
                    if ($maxDividendsDate->lte(Carbon::parse($diffDate['end']))) {
                        $sum = 0;
                    } else {
                        $sum = ($profit[$i] * $percent) - $profit[$i];
                    }

                    $this->report->dividends[] = $sum;
                    $total                     = $sum + $total;

                    if ($diffDate['isLast']) {
                        $this->report->dividends[] = $total;
                        $total                     = 0;

                        ++$i;
                    }
                    ++$i;
                }
            }
        }
    }

    /**
     * Добавить переменные расходы на лист
     *
     * @return void
     */
    protected function attachVariableCosts(): void
    {
        $variableCosts = collect($this->report->variableCosts);

        if ($variableCosts->count() > 0) {
            $items = $variableCosts->mapWithKeys(function ($item, $key) {
                return ['vc:' . $key => $item];
            })->toArray();
            $items = $this->removeNamedColumns($items);

            $this->variableCostTotalSum = DI::getDH()->getProject()->getSumColumns($items);

            // добавляем строку с итоговой суммой переменных расходов
            $this->report->proceedsTable = array_merge(
                $this->report->proceedsTable,
                ['vc:total' => $this->variableCostTotalSum],
                $items
            );

            // имена строк с переменными расходами
            $this->variableCostNames = array_merge(
                ['vc:total' => 'Переменные расходы'],
                $variableCosts->mapWithKeys(function ($item, $key) {
                    return ['vc:' . $key => '     ' . $item[0]];
                })->toArray()
            );
        }
    }

    /**
     * Возвращает сумму основной выручи и "Прочие доходы"
     *
     * @param  int       $index
     * @return float|int
     */
    protected function getTotalAndAdditionalIncome(int $index): float|int
    {
        $proceedsTable = $this->report->proceedsTable;
        $total         = $proceedsTable['totalIncome'][$index] ?? 0;
        $otherIncome   = $proceedsTable['otherIncome'][$index] ?? 0;

        return $total + $otherIncome;
    }

    /**
     * Собирает данные по отчету о прибыли
     *
     * @return array
     */
    protected function getIncomeStatement(): array
    {
        $incomeTax               = $this->report->incomeTax;
        $dhOpiu                  = DI::getDH()->getProject()->getOpiu();
        $proceedsTable           = $this->removeNamedColumns([$this->proceedsTable['totalIncome']])[0] ?? [];
        $amortization            = $this->report->amortization;
        $incomeTax               = !empty($incomeTax) ? $this->removeNamedColumns([$this->report->incomeTax[0]])[0] : [];
        $generalAndAdminExpenses = $dhOpiu->getGeneralAndAdministrativeExpenses(
            $this->report->opiuFotData,
            $this->report->expensesWithoutNds
        );

        // Суммируем в выручку и прочие доходы
        for ($i = 0; $i < count($proceedsTable); $i++) {
            $proceedsTable[$i] = $this->getTotalAndAdditionalIncome($i);
        }

        // Считаем себестоимость
        $costPrice = DI::getDH()->getProject()->getSumColumns([
            $this->report->proceedsTable['opiuCostPrice'] ?? [],
            $this->report->proceedsTable['vc:total'] ?? [],
            $this->report->proceedsTable['payrollFundPP'] ?? [],
        ]);

        $debtServicing       = [];
        $expenses            = DI::getDH()->getProject()->getSumColumns([
            $costPrice,
            $generalAndAdminExpenses,
            $amortization
        ]);
        $profitTaxesInterest = $dhOpiu->getDifference($proceedsTable, $expenses);

        if (array_key_exists('debt', $this->getDebtService)) {
            $debtServicing = $this->removeNamedColumns([$this->getDebtService['debt']])[0];
        }

        $profitBeforeTaxes                   = $dhOpiu->getDifference($profitTaxesInterest, $debtServicing);
        $this->report->incomeStatementProfit = $dhOpiu->getDifference($profitBeforeTaxes, $incomeTax);

        return [
            'incomeStatement'         => ['Отчет о прибыли'],
            'revenueProductSales'     => $this->addNameToRow($proceedsTable, 'Выручка от реализации продукции'),
            'costPrice'               => $this->addNameToRow($costPrice, 'Себестоимость'),
            'generalAndAdminExpenses' => $this->addNameToRow($generalAndAdminExpenses, 'ОиАР'),
            'amortization'            => $this->addNameToRow($amortization, 'Амортизация'),
            'profitTaxesInterest'     => $this->addNameToRow($profitTaxesInterest, 'Прибыль до налогов и процентов'),
            'financeIncomeExpenses'   => $this->addNameToRow($debtServicing, 'Финансовые доходы/расходы'),
            'otherIncomeExpenses'     => ['Прочие доходы/расходы'],
            'exchangeDifference'      => ['Курсовая разница'],
            'profitBeforeTaxes'       => $this->addNameToRow($profitBeforeTaxes, 'Прибыль до налогов'),
            'incomeTax'               => $this->addNameToRow($incomeTax, 'Налог на прибыль'),
            'profit'                  => $this->addNameToRow($this->report->incomeStatementProfit, 'Чистая прибыль'),
        ];
    }
}
