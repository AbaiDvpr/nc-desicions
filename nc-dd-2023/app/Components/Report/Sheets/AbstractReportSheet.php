<?php declare(strict_types=1);

namespace App\Components\Report\Sheets;

use App\Components\Report\Report;
use App\Components\Report\Sheets\Interfaces\SheetInterface;
use App\DI;
use App\Models\Project;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

abstract class AbstractReportSheet implements SheetInterface
{
    use \App\Traits\Cache\Cacheable;
    use \App\Components\Report\Traits\StyleTrait;
    use \App\Components\Report\Traits\DatesTrait;

    /**
     * @var \PhpOffice\PhpSpreadsheet\Spreadsheet
     */
    public $spreadsheet;

    /**
     * @var \App\Models\Project
     */
    public $project;

    /**
     * Разница между периодами планирования
     *
     * @var array
     */
    public $diffDates = [];

    /**
     * Минимальный процент с которого нужно будет накапливать
     */
    const MIN_PERCENT = 0.5; // 50%

    /**
     * Максимальней процент до которого нужно будет накапливать
     */
    const MAX_PERCENT = 1; // 100%

    /**
     * Года планирования с месяцами по каждому году
     *
     * @var array

     */
    public $yearsPlaning = [];

    /**
     * Список столбцов с результирующими параметрами
     *
     * @var array
     */
    protected $resultCols = [];

    /**
     * Налоги для определенной формы
     *
     * @var array
     */
    public $taxes = [];

    /**
     * @var \App\Components\Report\Report
     */
    public $report;

    /**
     * Формат чисел в таблицах, с округлением до целых
     */
    const FORMAT_NUMBER = '#,##0';

    /**
     * AbstractReportSheet constructor.
     *
     * @param \PhpOffice\PhpSpreadsheet\Spreadsheet $spreadsheet
     * @param \App\Models\Project                   $project
     * @param \App\Components\Report\Report         $report
     */
    public function __construct(Spreadsheet $spreadsheet, Project $project, Report $report)
    {
        $this->report      = $report;
        $this->spreadsheet = $spreadsheet;
        $this->project     = $project;

        $this->getYearsPlaning();

        $this->getDiffBetweenDates();

        $this->getTaxes();
    }

    /**
     * Устанавливает номер активного листа
     *
     * @param  int                                 $sheet
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     */
    public function setActiveSheet(int $sheet = 1): void
    {
        $this->spreadsheet->setActiveSheetIndex($sheet);
    }

    /**
     * Возвращает активный лист
     *
     * @return \PhpOffice\PhpSpreadsheet\Worksheet\Worksheet
     */
    public function getActiveSheet(): Worksheet
    {
        return $this->spreadsheet->getActiveSheet();
    }

    /**
     * @inheritdoc
     */
    public function push(): self
    {
        return $this;
    }

    /**
     * Формируем массив с датами в формате начала и конца месяца без итоговых столбцов
     *
     * @return $this
     */
    protected function getDiffBetweenDates(): self
    {
        $planning = $this->project->getPlanning();

        if (!empty($planning)) {
            $dates     = CarbonPeriod::create($planning['from'], $planning['to'])->month();
            $firstDate = null;

            foreach ($dates as $key => $date) {
                $date = Carbon::make($date);

                $first = $date->month === 1  || $key === 0;
                $last  = $date->month === 12 || $key === $dates->count() - 1;

                if ($first) {
                    $firstDate = $date->format('d.m.Y');
                }

                $lastDateInMonth = $date->daysInMonth . $date->format('.m.Y');

                $this->diffDates[] = [
                    'start'     => $date->format('d.m.Y'),
                    'end'       => $lastDateInMonth,
                    'isFirst'   => $first,
                    'isLast'    => $last,
                    'firstDate' => $last ? $firstDate : null,
                    'lastDate'  => $last ? $date->daysInMonth . '.' .$date->format('m.Y') : null,
                    'percent'   => $this->getPercentageOfSales(Carbon::parse($lastDateInMonth), $key),
                ];
            }
        }

        return $this;
    }

    /**
     * Заполнить даты на лист
     *
     * @param  string|null                        $fromCell
     * @param  int                                $colIndex
     * @param  int                                $row
     * @return $this
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     */
    protected function setDatesToSheet(string $fromCell = null, int $colIndex = 4, int $row = 2): self
    {
        $sheet = $this->spreadsheet->getActiveSheet();
        $dates = $this->prepareDatesForSheet();
        $sheet->setShowSummaryRight(true);

        if (!is_null($fromCell)) {
            $sheet->fromArray($dates, null, $fromCell);

            $index     = $colIndex ?? 4;
            $visible   = true;
            $collapsed = true;
            foreach ($this->diffDates as $date) {
                $collName = $sheet->getCellByColumnAndRow($index, $row)->getColumn();
                $sheet->getColumnDimension($collName)->setOutlineLevel(1);

                $sheet->getColumnDimension($collName)->setCollapsed($collapsed);
                $sheet->getColumnDimension($collName)->setVisible($visible);
                $sheet->getColumnDimension($collName)->setAutoSize(true);

                if ($date['firstDate'] && $date['lastDate']) {
                    $visible   = false;
                    $collapsed = false;
                    $index     = $index + 1;

                    $this->resultCols[] = $sheet->getCellByColumnAndRow($index, $row)->getColumn();
                }

                ++$index;
            }

            $lastCell = $sheet->getCellByColumnAndRow($colIndex + count($dates[0]) - 1, $row + 1)->getCoordinate();
            $this->setHeaderStyle($fromCell . ':' . $lastCell);

            $this->setStyleByResultColumn($row, $row + 1);

            $this->setAutoSizeCols($this->resultCols);
        }

        return $this;
    }

    /**
     * Преобразуем массив с датами добавляем столбец с "Итоги года"
     *
     * @return array
     */
    private function prepareDatesForSheet(): array
    {
        if (!empty($this->diffDates)) {
            foreach ($this->diffDates as $diffDate) {
                $dates[0][] = $diffDate['start'];
                $dates[1][] = $diffDate['end'];

                if ($diffDate['lastDate']) {
                    $dates[0][] = $diffDate['firstDate'];
                    $dates[1][] = $diffDate['lastDate'];
                }
            }
        }

        return $dates ?? [];
    }

    /**
     * Возвращает список налоговых вычет
     *
     * @return $this
     */
    protected function getTaxes(): self
    {
        $this->taxes = \Arr::get(config('projects.taxes'), $this->project->getForm());

        return $this;
    }

    /**
     * Высчитываем процент от объема продаж по месяцам
     *
     * @param  \Carbon\Carbon $dateTime
     * @param  int            $key
     * @return float
     */
    private function getPercentageOfSales(Carbon $dateTime, int $key): float
    {
        $plannedCapacity = $this->project->getPlannedCapacity();

        if (!empty($plannedCapacity)) {
            if (!isset($plannedCapacity[$dateTime->year])) {
                return 0;
            }

            return (float) ((int) $plannedCapacity[$dateTime->year]) / 100;
        }

        $startDate  = Carbon::parse($this->project->salesStartDate);
        $endDate    = Carbon::parse($this->project->plannedVolume);
        $diffMonths = $startDate->diffInMonths($endDate);

        if ($dateTime->gte($startDate) && $dateTime->lte($endDate) && $diffMonths > 0) {
            $percent = (float) ((100 - 50) / $diffMonths) / 100;

            $minPercent = $key === 0 ? self::MIN_PERCENT : self::MIN_PERCENT + $percent;

            return $percent < self::MAX_PERCENT ? $minPercent : self::MAX_PERCENT;
        }

        return 1.0;
    }

    /**
     * Возвращает цену с учетом инфляции для конкретного года и с учетом даты начала продаж
     *
     * @param  $diffDate
     * @param  $price
     * @return float|int
     */
    public function getPriceByInflation($diffDate, $price)
    {
        $year = Carbon::parse($diffDate['start'])->year;

        return $price * ($this->yearsPlaning[$year]['inflation'] ?? 1);
    }

    /**
     * Удаляет "countCols" столбцов с названиями для дальнейших манипуляций
     *
     * @param  array $records
     * @param  int   $countCols
     * @return array
     */
    public function removeNamedColumns(array $records, int $countCols = 1): array
    {
        return collect($records)->map(function ($record) use ($countCols) {
            return collect($record)->splice($countCols)->values();
        })->toArray();
    }

    /**
     * Добавляет столбец с названием в начало массива
     * принимает массив данных одной строки
     *
     * @param  array  $records
     * @param  string $name
     * @return array
     */
    public function addNameToRow(array $records, string $name): array
    {
        return collect($records)->prepend($name)->values()->toArray();
    }

    /**
     * Увеличивает номер строки
     *
     * @param  int $item
     * @param  int $index
     * @return int
     */
    public function incrementRow(int $item, int $index = 1): int
    {
        return $item + ($item > 0 ? $index : 1);
    }

    /**
     * Суммирует значения в массивах с календарями платежей
     *
     * @param  string $sumColl
     * @return array
     */
    public function summarizeGraphsPayments(string $sumColl = 'D'): array
    {
        $scheduleMax = $this->report->paymentSchedule;
        $scheduleMin = $this->report->paymentWorkingCapital;

        if (count($scheduleMax) === 0) {
            return [];
        }

        if (count($scheduleMin) >= count($scheduleMax)) {
            $scheduleMax = $this->report->paymentWorkingCapital;
            $scheduleMin = $this->report->paymentSchedule;
        }

        foreach ($scheduleMax as $date => $datum) {
            $sumDate = $scheduleMin[$date] ?? [];

            $scheduleMax[$date][$sumColl] = $datum[$sumColl] + ($sumDate[$sumColl] ?? 0);
        }

        return $scheduleMax;
    }

    /**
     * Установить авто ширину столбцам
     *
     * @param  $cols
     * @return void
     */
    protected function setAutoSizeCols($cols): void
    {
        if (is_string($cols)) {
            $cols[] = $cols;
        }

        if (count($cols) > 0) {
            $sheet = $this->spreadsheet->getActiveSheet();

            foreach ($cols as $col) {
                $sheet->getColumnDimension($col)->setAutoSize(true);
            }
        }
    }

    /**
     * Получает дату с плюсом нескольких лет
     *
     * @param  int                        $year
     * @return \Illuminate\Support\Carbon
     */
    public function getMaxDividendsDate(int $year): Carbon
    {
        $planning = $this->project->getPlanning();

        return Carbon::parse($planning['from'])->addYears($year);
    }

    /**
     * Устанавливает переменные расходы
     *
     * @param  array $variableCost
     * @return void
     */
    public function setVariableCosts(array $variableCost): void
    {
        $this->report->variableCosts[] = $variableCost;
    }
}
