<?php declare(strict_types=1);

namespace App\Components\Report\Sheets;

use App\Components\Report\Sheets\Interfaces\SheetInterface;
use App\DI;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Log;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Color;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;

/**
 * Лист "Инвестиции/Амортизация"
 */
class InvestmentsSheet extends AbstractReportSheet implements SheetInterface
{
    /**
     * Номер текущего листа
     */
    const CURRENT_SHEET = 2;

    /**
     * Колонка с которой необходимо начать запись в файл
     */
    const START_COLL = 'B';

    /**
     * Строка с которой необходимо начать запись в файл
     */
    const START_ROW = 2;

    /**
     * Текущий активный лист
     *
     * @var \PhpOffice\PhpSpreadsheet\Worksheet\Worksheet
     */
    protected $sheet;

    /**
     * Данные по амортизации оборудования
     *
     * @var array
     */
    protected $depEquipment = [];

    /**
     * Данные по амортизации недвижимости
     *
     * @var array
     */
    protected $depProperty = [];

    /**
     * Данные по амортизации СМР
     *
     * @var array
     */
    protected $depSmr = [];

    /**
     * Данные по амортизации Капитальный ремонт
     *
     * @var array
     */
    protected $depOverhaul = [];

    /**
     * Номер строки, на которой остановилось заполнение
     *
     * @var int
     */
    private $lastRow = 2;

    /**
     * @inheritdoc
     *
     * @return \App\Components\Report\Sheets\InvestmentsSheet
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     */
    public function generate(): self
    {
        $this->setActiveSheet(self::CURRENT_SHEET);
        $this->sheet = $this->getActiveSheet();

        $this->fillEquipment();
        $this->fillProperty();
        $this->fillSmr();
        $this->fillOverhaul();

        // Считаем сумму амортизации
        $this->report->amortization = collect(DI::getDH()->getProject()->getSumColumns([
            $this->depEquipment['depAmount'] ?? [],
            $this->depProperty['depAmount'] ?? [],
            $this->depSmr['depAmount'] ?? [],
            $this->depOverhaul['depAmount'] ?? [],
        ]))->forget(0)->values()->toArray();

        return $this;
    }

    /**
     * Заполняет таблицу по амортизации оборудования
     *
     * @return void
     */
    protected function fillEquipment(): void
    {
        try {
            $depEquipment = $this->project->equipmentList;

            $this->depEquipment = $this->calculateDepreciation($depEquipment, 'Оборудование');

            $sum = $depEquipment->count() > 3 ? 2 : 5 ;

            $this->lastRow = $this->lastRow + $sum;
        } catch (\Throwable $throwable) {
            Log::error('[Амортизация] ошибка при получении оборудования', [
                'message' => $throwable->getMessage(),
                'line'    => $throwable->getLine(),
            ]);
        }
    }

    /**
     * Заполняет таблицу по амортизации недвижимости
     *
     * @return void
     */
    protected function fillProperty(): void
    {
        try {
            $depProperty = $this->project->readyPremisesList;

            $this->depProperty = $this->calculateDepreciation($depProperty, 'Помещение');

            $sum = $depProperty->count() > 3 ? 2 : 5 ;

            $this->lastRow = $this->lastRow + $sum;
        } catch (\Throwable $throwable) {
            Log::error('[Амортизация] ошибка при получении недвижимости', [
                'message' => $throwable->getMessage(),
                'line'    => $throwable->getLine(),
            ]);
        }
    }

    /**
     * Заполняет таблицу по амортизации СМР
     *
     * @return void
     */
    protected function fillSmr(): void
    {
        try {
            $smrList = $this->project->smrList;

            $this->depSmr = $this->calculateDepreciation($smrList, 'СМР');

            $sum = $smrList->count() > 3 ? 2 : 5;

            $this->lastRow = $this->lastRow + $sum;
        } catch (\Throwable $throwable) {
            Log::error('[Амортизация] ошибка при получении СМР', [
                'message' => $throwable->getMessage(),
                'line'    => $throwable->getLine(),
            ]);
        }
    }

    /**
     * Заполняет таблицу по амортизации СМР
     *
     * @return void
     */
    protected function fillOverhaul(): void
    {
        try {
            $overhaulList = $this->project->overhaulList;

            $this->depOverhaul = $this->calculateDepreciation($overhaulList, 'Капитальный ремонт');

            $sum = $overhaulList->count() > 3 ? 2 : 5;

            $this->lastRow = $this->lastRow + $sum;
        } catch (\Throwable $throwable) {
            Log::error('[Амортизация] ошибка при получении Капитального ремонта', [
                'message' => $throwable->getMessage(),
                'line'    => $throwable->getLine(),
            ]);
        }
    }

    /**
     * Формирует и заполняет таблицы по амортизации
     *
     * @param  \Illuminate\Database\Eloquent\Collection $items
     * @param  string                                   $typeName
     * @return array
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     */
    protected function calculateDepreciation(Collection $items, string $typeName): array
    {
        if ($items->count() === 0) {
            return [];
        }

        $avgLifeTime   = collect($items)->avg('lifeTime');
        $lastCol       = $this->setTableHeaders($this->lastRow);
        $this->lastRow = $this->lastRow + 1; // увеличиваем на единицу за счет строки с названием таблицы
        $tableData     = collect([]);

        foreach ($items as $item) {
            $tableData->push(
                $item->only(['name', 'count', 'priceOneUnit', 'price'])
            );
        }

        $tableData->push(['ИТОГО', null, null, $tableData->sum('price')]);

        $this->sheet->fromArray(
            $tableData->toArray(), null, self::START_COLL . $this->lastRow, true
        );

        $depreciationTable = $this->prepareDepreciationTable(
            (int) $tableData->sum('price'),
            1 / $avgLifeTime / 12
        );

        $this->setDepreciationTableHeaders($this->lastRow - 1, $typeName);
        $this->appendDataToSheet($depreciationTable, $this->lastRow + 1);

        $startRow      = $this->lastRow;
        $this->lastRow = $this->lastRow + $tableData->count() - 1;

        $this->setResultStyle(self::START_COLL . $this->lastRow . ':' . $lastCol . $this->lastRow);
        $this->sheet->getStyle(self::START_COLL . $startRow . ':' . $lastCol . $this->lastRow)->applyFromArray([
            'numberFormat' => [
                'formatCode' => self::FORMAT_NUMBER
            ],
        ]);

        return $depreciationTable;
    }

    /**
     * Добавляет заголовки для таблиц "Оборудования" и "Недвижимости"
     *
     * @param  int    $row
     * @return string
     */
    protected function setTableHeaders(int $row = 2): string
    {
        $heads = ['Наименование', 'Кол-во', 'Стоимость', 'ИТОГО'];

        $this->sheet->fromArray([$heads], null, self::START_COLL . $row, true);

        $lastColl = $this->sheet->getCellByColumnAndRow(count($heads) + 1, 1)->getColumn();

        $this->setHeaderStyle(self::START_COLL . $row . ':' . $lastColl . $row);

        return $lastColl ?? self::START_COLL;
    }

    /**
     * Заполняет названия полей для таблиц с амортизацией
     *
     * @param  int                                 $row
     * @param  string                              $typeName
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     */
    private function setDepreciationTableHeaders(int $row = self::START_ROW, string $typeName = '')
    {
        $this->sheet->fromArray([
            ['Амортизация периода',                    null],
            [$typeName,                                null],
            ['Балансовая стоимость на начало периода', null],
            ['Сумма износа в периоде',                 null],
            ['Накопленная амортизация',                null],
            ['Балансовая стоимость на конец периода',  null],
        ], null, 'G' . $row, true);

        $this->sheet->mergeCells('G' . $row . ':H' . $row)->mergeCells('G' . ($row + 1) . ':H' . ($row + 1));
        $this->setHeaderStyle('G' . $row . ':H' . ($row + 1));
        $this->setDatesToSheet('I' . $row, 9, $row);
        $this->sheet->getStyle('H' . $row . ':H' . ($row + 5))->applyFromArray([
            'borders' => [
                'right' => array(
                    'borderStyle' => Border::BORDER_THIN,
                    'color'       => [
                        'argb' => Color::COLOR_BLACK
                    ],
                ),
            ]
        ]);
        $this->setStyleByResultColumn($row, $row + 5);
    }

    /**
     * Формирует данные для таблиц по амортизации
     * Как для амортизации оборудования так и для недвижимости
     *
     * @param  int   $total
     * @param  float $percent
     * @return array
     */
    protected function prepareDepreciationTable(int $total, float $percent): array
    {
        $result = [
            'forStartPeriod' => [ $total ],
            'depAmount'      => [ 0      ],
            'depreciation'   => [ 0      ],
            'forEndPeriod'   => [ $total ],
        ];

        if (count($this->diffDates) > 0) {
            $index             = 0;
            $totalDepAmount    = 0;
            $indexStartPeriod  = 0;

            foreach ($this->diffDates as $diffDate) {
                // На начало периода, значение из предыдущей ячейки
                // Закомментировал, потому что пока не известно, точно ли так должно быть
                // $forStartPeriod = $result['forEndPeriod'][$index];
                $forStartPeriod = $total;
                // Высчитываем значение "Сумма износа в периоде" за текущий месяц
                $depAmount      = $result['forEndPeriod'][0] * $percent;
                // Суммируем значения для годовой ячейки параметра "Сумма износа в периоде"
                $totalDepAmount = $totalDepAmount + $depAmount;
                // Высчитываем значение для "Накопленная амортизация" за текущий месяц
                $depreciation   = $result['depreciation'][$index] + $depAmount;
                // Высчитываем значение "Балансовая стоимость на конец периода" для текущего месяца
                $forEndPeriod   = $forStartPeriod - $depAmount;

                $result['forStartPeriod'][] = $forStartPeriod;
                $result['depAmount'][]      = $depAmount;
                $result['depreciation'][]   = $depreciation;
                $result['forEndPeriod'][]   = $forEndPeriod;

                if ($diffDate['firstDate'] && $diffDate['lastDate']) {
                    $result['forStartPeriod'][] = $result['forEndPeriod'][$indexStartPeriod] ?? 0;
                    $result['depAmount'][]      = $totalDepAmount;
                    $result['depreciation'][]   = $depreciation;
                    $result['forEndPeriod'][]   = $forEndPeriod;

                    ++$index;

                    $totalDepAmount   = 0;
                    $indexStartPeriod = $index;
                }

                ++$index;
            }
        }

        return $result;
    }

    /**
     * Заполняет подготовленные данные по амортизации
     * применяет стили к ячейкам
     *
     * @param array $data
     * @param int   $row
     */
    protected function appendDataToSheet(array $data, int $row = self::START_ROW)
    {
        $this->sheet->fromArray($data, null, 'H' . $row, true);

        $lastCell = $this->sheet->getCellByColumnAndRow(
            count(head($data)) + 7, $row + count($data) - 1
        )->getCoordinate();
        $this->sheet->getStyle('H' . $row . ':' . $lastCell)->applyFromArray([
            'numberFormat' => [
                'formatCode' => self::FORMAT_NUMBER
            ],
        ]);
    }
}
