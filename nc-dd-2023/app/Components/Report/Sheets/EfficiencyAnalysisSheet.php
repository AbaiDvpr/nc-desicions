<?php declare(strict_types=1);

namespace App\Components\Report\Sheets;

use App\Components\Report\Sheets\Interfaces\SheetInterface;
use App\DI;
use PhpOffice\PhpSpreadsheet\Cell\Cell;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Color;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Calculation\Financial\CashFlow\Variable\Periodic;

/**
 * Класс для заполнения листа "Анализ эффективности"
 *
 * @package App\Components\Report\Sheets
 */
class EfficiencyAnalysisSheet extends AbstractReportSheet implements SheetInterface
{
    use \App\Components\Report\Helpers\EfficiencyAnalysis\Npv;
    use \App\Components\Report\Helpers\EfficiencyAnalysis\Irr;
    use \App\Components\Report\Helpers\EfficiencyAnalysis\PaybackPeriod;
    use \App\Components\Report\Helpers\EfficiencyAnalysis\Roi;
    use \App\Components\Report\Helpers\EfficiencyAnalysis\ProfitabilityIndex;
    use \App\Components\Report\Helpers\EfficiencyAnalysis\Results;
    use \App\Components\Report\Helpers\EfficiencyAnalysis\StressTesting;

    /**
     * Номер текущего листа
     */
    const CURRENT_SHEET = 10;

    /**
     * Текущий активный лист
     *
     * @var \PhpOffice\PhpSpreadsheet\Worksheet\Worksheet
     */
    protected $sheet;

    /**
     * NPV проекта, для расчета Чистой Приведенной Стоимости (NPV) проекта
     *
     * @var array
     */
    protected $npv = [];

    /**
     * NPV проекта за последний год
     *
     * @var int
     */
    protected $npvLastForYear = 0;

    /**
     * Расчет Рентабельности инвестиции (Р) проекта - рентабельность инвестиции
     *
     * @var float
     */
    protected $returnOnInvestment = 0;

    /**
     * Free Cash Flow - в таблице с расчетами NPV
     *
     * @var array
     */
    protected $npvFreeCashFlow = [];

    /**
     * Free Cash Flow нарастающим итогом - в таблице с расчетами NPV
     *
     * @var array
     */
    protected $npvFreeCashFlowTotal = [];

    /**
     * Дисконтированный денежный поток - в таблице с расчетами NPV
     *
     * @var array
     */
    protected $prepareDiscountedCashFlow = [];

    /**
     * @inheritdoc
     *
     * @return \App\Components\Report\Sheets\EfficiencyAnalysisSheet
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     */
    public function generate(): self
    {
        $this->setActiveSheet(self::CURRENT_SHEET);
        $this->sheet = $this->getActiveSheet();

        $yearsPeriod = $this->prepareYearsPeriod();
        $lastRow     = count($yearsPeriod);
        $this->sheet->fromArray($yearsPeriod, null, 'A1', true);

        $lastColumn = $this->sheet->getCellByColumnAndRow(count(head($yearsPeriod)), $lastRow)->getCoordinate();
        $this->setHeaderStyle('A1:' . $lastColumn);

        $lastRow = $this->incrementRow($lastRow, 2);
        $this->sheet->setCellValue('A' . $lastRow, 'Расчет Чистой Приведенной Стоимости (NPV) проекта')
            ->mergeCells('A' . $lastRow . ':' . $this->sheet->getCell($lastColumn)->getColumn() . $lastRow);
        $this->setHeaderStyle('A'.$lastRow, 'empty');

        // Расчет Чистой Приведенной Стоимости (NPV) проекта
        $dates = $this->prepareNpvTable($lastRow);

        // Расчет Внутренней нормы доходности (IRR) проекта
        $irrDates = $this->prepareIrrTable($dates, $lastColumn);

        /* Устанавливаем таблицу со сроками окупаемости */
        $lastRow = $this->incrementRow($irrDates['lastRow'] ?? $dates['lastRow'] ?? $lastRow, 2);

        $coefficient = DI::getDH()->getProject()->getEfficiencyAnalysis()->getCoefficient(
            $this->diffDates,
            $this->npvFreeCashFlowTotal,
            $this->report->operatingActivities,
            $this->report->investmentActivities
        );

        $lastRow = $this->setTablePaybackPeriodInfo($coefficient, $lastRow);

        /* Устанавливаем таблицу данными по "Расчет Рентабельности инвестиции (Р) проекта" */
        $lastRow = $this->setTableROI($lastRow);

        /* Устанавливаем таблицу "Расчет Индекса рентабельности инвестиции (РI) проекта" */
        $lastRow = $this->setTableProfitabilityIndex($lastRow);

        $lastRow = $this->getFirstTable($lastRow);

        $this->getSecondTable($lastRow);

        return $this;
    }

    /**
     * Подготавливает массив с датами по годам
     *
     * @return array|array[]
     */
    protected function prepareYearsPeriod(): array
    {
        $dates = [
            ['Начало периода', 'дата', null],
            ['Конец периода',  'дата', null],
        ];

        foreach ($this->yearsPlaning as $yearPlan) {
            $dates[0][] = $yearPlan['fromDate'];
            $dates[1][] = $yearPlan['toDate'];
        }

        return $dates;
    }

    /**
     * Вставляет таблицу "Расчет Чистой Приведенной Стоимости (NPV) проекта"
     *
     * @param  int                                $row
     * @return array
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     */
    protected function prepareNpvTable(int $row = 5): array
    {
        $discount = round(($this->report->discountPercent * 100), 2) . '%';
        $dates    = [
            'discount'           => [$discount,                                     null, null],
            'coefficient'        => ['Коэффициент дисконтирования',                 null, null],
            'costs'              => ['Инвестиционные затраты',                      null, null],
            'moneyMovement'      => ['Движение денег от операционной деятельности', null, null],
            'freeCashFlow'       => ['Free Cash Flow',                              null, null],
            'freeCashFlowTotal'  => ['Free Cash Flow нарастающим итогом',           null, null],
            'discountedCashFlow' => ['Дисконтированный денежный поток',             null, null],
            'npvProject'         => ['NPV проекта',                                 null, null],
        ];

        $dates['moneyMovement'] = collect([
            $dates['moneyMovement'],
            $this->getTotalByYears(last($this->report->operatingActivities) ?? [])
        ])->collapse()->toArray();

        $index         = 2;
        $iteration     = 0;
        $totalCashFlow = 0;

        if (array_key_exists('totalCashFlow', $this->report->investmentActivities)) {
            $totalCashFlow = $this->report->investmentActivities['totalCashFlow'];
        }
        foreach ($this->yearsPlaning as $yearPlan) {
            $dates['discount'][]    = $yearPlan['year'];
            $dates['coefficient'][] = $yearPlan['discount'];
            $financingTotal         = $totalCashFlow[1] ?? 0;
            $dates['costs'][]       = $index > 2 ? 0 : $financingTotal;

            $index++;
            $iteration++;
        }

        // Free Cash Flow
        $dates['freeCashFlow'] = $this->getFreeCashFlow($dates);

        // Free Cash Flow нарастающим итогом
        $dates['freeCashFlowTotal'] = $this->getFreeCashFlowTotal($dates);

        // Дисконтированный денежный поток
        $dates['discountedCashFlow'] = $this->getDiscountedCashFlow($dates);

        // NPV проекта
        $dates['npvProject'] = $this->getNpvProject($dates);

        $lastRow = $row + 1;
        $this->sheet->fromArray($dates, null, 'A' . $lastRow, true);
        $lastColumn = $this->sheet->getCellByColumnAndRow(count(head($dates)), $lastRow)->getCoordinate();
        $this->setHeaderStyle('A' . $lastRow . ':' . $lastColumn, 'empty');
        $lastNameColumn = $this->sheet->getCell($lastColumn)->getColumn();

        foreach ($dates as $dateItem) {
            $this->sheet->mergeCells('A' . $lastRow . ':C' . $lastRow);
            $this->sheet->getStyle('D'.$lastRow.':' . $lastNameColumn . $lastRow)->applyFromArray([
                'numberFormat' => [
                    'formatCode' => self::FORMAT_NUMBER,
                ],
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_RIGHT,
                ],
            ]);

            $lastRow++;
        }

        $dateCells = 'D' . ($lastRow - count($dates)) . ':' . $lastNameColumn . ($lastRow - count($dates));
        $this->setDefaultFormat($dateCells);

        return [
            'dates'   => $dates,
            'lastRow' => $lastRow
        ];
    }

    /**
     * Заполняет таблицу "Расчет Внутренней нормы доходности (IRR) проекта"
     *
     * @param  array                               $npvDates
     * @param  string                              $lastColumn
     * @return array
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     */
    protected function prepareIrrTable(array $npvDates, string $lastColumn): array
    {
        if (!isset($npvDates['dates']['freeCashFlow'])) {
            return [];
        }

        $lastRow      = $this->incrementRow($npvDates['lastRow'], 2);
        $freeCashFlow = $this->removeNamedColumns([$npvDates['dates']['freeCashFlow']], 3)[0];
        $irr          = Periodic::rate($freeCashFlow);

        if (is_float($irr)) {
            $this->sheet->setCellValue('A' . $lastRow, 'Расчет Внутренней нормы доходности (IRR) проекта')
                ->mergeCells('A' . $lastRow . ':B' . $lastRow);
            $this->setHeaderStyle('A'.$lastRow, 'empty');

            $irrCoefficients = $this->getIrrCoefficients($irr);

            $dates = [
                'rateOfReturn'       => collect([
                    ['Внутренняя норма доходности, IRR', null, round($irr * 100, 2) . '%'],
                    array_keys($this->yearsPlaning),
                ])->collapse()->values()->toArray(),
                'costs'              => collect([
                    ['Коэффициент', null, null],
                    $irrCoefficients,
                ])->collapse()->values()->toArray(),
                'freeCashFlow'       => collect([
                    ['Free Cash Flow', null, null],
                    collect($freeCashFlow)->map(function ($item) {
                        return $item;
                    })->values()->toArray(),
                ])->collapse()->values()->toArray(),
                'discountedCashFlow' => collect([
                    ['Дисконтированный денежный поток', null, null],
                    collect($freeCashFlow)->map(function ($value, $index) use ($irrCoefficients) {
                        return $value * $irrCoefficients[$index];
                    })->values()->toArray(),
                ])->collapse()->values()->toArray(),
                'npvProject'         => ['NPV проекта', null, null],
            ];

            $dates['npvProject'] = $this->getIrrNpvProject($dates);

            $this->sheet->fromArray($dates, null, 'A' . $lastRow, true);

            $lastColumn = $this->sheet->getCellByColumnAndRow(count(head($dates)), $lastRow)->getCoordinate();
            $this->setHeaderStyle('A' . $lastRow . ':' . $lastColumn, 'empty');

            $this->sheet->mergeCells('A' . $lastRow . ':B' . $lastRow);
            $lastNameColumn = $this->sheet->getCell($lastColumn)->getColumn();

            foreach ($dates as $dateItem) {
                $this->sheet->mergeCells('A' . $lastRow . ':B' . $lastRow);
                $this->sheet->getStyle('D' . $lastRow . ':' . $lastNameColumn . $lastRow)->applyFromArray([
                    'numberFormat' => [
                        'formatCode' => self::FORMAT_NUMBER,
                    ],
                    'alignment'    => [
                        'horizontal' => Alignment::HORIZONTAL_RIGHT,
                    ],
                ]);

                $lastRow = $this->incrementRow($lastRow);
            }

            $dateCells = 'D' . ($lastRow - count($dates)) . ':' . $lastNameColumn . ($lastRow - count($dates));
            $this->setDefaultFormat($dateCells);

            return [
                'dates'   => $dates,
                'lastRow' => $lastRow,
            ];
        }

        return [];
    }
}
