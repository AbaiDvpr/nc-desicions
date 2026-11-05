<?php declare(strict_types=1);

namespace App\Components\Report\Traits;

use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Color;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;

trait StyleTrait
{
    /**
     * Основной фон всех таблиц
     */
    private $mainFont = 'FFDEEBF7';

    /**
     * Цвет главного подзаголовка для таблиц
     *
     * @var string
     */
    public $mainSubHeaderColor = 'CC4A74A8';

    /**
     * Цвет подзаголовка для таблиц
     *
     * @var string
     */
    public $subHeaderColor = 'FFFFFFFF';

    /**
     * Цвет подзаголовка для таблиц "Анализ эффективности"
     *
     * @var string
     */
    public $analysisHeaderColor = 'FFDAEEF3';

    /**
     * Применяем стили к ячейкам
     *
     * @param  string $cells
     * @param  string $bgColor
     * @return $this
     */
    protected function setHeaderStyle(string $cells = '', string $bgColor = 'FFDEEBF7'): self
    {
        if (!empty($cells)) {
            $sheet = $this->spreadsheet->getActiveSheet();

            $style = [
                'font'      => [
                    'bold'  => true,
                    'color' => ['argb' => Color::COLOR_BLACK]
                ],
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
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

            if ($bgColor !== 'empty') {
                $style = array_merge($style, [
                    'fill'      => [
                        'fillType' => Fill::FILL_SOLID,
                        'color'    => [
                            'argb' => $bgColor
                        ]
                    ]
                ]);
            }

            $sheet->getStyle($cells)->applyFromArray($style);
        }

        return $this;
    }

    /**
     * Формирует массив со стилями для границ ячеек
     *
     * @param  string $border
     * @param  string $borderStyle
     * @return array
     */
    public function getBorderStyle(string $border = 'allBorders', string $borderStyle = Border::BORDER_THIN): array
    {
        return [
            'borders' => [
                $border => [
                    'borderStyle' => $borderStyle,
                    'color'       => [
                        'argb' => Color::COLOR_BLACK,
                    ],
                ],
            ],
        ];
    }

    /**
     * Устанавливает выравнивание в ячейках
     *
     * @param  string $cells
     * @param  string $alignment
     * @return $this
     */
    public function setAlignment(string $cells = '', string $alignment = Alignment::HORIZONTAL_CENTER): self
    {
        if (!empty($cells)) {
            $sheet = $this->spreadsheet->getActiveSheet();

            $sheet->getStyle($cells)->applyFromArray([
                'alignment' => [
                    'horizontal' => $alignment ?? Alignment::HORIZONTAL_CENTER,
                ],
            ]);
        }

        return $this;
    }

    /**
     * Устанавливает border ячеек
     *
     * @param  string $cells
     * @param  string $borderColor
     * @return $this
     */
    public function setBorders(string $cells = '', string $borderColor = 'FF366092'): self
    {
        if (!empty($cells)) {
            $sheet = $this->spreadsheet->getActiveSheet();

            $sheet->getStyle($cells)->applyFromArray([
                'borders' => [
                    'allBorders' => [
                        'color' => [
                            'argb' => $borderColor,
                        ],
                    ],
                ],
            ]);
        }

        return $this;
    }

    /**
     * Устанавливает стили для выделения результатов
     *
     * @param  string $cells
     * @param  bool   $isRow
     * @return $this
     */
    public function setResultStyle(string $cells = '', bool $isRow = true): self
    {
        if (!empty($cells)) {
            $sheet = $this->spreadsheet->getActiveSheet();

            $sheet->getStyle($cells)->applyFromArray([
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'color'    => [
                        'argb' => $isRow ? 'FFD9D9D9' : 'FFDEEBF7'
                    ]
                ],
                'font' => [
                    'bold'  => true,
                    'color' => ['argb' => Color::COLOR_BLACK]
                ],
                'borders' => [
                    'right' => [
                        'borderStyle' => Border::BORDER_THIN,
                        'color'       => [
                            'argb' => Color::COLOR_BLACK,
                        ],
                    ],
                ],
            ]);
        }

        return $this;
    }

    /**
     * Применяет стили к столбцам с результатами
     *
     * @param  int   $startRow
     * @param  int   $lastRow
     * @return $this
     */
    public function setStyleByResultColumn(int $startRow = 1, int $lastRow = 5): self
    {
        if (count($this->resultCols) > 0) {
            foreach ($this->resultCols as $resultCol) {
                $cells = $resultCol . $startRow . ':' . $resultCol . $lastRow;

                $this->setResultStyle($cells, false);
            }
        }

        return $this;
    }

    /**
     * Установить подзаголовок таблице на всю ширину
     *
     * @param  string $title
     * @param  string $coll
     * @param  int    $row
     * @param  string $background
     * @return $this
     */
    public function setSubHeader(string $title, string $coll, int $row = 10, string $background = ''): self
    {
        $sheet = $this->spreadsheet->getActiveSheet();

        $sheet->setCellValue('A' . $row, $title)->mergeCells('A' . $row . ':' . $coll . $row);

        $this->setHeaderStyle('A' . $row, !empty($background) ? $background : $this->subHeaderColor);

        return $this;
    }

    /**
     * Устанавливает стили по умолчанию для диапазона ячеек
     *
     * @param string $cells
     */
    public function setDefaultFormat(string $cells): void
    {
        $this->sheet->getStyle($cells)->applyFromArray([
            'numberFormat' => [
                'formatCode' => NumberFormat::FORMAT_NUMBER,
            ],
            'alignment'    => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
            ],
        ]);
    }

    /**
     * Устанавливает процентный формат ячеек
     *
     * @param  string $cells
     * @return void
     */
    public function setPercentFormat(string $cells): void
    {
        $this->sheet->getStyle($cells)->applyFromArray([
            'numberFormat' => [
                'formatCode' => NumberFormat::FORMAT_PERCENTAGE_00,
            ],
            'alignment'    => [
                'horizontal' => Alignment::HORIZONTAL_RIGHT,
            ],
        ]);
    }

    /**
     * Установить стили для строки с итогами
     *
     * @param  string $cells
     * @return void
     */
    public function setStyleForResultRow(string $cells): void
    {
        $this->sheet->getStyle($cells)->applyFromArray($this->getBorderStyle('top'));
    }
}
