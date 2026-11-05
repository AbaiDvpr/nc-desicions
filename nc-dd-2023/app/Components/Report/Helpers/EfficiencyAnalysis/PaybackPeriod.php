<?php declare(strict_types=1);

namespace App\Components\Report\Helpers\EfficiencyAnalysis;

use Illuminate\Support\Arr;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Color;

trait PaybackPeriod
{
    /**
     * Стили для подзаголовков
     *
     * @var array
     */
    protected $styleSubHeaders = [
        'font'      => [
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
    ];

    /**
     * Возвращает массив с инфой по Срокам окупаемости
     *
     * @return array[]
     */
    public function getPaybackPeriodInfo(float $coefficient): array
    {
        return [
            [
                'Расчет Простого срока окупаемости инвестиции (PB) проекта',
            ],
            [
                'PB = N + Cn-1/CFn',
            ],
            [
                'N = ',
                'число лет, предшествующих году окупаемости;',
            ],
            [
                'Cn-1 =',
                'число лет, предшествующих году окупаемости;',
            ],
            [
                'СFn  =',
                'денежный поток в год окупаемости.',
            ],
            [
                null,
            ],
            [
                'PB = ',
                $this->calculatePB($coefficient),
                'или',
                round($this->calculatePB($coefficient) * 12),
                'месяца',
            ],

            [
                null,
            ],

            [
                'Расчет Дисконтированного срока окупаемости инвестиции (DPB) проекта',
            ],
            [
                'РР = N + Cn-1/ NPVn',
            ],
            [
                'N = ',
                'невозмещенная стоимость на начало года окупаемости;',
            ],
            [
                'Cn-1 =',
                'невозмещенная стоимость на начало года окупаемости;',
            ],
            [
                'NPVn  =',
                'дисконтированный денежный поток в год окупаемости.',
            ],
            [
                null,
            ],
            [
                'DPB = ',
                $this->calculateDPB($coefficient),
                'или',
                round($this->calculateDPB($coefficient) * 12),
                'месяца',
            ],
        ];
    }

    /**
     * Устанавливаем таблицу со сроками окупаемости
     *
     * @param  float                               $coefficient
     * @param  int                                 $lastRow
     * @return int
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     */
    public function setTablePaybackPeriodInfo(float $coefficient, int $lastRow): int
    {
        $records = $this->getPaybackPeriodInfo($coefficient);

        $this->sheet->fromArray($records, null, 'A' . $lastRow, true);

        $this->sheet->mergeCells('A' . $lastRow . ':F' . $lastRow);
        $this->sheet->getStyle('A' . $lastRow . ':F' . $lastRow)->applyFromArray($this->styleSubHeaders);

        $this->sheet->mergeCells('A' . ($lastRow + 8) . ':F' . ($lastRow + 8));
        $this->sheet->getStyle('A' . ($lastRow + 8) . ':F' . ($lastRow + 8))->applyFromArray($this->styleSubHeaders);

        $this->sheet->getStyle('A' . $lastRow . ':J' . ($lastRow + count($records)))->applyFromArray([
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_LEFT,
            ],
        ]);

        return $lastRow + count($records) + 2;
    }

    /**
     * Расчет Простого срока окупаемости инвестиции (PB) проекта
     *
     * PB = N + Cn-1/CFn
     * N - число лет, предшествующих году окупаемости;
     * Cn-1 - невозмещенная стоимость на начало года окупаемости;
     * СFn - денежный поток в год окупаемости.
     *
     * @param  float $coefficient
     * @return float
     */
    protected function calculatePB(float $coefficient): float
    {
        $countNegativeValue = $this->getCountNegativeValue($this->npvFreeCashFlowTotal);

        $npvFreeCashFlow           = $this->npvFreeCashFlow[$countNegativeValue] ?? last($this->npvFreeCashFlow);
        $negativeFreeCashFlowTotal = $this->getLastNegativeValue($this->npvFreeCashFlowTotal);

        return round($coefficient + ($negativeFreeCashFlowTotal * -1) / $npvFreeCashFlow, 2);
    }

    /**
     * Расчет Дисконтированного срока окупаемости инвестиции (DPB) проекта
     *
     * РР = N - Cn-1 / NPVn
     * N - число лет, предшествующих году окупаемости;
     * Cn-1 - невозмещенная стоимость на начало года окупаемости;
     * NPVn - дисконтированный денежный поток в год окупаемости.
     *
     * @param  float $coefficient
     * @return float
     */
    protected function calculateDPB(float $coefficient): float
    {
        $countNegativeValue = $this->getCountNegativeValue($this->npv);

        // Если NPV в первый год положительный, то считаем по простому сроку
        if ($countNegativeValue < $coefficient) {
            return $this->calculatePB($coefficient);
        }

        $npvFreeCashFlow = $this->prepareDiscountedCashFlow[$countNegativeValue] ??
                              last($this->prepareDiscountedCashFlow);

        $negativeFreeCashFlowTotal = $this->getLastNegativeValue($this->npv);

        return round($countNegativeValue - ($negativeFreeCashFlowTotal / $npvFreeCashFlow), 2);
    }

    /**
     * Возвращает последнее отрицательное число в последовательности
     *
     * @param  array $sequence
     * @return float
     */
    private function getLastNegativeValue(array $sequence): float
    {
        return (float) collect($sequence)->filter(function ($item) {
                return $item < 0;
            })->last() ?? 0.00;
    }

    /**
     * Возвращает кол-во отрицательных значений в последовательности
     *
     * @param  array $sequence
     * @return int
     */
    private function getCountNegativeValue(array $sequence): int
    {
        return collect($sequence)->filter(function ($item) {
            return $item < 0;
        })->count();
    }
}
