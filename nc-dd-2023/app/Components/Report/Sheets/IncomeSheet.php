<?php declare(strict_types=1);

namespace App\Components\Report\Sheets;

use App\Components\Report\Sheets\Interfaces\SheetInterface;
use App\DI;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use PhpOffice\PhpSpreadsheet\Style\Color;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;

/**
 * Класс для заполнения листа доходов
 *
 * @package App\Components\Report\Sheets
 */
class IncomeSheet extends AbstractReportSheet implements SheetInterface
{
    /**
     * Номер текущего листа
     */
    const CURRENT_SHEET = 5;

    /**
     * Строка с которой необходимо начать запись в файл
     */
    const START_ROW = 4;

    /**
     * Данные для таблицы "Ценовая политика"
     *
     * @var array
     */
    private $volumeSales = [];

    /**
     * Текущий активный лист
     *
     * @var \PhpOffice\PhpSpreadsheet\Worksheet\Worksheet
     */
    protected $sheet;

    /**
     * @inheritdoc
     *
     * @return \App\Components\Report\Sheets\IncomeSheet
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     */
    public function generate(): self
    {
        $this->setActiveSheet(self::CURRENT_SHEET);
        $this->setDatesToSheet('D1', 4, 1);

        $this->sheet = $this->getActiveSheet();
        $volumeSales = DI::getRepository()->getProject()->getVolumeSales($this->project->getUuid())->getAll();

        if ($volumeSales->count() > 0) {
            $startRow = $this->generateVolumeSales($volumeSales, self::START_ROW);

            $startRow = $this->generatePricePolicy($volumeSales, ++$startRow);

            $this->generateTotal($volumeSales, ++$startRow);
        }

        return $this;
    }

    /**
     * Генерируем таблицу "План продаж"
     *
     * @param  $volumeSales
     * @param  $startRow
     * @return int
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     */
    protected function generateVolumeSales($volumeSales, $startRow): int
    {
        $this->setStyleByResultColumn($startRow, $startRow + $volumeSales->count());

        foreach ($volumeSales as $iteration => $volumeSale) {
            $this->sheet->setCellValue('A' . $startRow, ++$iteration);
            $this->sheet->setCellValue('B' . $startRow, $volumeSale->name);
            $this->sheet->setCellValue('C' . $startRow, $volumeSale->getUnitValue());

            if (count($this->diffDates) > 0) {
                $i     = 0;
                $total = 0;

                foreach ($this->diffDates as $diffDate) {
                    $currentDate = Carbon::parse($diffDate['start']);
                    $count       = 0;

                    if ($this->isStartedSaleDate($diffDate['start'])) {
                        $count = $this->getVolumeSaleForMonth($volumeSale->count, $diffDate, $volumeSale->units);
                    }

                    // Подставляем данные согласно сезонности
                    if ($volumeSale->seasonal && !in_array($currentDate->month, $volumeSale->seasonal)) {
                        $count = 0;
                    }

                    $this->report->volumeSales[$iteration][$i] = $count;
                    $total                                     = $total + $count;

                    if ($diffDate['firstDate'] && $diffDate['lastDate']) {
                        $this->report->volumeSales[$iteration][] = $total;
                        $total                                   = 0;

                        $i++;
                    }

                    $i++;

                    $this->volumeSales[$volumeSale->id] = $this->report->volumeSales[$iteration];
                }
            }

            $startRow++;
        }

        $this->setSubHeaderTable($volumeSales, $this->report->volumeSales, 'План продаж', $startRow);
        $this->setTableValues($volumeSales, $this->report->volumeSales, $startRow);

        return $startRow;
    }

    /**
     * Генерируем таблицу "Цена реализации"
     *
     * @param  $volumeSales
     * @param  $startRow
     * @return int
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     */
    protected function generatePricePolicy($volumeSales, $startRow): int
    {
        $this->setStyleByResultColumn($startRow, $startRow + $volumeSales->count());
        $pricePolicy = [];

        foreach ($volumeSales as $iteration => $volumeSale) {
            $this->sheet->setCellValue('A' . $startRow, ++$iteration);
            $this->sheet->setCellValue('B' . $startRow, $volumeSale->name);
            $this->sheet->setCellValue('C' . $startRow, $this->project->getCurrencyValue());

            if (count($this->diffDates) > 0) {
                $i = 0;

                foreach ($this->diffDates as $diffDate) {
                    $price = $this->getPricePolicyByYear($diffDate, $volumeSale);

                    if ($this->volumeSales[$volumeSale->id][$i] === 0) {
                        $price = 0;
                    }

                    $pricePolicy[$iteration][] = $price;

                    if ($diffDate['firstDate'] && $diffDate['lastDate']) {
                        $pricePolicy[$iteration][] = $price;

                        $i++;
                    }

                    $i++;
                }
            }

            $startRow++;
        }

        $this->setSubHeaderTable($volumeSales, $pricePolicy, 'Цена реализации', $startRow);
        $this->setTableValues($volumeSales, $pricePolicy, $startRow);

        return $startRow;
    }

    /**
     * Генерируем таблицу с итогами
     *
     * @param  $volumeSales
     * @param  $startRow
     * @return int
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     */
    protected function generateTotal($volumeSales, $startRow): int
    {
        $volumeSaleValues = [];

        ++$startRow;

        foreach ($volumeSales as $iteration => $volumeSale) {
            $this->sheet->setCellValue('A' . $startRow, ++$iteration);
            $this->sheet->setCellValue('B' . $startRow, $volumeSale->name);
            $this->sheet->setCellValue('C' . $startRow, $this->project->getCurrencyValue());

            if (count($this->diffDates) > 0) {
                $i     = 0;
                $total = 0;

                foreach ($this->diffDates as $diffDate) {
                    $count = $this->volumeSales[$volumeSale->id][$i];

                    $price = $this->getPricePolicyByYear($diffDate, $volumeSale);

                    $result = $this->isStartedSaleDate($diffDate['start']) ? $price * $count : 0;

                    $volumeSaleValues[$iteration][] = $result;
                    $total                          = $total + $result;

                    if ($diffDate['firstDate'] && $diffDate['lastDate']) {
                        $volumeSaleValues[$iteration][] = $total;
                        $total                          = 0;

                        $i++;
                    }

                    $i++;
                }
            }

            $this->report->detailIncome[$volumeSale->id] =  $volumeSaleValues[$iteration];

            $startRow++;
        }

        $this->report->totalIncome = DI::getDH()->getProject()->getSumColumns($volumeSaleValues);
        $volumeSaleValues[]        = $this->report->totalIncome;

        $this->setSubHeaderTable($volumeSales, $volumeSaleValues, 'Выручка / Доходы', $startRow);

        $this->setTableValues($volumeSales, $volumeSaleValues, $startRow);

        $this->sheet->setCellValue('B' . $startRow, 'ИТОГО, ВЫРУЧКА');
        $this->sheet->setCellValue('C' . $startRow, $this->project->getCurrencyValue());

        $lastCell = $this->sheet->getCellByColumnAndRow(count(last($volumeSaleValues)) + 3, $startRow)->getCoordinate();
        $this->setResultStyle('A'.$startRow . ':' . $lastCell);

        $this->setStyleByResultColumn($startRow - $volumeSales->count(), $startRow - 1);

        return $startRow;
    }

    /**
     * Установить подзаголовок таблицы
     *
     * @param  Collection                          $volumeSales
     * @param  array                               $volumeSaleValues
     * @param  string                              $title
     * @param  int                                 $row
     * @return void
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     */
    private function setSubHeaderTable(
        Collection $volumeSales,
        array $volumeSaleValues = [],
        string $title = '',
        int $row = self::START_ROW
    ): void
    {
        $col = $this->sheet->getCellByColumnAndRow(count(last($volumeSaleValues)) + 3, $row)->getColumn();
        $row = $row - $volumeSales->count() - 1;

        $this->setSubHeader($title, $col, $row);
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
        $this->setAlignment('C'.($startRow - $count).':C'. $startRow);

        $this->sheet->fromArray($volumeSaleValues, null, 'D' . ($startRow - $count), true);

        $lastCell = $this->sheet->getCellByColumnAndRow(count(last($volumeSaleValues)) + 3, $startRow)->getCoordinate();

        $this->setBorders('A' . ($startRow - $count) . ':' . $lastCell, Color::COLOR_BLACK);

        $this->sheet->getStyle('D' . ($startRow - $count) . ':' . $lastCell)->applyFromArray([
            'numberFormat' => [
                'formatCode' => self::FORMAT_NUMBER
            ]
        ]);

        return $this;
    }

    /**
     * Проверяет больше ли переданная дата, даты начала продаж
     *
     * @param  $date
     * @return bool
     */
    private function isStartedSaleDate($date): bool
    {
        $salesStartDate = Carbon::parse($this->project->salesStartDate);

        return $salesStartDate->lte(Carbon::parse($date));
    }

    /**
     * Возвращает цену с учетом инфляции для конкретного года и с учетом даты начала продаж
     *
     * @param  $diffDate
     * @param  $volumeSale
     * @return float|int
     */
    private function getPricePolicyByYear($diffDate, $volumeSale)
    {
        $year  = Carbon::parse($diffDate['start'])->year;

        $price = $volumeSale->price * ($this->yearsPlaning[$year]['inflation'] ?? 1);

        return $this->isStartedSaleDate($diffDate['start']) ? $price : 0;
    }

    /**
     * Получает объем продаж за месяц
     *
     * @param        $lastCount
     * @param  array $diffDate
     * @param  int   $units
     * @return float
     */
    public function getVolumeSaleForMonth($lastCount, array $diffDate, int $units): float
    {
        if ($diffDate['percent'] === 0) {
            return $lastCount;
        }

        $count = $lastCount * $diffDate['percent'];

        return $this->getRoundVolumeSales($count, $units);
    }

    /**
     * Получить округленное значение или нет
     *
     * @param  float $count
     * @param  int   $units
     * @return float
     */
    private function getRoundVolumeSales(float $count, int $units): float
    {
        // 1, 4, 10, 11 - значения из поля с единицами измерения
        if (in_array($units, [1, 4, 10, 11])) {
            return ceil($count);
        }

        return $count;
    }
}
