<?php declare(strict_types=1);

namespace App\Components\Report\Helpers\EfficiencyAnalysis;

use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Color;

/**
 * Расчет Индекса рентабельности (коэффициент рентабельности) инвестиции (РI) проекта
 */
trait ProfitabilityIndex
{
    /**
     * Возвращает массив с инфой для Индекса рентабельности
     *
     * @return array[]
     */
    public function getAProfitabilityIndex(): array
    {
        return [
            [
                'Расчет Индекса рентабельности (коэффициент рентабельности) инвестиции (РI) проекта',
            ],
            [
                round($this->returnOnInvestment * 100, 2) . '%',
                '(рентабельность инвестиции)',
            ],
            [
                $this->returnOnInvestment + 1,
                '(индекс рентабельности инвестиций)'
            ],
        ];
    }

    /**
     * Вставляем таблицу "Расчет Индекса рентабельности инвестиции (РI) проекта"
     *
     * @param  int                                 $lastRow
     * @return int
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     */
    public function setTableProfitabilityIndex(int $lastRow): int
    {
        $records = $this->getAProfitabilityIndex();

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
