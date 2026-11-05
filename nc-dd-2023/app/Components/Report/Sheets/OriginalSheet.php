<?php declare(strict_types=1);

namespace App\Components\Report\Sheets;

use App\Components\Report\Sheets\Interfaces\SheetInterface;
use App\DI;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;

class OriginalSheet extends AbstractReportSheet implements SheetInterface
{
    /**
     * Номер текущего листа
     */
    const CURRENT_SHEET = 0;

    /**
     * Текущий активный лист
     *
     * @var \PhpOffice\PhpSpreadsheet\Worksheet\Worksheet
     */
    protected $sheet;

    /**
     * @inheritdoc
     *
     * @return \App\Components\Report\Sheets\OriginalSheet
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     */
    public function generate(): self
    {
        $this->setActiveSheet(self::CURRENT_SHEET);
        $this->sheet = $this->getActiveSheet();

        $this->setRatesToSheet();
        $this->setFixedData();

        $this->setTurnover();

        return $this;
    }

    /**
     * Добавить на страницу данные по курсам валют
     *
     * @return void
     */
    protected function setRatesToSheet(): void
    {
        $createdAt = $this->project->created_at;
        $rates     = DI::getRepository()->getRates()->findByDate($createdAt->format('Y-m-d'));

        if (!is_null($rates)) {
            $this->sheet->setCellValue('C2', $createdAt->format('Y.m.d'))
                ->setCellValue('C3', $rates->amount_RUB)
                ->setCellValue('C4', $rates->amount_EUR)
                ->setCellValue('C5', $rates->amount_USD);
        }
    }

    /**
     * Заполнить первоначальные данные по ставкам
     *
     * @return void
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     */
    private function setFixedData(): void
    {
        $row = 10;

        foreach (config('projects.original') as $key => $record) {
            $percent = in_array($key, config('projects.percentageValues'));

            $this->sheet->setCellValue('A' . $row, $record['name'])->mergeCells('A' . $row . ':B' . $row);

            if (in_array($key, ['workingDays', 'daysInMonths'])) {
                $this->sheet->setCellValue('C' . $row, $record['value'][$this->project->schedule ?? 1]);
            } else if ($key === 'discount') {
                $this->sheet->setCellValue('C' . $row, round($this->report->discountPercent  * 100, 2) . '%');
            } else {
                $this->sheet->setCellValue('C' . $row, $percent ? ($record['value'] * 100) . '%' : $record['value']);
            }

            ++$row;
        }

        $planingData[] = array_keys($this->yearsPlaning);

        if (count($this->yearsPlaning) > 0) {
            for ($indexYear = 1; $indexYear <= count($this->yearsPlaning); $indexYear++) {
                $planingData[1][] = $indexYear . ' год';
            }

            $row = 2;
            foreach (config('projects.original') as $key => $record) {
                $planingData[$row] = collect($this->yearsPlaning)->map(function ($yearPlan) use($key) {
                    return $yearPlan[$key] ?? 0;
                })->toArray();

                $row++;
            }

            $this->sheet->fromArray($planingData, null, 'D8');

            $lastCell = $this->sheet->getCellByColumnAndRow(count(head($planingData)) + 3, 9)->getCoordinate();
            $this->setHeaderStyle('A8:' . $lastCell)->setBorders('A8:' . $lastCell, 'FF000000');

            $lastCell = $this->sheet->getCellByColumnAndRow(
                count(head($planingData)) + 3,
                count(config('projects.original')) + 9
            )->getCoordinate();

            $this->sheet->getStyle('C10:' . $lastCell)->applyFromArray([
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_RIGHT,
                ],
                'numberFormat' => [
                    'formatCode' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1
                ]
            ]);

            $this->sheet->getStyle('C10:C15')->applyFromArray(
                $this->getBorderStyle('right')
            );
        }
    }

    /**
     * Установить на страницу данные по оборачиваемости
     *
     * @return void
     */
    private function setTurnover()
    {
        $this->report->turnover = $this->project->getTurnover();

        if ($this->report->turnover) {
            $this->sheet->fromArray([
                [
                    'Период погашения дебиторской задолженности, дней',
                    null,
                    null,
                    $this->report->turnover['reserves'],
                ],
                [
                    'Период оборачиваемости запасов, дней',
                    null,
                    null,
                    $this->report->turnover['receivables'],
                ],
                [
                    'Период погашения кредиторской задолженности, дней',
                    null,
                    null,
                    $this->report->turnover['accountsPayable'],
                ]
            ], null, 'A18');

            $this->sheet->getStyle('C18:C20')->applyFromArray(
                $this->getBorderStyle('right')
            );
        }
    }
}
