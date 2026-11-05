<?php declare(strict_types=1);

namespace App\Components\Report\Sheets;

use App\Components\Report\Sheets\Interfaces\SheetInterface;
use App\DI;
use Illuminate\Support\Arr;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

/**
 * Заполнение данными листа "ДДС"
 *
 * TODO: Вынести все переводы в отдельный файл и не писать их где попало
 */
class DdsSheet extends AbstractReportSheet implements SheetInterface
{
    /**
     * Номер текущего листа
     */
    const CURRENT_SHEET = 8;

    /**
     * @var array
     */
    private array $opiuIncome = [];

    /**
     * Выплата вознаграждений из графика платежей по месяцам
     *
     * @var array
     */
    protected array $paymentRemuneration = [];

    /**
     * Изменение в оборотном капитале
     *
     * @var array
     */
    protected array $changeInWorkingCapital = [];

    /**
     * Денежные средства до и после
     *
     * @var array
     */
    protected array $cashBeforeAfter = [];

    /**
     * Текущий активный лист
     *
     * @var \PhpOffice\PhpSpreadsheet\Worksheet\Worksheet
     */
    protected $sheet;

    /**
     * @inheritdoc
     *
     * @return \App\Components\Report\Sheets\DdsSheet
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     */
    public function generate(): self
    {
        $this->setActiveSheet(self::CURRENT_SHEET);
        $this->sheet = $this->getActiveSheet();

        $this->setDatesToSheet('B1', 2, 1);

        $this->getOpiuIncome();
        $lastColumn = $this->sheet->getCellByColumnAndRow(count(head($this->opiuIncome)), 3)->getColumn();

        $this->setSubHeader('Движение денежных средств от операционной деятельности', $lastColumn, 3, 'empty');
        $this->setSubHeader('Притоки по операционной деятельности', $lastColumn, 4);

        $this->sheet->fromArray($this->opiuIncome, null, 'A5', true);

        $this->setSubHeader('Оттоки по операционной деятельности', $lastColumn, count($this->opiuIncome) + 6);

        $drainRow = count($this->opiuIncome) + 7;

        if (
            array_key_exists('opiuCostPrice', $this->report->proceedsTable) &&
            !empty($this->report->proceedsTable['opiuCostPrice'])
        ) {
            $this->report->operatingActivities[] = array_merge(
                ['Платежи поставщикам за товары и услуги'],
                $this->report->proceedsTable['opiuCostPrice']
            );
        }

        $paymentRemuneration = $this->getPaymentRemuneration('D', 'Выплата вознаграждений');

        if (count($paymentRemuneration)) {
            $this->paymentRemuneration[] = $paymentRemuneration;
        }

        $this->report->operatingActivities = Arr::collapse([
            $this->report->operatingActivities,
            array_merge(
                $this->report->opiuFotData,
                array_merge([
                    $this->addNameToRow($this->report->vpSalaryFundTotal, 'ФОТ ПП'),
                    $this->addNameToRow($this->report->vpSalaryFundTaxes, 'ФОТ ПП Налоги')
                ]),
                $this->report->expensesWithNds
            ),
            $this->paymentRemuneration,
            $this->report->incomeTax
        ]);

        // Добавляем строки с итогами
        $this->addTotalsToOperatingActivities();

        $this->sheet->fromArray($this->report->operatingActivities, null, 'A' . $drainRow, true);

        $lastRow = $drainRow + count($this->report->operatingActivities);

        $lastCell = $this->sheet->getCellByColumnAndRow(
            count(head($this->report->operatingActivities)),
            $drainRow + count($this->report->operatingActivities)
        )->getCoordinate();

        $lastRow = $this->investmentActivities($lastCell, $lastRow + 2);

        $lastRow = $this->financialActivities($lastCell, $lastRow + 2);
        $lastRow = $this->financialOutflows($lastCell, $lastRow + 2);

        $lastRow = $this->prepareTurnoverTable($lastCell, $lastRow + 1);

        $this->balanceInHand($lastCell, $lastRow + 1);

        $lastRow = $lastRow + 5;

        $cashFlowStatement = $this->getCashFlowStatement();
        $this->sheet->fromArray($cashFlowStatement, null, 'A' . $lastRow, true);

        $lastRow  = $lastRow + count($cashFlowStatement);
        $lastCell = $this->sheet->getCellByColumnAndRow(count(last($cashFlowStatement)), $lastRow)
            ->getCoordinate();

        $this->sheet->getStyle('B3:' . $lastCell)->applyFromArray([
            'numberFormat' => [
                'formatCode' => '#,##0',
            ],
            'alignment'    => [
                'horizontal' => Alignment::HORIZONTAL_RIGHT,
            ],
        ]);
        return $this;
    }

    /**
     * Собирает данные по выручке и дополнительному доходу для "ДДС"
     *
     * @return void
     */
    private function getOpiuIncome(): void
    {
        $dhProject          = DI::getDH()->getProject();
        $this->opiuIncome[] = $this->addNameToRow($this->report->opiuIncome[0], 'Поступления');
        $names              = $dhProject->getIncome()->getNames($this->project->getUuid());

        foreach ($names as $id => $fotName) {
            $this->opiuIncome[] = array_merge(
                [$fotName],
                $this->report->detailIncome[$id]
            );
        }

        if (isset($this->report->opiuIncome[1])) {
            $this->opiuIncome[] = $this->addNameToRow($this->report->opiuIncome[1] ?? [], 'Дополнительный доход');
        }

        $this->report->opiuIncomeTotal = $dhProject->getSumColumns($this->report->opiuIncome);

        $this->opiuIncome[] = $this->addNameToRow($this->report->opiuIncomeTotal, 'Поступление денежных средств от реализации продукции и оказания услуг');
    }

    /**
     * Добавляет "Итого" и "Итого денежный поток по операционной деятельности"
     * к данным по "Оттоки по операционной деятельности"
     */
    private function addTotalsToOperatingActivities(): void
    {
        $operatingActivities                    = $this->removeNamedColumns($this->report->operatingActivities);
        $totalOpiuIncome                        = last($this->removeNamedColumns($this->opiuIncome));
        $operatingActivitiesTotal               = DI::getDH()->getProject()->getSumColumns($operatingActivities);
        $this->report->operatingActivitiesTotal = DI::getDH()->getProject()->getSumColumns($operatingActivities);
        $this->report->operatingActivities[]    = $this->addNameToRow(
            $this->report->operatingActivitiesTotal,
            'Выбытие денежных средств, всего'
        );

        $this->report->operatingActivities[] = collect($totalOpiuIncome)
            ->map(function ($total, $index) use ($operatingActivitiesTotal) {
                return $total - $operatingActivitiesTotal[$index];
            })->prepend('Итого денежный поток по операционной деятельности')->values()->toArray();
    }

    /**
     * Формирует массив "Выплата вознаграждений"
     *
     * @param  string $column
     * @param  string $title
     * @return array
     */
    private function getPaymentRemuneration(string $column = 'D', string $title = ''): array
    {
        $graphData = $this->summarizeGraphsPayments($column);

        if (!empty($graphData)) {
            $totalByYear      = 0;
            $getDebtService[] = $title;

            foreach ($this->diffDates as $diffDate) {
                $scheduleByMonth = 0;

                if (Arr::exists($graphData, $diffDate['start'])) {
                    $scheduleByMonth = $graphData[$diffDate['start']][$column];
                }

                $totalByYear      = $totalByYear + $scheduleByMonth;
                $getDebtService[] = $scheduleByMonth;

                if ($diffDate['firstDate'] && $diffDate['lastDate']) {
                    $getDebtService[] = $totalByYear;

                    $totalByYear = 0;
                }
            }

            return $getDebtService;
        }

        return [];
    }

    /**
     * Собирает данные по "Инвестиционная деятельность"
     * в частности формирует "Оттоки от инвестиционной деятельности" вместе с итогами
     *
     * @param  string                              $lastCell
     * @param  int                                 $row
     * @return int
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     */
    protected function investmentActivities(string $lastCell, int $row): int
    {
        if (!array_key_exists('grant', $this->report->financingTotal)) {
            return $row - 1;
        }

        $column = $this->sheet->getCell($lastCell)->getColumn();

        $this->setSubHeader('Движение денежных средств от инвестиционной деятельности', $column, $row, 'empty');
        $row = $row + 1;
        $this->setSubHeader('Оттоки от инвестиционной деятельности', $column, $row);
        $row = $row + 1;

        $totalByYear           = 0;
        $acquisitionOfPremises = [];

        if (count($this->report->investmentItems)) {
            foreach ($this->report->investmentItems as $investmentName => $investmentItem) {
                foreach ($this->diffDates as $i => $diffDate) {
                    $itemData = ($i === 0) ? -$investmentItem : 0;

                    $acquisitionOfPremises[] = $itemData;
                    $totalByYear             = $totalByYear + $itemData;

                    if ($diffDate['firstDate'] && $diffDate['lastDate']) {
                        $acquisitionOfPremises[] = $totalByYear;

                        $totalByYear = 0;
                    }
                }

                $this->report->investmentActivities[$investmentName] = $this->addNameToRow(
                    $acquisitionOfPremises,
                    config('projects.financing.' . $investmentName)
                );

                $acquisitionOfPremises = [];
            }
        }

        $investmentActivitiesTotal = DI::getDH()->getProject()->getDds()->getInvestmentActivitiesTotal(
            $this->report->investmentActivities
        );

        $this->report->investmentActivities['total']         = $this->addNameToRow(
            $investmentActivitiesTotal,
            'Итого выбытие по инвестиционной деятельности'
        );
        $this->report->investmentActivities['totalCashFlow'] = $this->addNameToRow(
            $investmentActivitiesTotal,
            'Итого денежный поток по инвестиционной деятельности'
        );

        $this->sheet->fromArray($this->report->investmentActivities,null, 'A' . $row, true);

        $lastRow = $row + count($this->report->investmentActivities) - 1;

        $this->sheet->getStyle('B'.$row.':' . $column . $lastRow)->applyFromArray([
            'numberFormat' => [
                'formatCode' => self::FORMAT_NUMBER
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_RIGHT,
            ],
        ]);

        return $lastRow;
    }

    /**
     * Собирает данные по "Финансовая деятельность"
     * в частности формирует "Притоки по финансовой деятельности" вместе с итогами
     *
     * @param  string                              $lastCell
     * @param  int                                 $row
     * @return int                                 Номер последней строки
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     */
    protected function financialActivities(string $lastCell, int $row): int
    {
        if (!array_key_exists('own', $this->report->financingTotal)) {
            return $row - 1;
        }

        $column = $this->sheet->getCell($lastCell)->getColumn();

        $this->setSubHeader('Движение денежных средств от финансовой деятельности', $column, $row, 'empty');
        $row = $row + 1;
        $this->setSubHeader('Притоки по финансовой деятельности', $column, $row);
        $row = $row + 1;

        $totalByYearFoundersCapital = 0;
        $totalByYearBorrowedCapital = 0;
        $totalByYear                = 0;
        $i                          = 0;

        foreach ($this->diffDates as $diffDate) {
            $itemFoundersCapital = ($i === 0) ? $this->report->financingTotal['own'] : 0;
            $itemBorrowedCapital = ($i === 0) ? $this->report->financingTotal['grant'] : 0;

            $this->report->financialActivities['FOUNDER'][] = $itemFoundersCapital;
            $totalByYearFoundersCapital                     = $totalByYearFoundersCapital + $itemFoundersCapital;

            $this->report->financialActivities['BORROWED'][] = $itemBorrowedCapital;
            $totalByYearBorrowedCapital                      = $totalByYearBorrowedCapital + $itemBorrowedCapital;

            $itemTotal                                    = ($i === 0) ? $itemBorrowedCapital + $itemFoundersCapital : 0;
            $this->report->financialActivities['TOTAL'][] = $itemTotal;
            $totalByYear                                  = $totalByYear + $itemTotal;

            if ($diffDate['firstDate'] && $diffDate['lastDate']) {
                $this->report->financialActivities['FOUNDER'][]  = $totalByYearFoundersCapital;
                $this->report->financialActivities['BORROWED'][] = $totalByYearBorrowedCapital;
                $this->report->financialActivities['TOTAL'][]    = $totalByYear;

                $totalByYearFoundersCapital = 0;
                $totalByYearBorrowedCapital = 0;
                $totalByYear                = 0;

                $i++;
            }
            $i++;
        }

        $this->report->financialActivities['FOUNDER']  = $this->addNameToRow(
            $this->report->financialActivities['FOUNDER'],
            'Привлечение капитала учредителей'
        );
        $this->report->financialActivities['BORROWED'] = $this->addNameToRow(
            $this->report->financialActivities['BORROWED'],
            'Привлечение заемного капитала'
        );
        $this->report->financialActivities['TOTAL']    = $this->addNameToRow(
            $this->report->financialActivities['TOTAL'],
            'Итого поступления денежных средств от финансовой деятельности'
        );

        $this->sheet->fromArray($this->report->financialActivities,null, 'A' . $row, true);

        $lastRow = $row + count($this->report->financialActivities) - 1;

        $this->sheet->getStyle('B'.$row.':' . $column . $lastRow)->applyFromArray([
            'numberFormat' => [
                'formatCode' => self::FORMAT_NUMBER
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_RIGHT,
            ],
        ]);

        return $lastRow;
    }

    /**
     * Собирает данные по "Оттоки по финансовой деятельности" вместе с итогами
     */
    protected function financialOutflows(string $lastCell, int $row): int
    {
        if (!array_key_exists('TOTAL', $this->report->financialActivities)) {
            return $row - 1;
        }
        $column = $this->sheet->getCell($lastCell)->getColumn();

        $this->setSubHeader('Оттоки по финансовой деятельности', $column, $row);
        $row = $row + 1;

        $totalFinancialActivities  = $this->removeNamedColumns([$this->report->financialActivities['TOTAL']])[0];
        if ($this->project->isCreditFinancing()) {
            $credits = $this->getPaymentRemuneration('C', 'Погашение займов и кредитов');
        } else {
            $credits = $this->report->dividends;
        }
        $this->report->financialOutflows['CREDIT'] = $credits;
        $credits                   = head($this->removeNamedColumns([$credits]));
        $this->report->financialOutflows['RETIREMENT'] = $this->addNameToRow(
            $credits,
            'Итого выбытие по финансовой деятельности'
        );
        $this->report->financialOutflows['FLOW'] = collect($totalFinancialActivities)
            ->map(function ($value, $index) use ($credits) {
                $credit = $credits[$index] ?? 0;

                return $value - $credit;
            })->prepend('Итого денежный поток по финансовой деятельности')->toArray();

        $this->sheet->fromArray($this->report->financialOutflows, null, 'A' . $row, true);

        $lastRow = $row + count($this->report->financialOutflows);
        $this->sheet->getStyle('B'.$row.':' . $column . $lastRow)->applyFromArray([
            'numberFormat' => [
                'formatCode' => self::FORMAT_NUMBER
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_RIGHT,
            ],
        ]);

        return $lastRow;
    }

    /**
     * Заполняет данные по оборачиваемости
     *
     * @param  string                              $lastCell
     * @param  int                                 $row
     * @return int                                 // номер строки окончания заполнение
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     */
    protected function prepareTurnoverTable(string $lastCell, int $row): int
    {
        if (!is_null($this->project->turnover) && count($this->project->turnover) === 3) {
            $column    = $this->sheet->getCell($lastCell)->getColumn();
            $proceeds  = $this->report->opiuIncome[0] ?? [];
            $costPrice = $this->report->proceedsTable['opiuCostPrice'] ?? [];

            if (count($proceeds) > 0 && count($costPrice) > 0) {

                $dhProject       = DI::getDH()->getProject();
                $receivables     = $dhProject->getDds()->getReceivables(
                    $this->diffDates,
                    $proceeds,
                    (int)$this->project->turnover['receivables'],
                    $this->project->schedule
                );
                $reserves        = $dhProject->getDds()->getReserves(
                    $this->diffDates,
                    $proceeds,
                    (int)$this->project->turnover['reserves'],
                    $this->project->schedule
                );
                $accountsPayable = $dhProject->getDds()->getAccountsPayable(
                    $this->diffDates,
                    $costPrice,
                    (int)$this->project->turnover['accountsPayable'],
                    $this->project->schedule
                );

                // Получаем сумму ячеек "Дебиторская задолженность" и "Запасы" с минусом
                $receivableAndReserves  = $dhProject->getSumColumns([$receivables, $reserves]);
                $receivableAndReserves  = $dhProject->getNegativeValues($receivableAndReserves);

                $originalWorkingCapital       = $dhProject->getSumColumns([$receivableAndReserves, $accountsPayable]);
                $this->changeInWorkingCapital = $dhProject->getChangeInWorkingCapital($this->diffDates, $originalWorkingCapital);

                $this->report->turnover = [
                    $this->addNameToRow($this->changeInWorkingCapital, 'Изменение в оборотном капитале'),
                    $this->addNameToRow($receivables, 'Дебиторская задолженность'),
                    $this->addNameToRow($reserves, 'Запасы'),
                    $this->addNameToRow($accountsPayable, 'Кредиторская задолженность'),
                ];

                $this->sheet->fromArray($this->report->turnover, null, 'A' . $row, true);

                $lastRow = $row + count($this->report->turnover);

                $this->sheet->getStyle('B' . $row . ':' . $column . $lastRow)->applyFromArray([
                    'numberFormat' => [
                        'formatCode' => self::FORMAT_NUMBER,
                    ],
                    'alignment'    => [
                        'horizontal' => Alignment::HORIZONTAL_RIGHT,
                    ],
                ]);
            }
        }

        return $lastRow ?? $row - 1;
    }

    /**
     * Строка "Чистое изменение денежных средств за период"
     *
     * @param  string                              $lastCell
     * @param  int                                 $row
     * @return int
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     */
    protected function balanceInHand(string $lastCell, int $row): int
    {
        if (!array_key_exists('totalCashFlow', $this->report->investmentActivities)) {
            return $row - 1;
        }

        $column = $this->sheet->getCell($lastCell)->getColumn();

        $operatingActivities = last($this->report->operatingActivities);
        $totalCashFlow       = $this->report->investmentActivities['totalCashFlow'];
        $financialOutflows   = $this->report->financialOutflows['FLOW'];

        $total  = DI::getDH()->getProject()->getSumColumns(array_merge(
            $this->removeNamedColumns([$operatingActivities, $totalCashFlow, $financialOutflows]),
            [$this->changeInWorkingCapital]
        ));
        // Чистое изменение денежных средств за период
        $result = $this->addNameToRow($total, 'ИТОГО поступление-выбытие денежных средств');

        $result = Arr::collapse([
            [$result],
            $this->prepareDepreciationTable($total)
        ]);

        $this->sheet->fromArray($result, null, 'A' . $row, true);

        $lastRow = $row + count($result) - 1;
        $this->sheet->getStyle('B'.$row.':' . $column . $lastRow)->applyFromArray([
            'numberFormat' => [
                'formatCode' => self::FORMAT_NUMBER
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_RIGHT,
            ],
        ]);

        return $lastRow;
    }

    /**
     * Формирует данные по "Сальдо на начало периода" и "Сальдо на конец периода"
     *
     * @param  array $result
     * @return array
     */
    protected function prepareDepreciationTable(array $result): array
    {
        $this->cashBeforeAfter = [
            'forStartPeriod' => [],
            'forEndPeriod'   => []
        ];

        if (count($this->diffDates) > 0 && count($result) > 0) {
            $forStartPeriod = 0;
            $index          = 0;

            foreach ($this->diffDates as $diffDate) {
                if (true === $diffDate['isFirst']) {
                    $forStartPeriod = $this->cashBeforeAfter['forEndPeriod'][$index - 1] ?? 0;
                }

                $this->cashBeforeAfter['forStartPeriod'][]     = $this->cashBeforeAfter['forEndPeriod'][$index - 1] ?? 0;
                $this->cashBeforeAfter['forEndPeriod'][$index] = $result[$index] + $this->cashBeforeAfter['forStartPeriod'][$index] ?? 0;

                if (true === $diffDate['isLast']) {
                    $this->cashBeforeAfter['forStartPeriod'][] = $forStartPeriod;
                    $this->cashBeforeAfter['forEndPeriod'][]   = $this->cashBeforeAfter['forEndPeriod'][$index];

                    $index++;
                }

                $index++;
            }
        }

        return [
            'forStartPeriod' => $this->addNameToRow($this->cashBeforeAfter['forStartPeriod'], 'Денежные средства на начало периода'),
            'forEndPeriod'   => $this->addNameToRow($this->cashBeforeAfter['forEndPeriod'], 'Денежные средства на конец периода')
        ];
    }

    /**
     * Собирает данные по отчету о движении денежных средств
     *
     * @return array
     */
    protected function getCashFlowStatement(): array
    {
        $proceedsTable = $this->report->proceedsTable['totalIncome'] ?? [];

        $cashFlowsFromOperatingActivities = $this->removeNamedColumns([last($this->report->operatingActivities)])[0] ?? [];
        $growthLongTermAssets             = $this->removeNamedColumns([$this->report->investmentActivities['totalCashFlow'] ?? []])[0] ?? [];
        $cashFlowsFromFinancingActivities = $this->removeNamedColumns([$this->report->financialOutflows['FLOW']])[0] ?? [];
        $cashAtBeginningPeriod            = $this->cashBeforeAfter['forStartPeriod'] ?? [];

        for ($i = 0; $i < count($proceedsTable); $i++) {
            $cashFlowsFromOperatingActivitiesItem = $cashFlowsFromOperatingActivities[$i] ?? 0;
            $growthLongTermAssetsItem             = $growthLongTermAssets[$i] ?? 0;
            $cashFlowsFromFinancingActivitiesItem = $cashFlowsFromFinancingActivities[$i] ?? 0;
            $cashAtBeginningPeriodItem            = $cashAtBeginningPeriod[$i] ?? 0;
            $this->report->cashAtEndPeriod[]      = $cashFlowsFromOperatingActivitiesItem + $growthLongTermAssetsItem
                                                    + $cashFlowsFromFinancingActivitiesItem + $cashAtBeginningPeriodItem;
        }

        return [
            'cashFlowStatement'                        => ['Отчет о движении денежных средств'],
            'profit'                                   => $this->addNameToRow($this->report->incomeStatementProfit, 'Чистая прибыль'),
            'changeWorkingCapital'                     => ['Изменение оборотного капитала'],
            'cashFlowsFromOperatingActivities'         => $this->addNameToRow($cashFlowsFromOperatingActivities, 'Денежные потоки от операционной деятельности'),
            'growthLongTermAssets'                     => $this->addNameToRow($growthLongTermAssets, 'Прирост долгосрочных активов'),
            'cashFlowsFromInvestingActivities'         => $this->addNameToRow($growthLongTermAssets, 'Денежные потоки от инвестиционной деятельности'),
            'receiptOrRepaymentOfFinancialLiabilities' => ['Поступление/погашение финансовых обязательств'],
            'cashFlowsFromFinancingActivities'         => $this->addNameToRow($cashFlowsFromFinancingActivities, 'Денежные потоки от финансовой деятельности'),
            'totalChangeInCash'                        => ['Итого изменение денежных средств'],
            'impactOfExchangeRatesAgainstTenge'        => ['Влияние обменных курсов валют к тенге'],
            'cashAtBeginningPeriod'                    => $this->addNameToRow($cashAtBeginningPeriod, 'Денежные средства на начало периода'),
            'cashAtEndPeriod'                          => $this->addNameToRow($this->report->cashAtEndPeriod, 'Денежные средства на конец периода'),
        ];
    }
}
