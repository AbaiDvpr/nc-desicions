<?php declare(strict_types=1);

namespace App\Components\Report\Helpers\EfficiencyAnalysis;

use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Color;

trait Roi
{
    /**
     * Возвращает массив с информацией "Расчет Рентабельности инвестиции (Р) проекта"
     *
     * @return array[]
     */
    public function getROI(): array
    {
        $this->npvLastForYear = last($this->npv);
        $financingTotal        = $this->report->financingTotal['total'] ?? 0;

        if ($financingTotal > 0) {
            $this->returnOnInvestment = $this->npvLastForYear / $financingTotal;
        }

        return [
            [
                'Расчет Рентабельности инвестиции (Р) проекта',
            ],
            [
                $this->npvLastForYear,
                '(чистая приведенная стоимость)',
            ],
            [
                $this->report->financingTotal['total'] ?? 0,
                '(величина исходной инвестиции)',
            ],
            [
                round($this->returnOnInvestment * 100, 2) . '%',
                '(рентабельность инвестиции)',
            ],
        ];
    }

    /**
     * Вставляем таблицу "Расчет Рентабельности инвестиции (Р) проекта"
     *
     * @param  int $lastRow
     * @return int
     */
    public function setTableROI(int $lastRow): int
    {
        $records = $this->getROI();
        $this->sheet->fromArray($records, null, 'A' . $lastRow, true);

        $this->sheet->mergeCells('A' . $lastRow . ':F' . $lastRow);

        $this->sheet->getStyle('A' . $lastRow . ':A' . $lastRow)->applyFromArray([
            'font' => [
                'bold'  => true,
                'color' => ['argb' => 'FF365f92']
            ],
            'borders' => [
                'allBorders' => array(
                    'borderStyle' => Border::BORDER_THIN,
                    'color'       => [
                        'argb' => Color::COLOR_BLACK
                    ],
                ),
            ],
        ]);

        $this->sheet->getStyle('A' . $lastRow . ':J' . ($lastRow + count($records)))->applyFromArray([
            'numberFormat' => [
                'formatCode' => '#,##0.00'
            ],
        ]);

        // +2 это отступ до следующей таблицы
        return $lastRow + count($records) + 2;
    }
}
