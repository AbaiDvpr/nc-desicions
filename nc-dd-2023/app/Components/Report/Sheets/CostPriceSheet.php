<?php declare(strict_types=1);

namespace App\Components\Report\Sheets;

use App\Components\Report\Sheets\Interfaces\SheetInterface;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;

/**
 * Данные для заполнения на страницу "Себестоимость"
 *
 * @package App\Components\Report\Sheets
 */
class CostPriceSheet extends AbstractReportSheet implements SheetInterface
{
    /**
     * Номер текущего листа
     */
    const CURRENT_SHEET = 3;

    /**
     * Текущий активный лист
     *
     * @var \PhpOffice\PhpSpreadsheet\Worksheet\Worksheet
     */
    protected $sheet;

    /**
     * @inheritdoc
     *
     * @return \App\Components\Report\Sheets\CostPriceSheet
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     */
    public function generate(): self
    {
        // TODO: сделать получение в репозитории
        $volumeSales = $this->project->volumeSales()->with('getVariableCoast')->get();

        $volumeSales = $volumeSales->where(function ($item) {
            return $item->getVariableCoast()->count() > 0;
        });

        if ($volumeSales->count() === 0) {
            return $this;
        }

        $this->setActiveSheet(self::CURRENT_SHEET);
        $this->sheet = $this->getActiveSheet();
        $iteration   = 2;

        foreach ($volumeSales as $i => $volumeSale) {
            $this->sheet->setCellValue('A' . $iteration, ++$i);
            $this->sheet->setCellValue('B' . $iteration, $volumeSale->name)
                ->mergeCells('B' . $iteration . ':E' . $iteration);
            $this->setHeaderStyle('A' . $iteration . ':E' . $iteration, 'FFD9D9D9');

            $variableCoasts = $volumeSale->getVariableCoast;

            if ($variableCoasts->count() > 0) {
                $itog = 0;
                foreach ($variableCoasts as $variableCoast) {
                    ++$iteration;

                    $this->sheet->setCellValue('B' . $iteration, $variableCoast->costType)
                        ->mergeCells('B' . $iteration . ':C' . $iteration);
                    $this->sheet->setCellValue('D' . $iteration, $variableCoast->price);
                    $this->sheet->setCellValue('E' . $iteration, $variableCoast->price);
                    $itog += $variableCoast->price;
                }
                ++$iteration;

                $this->report->costPriceTotal[$volumeSale->id] = $itog;

                $this->sheet->setCellValue('A' . $iteration, 'ИТОГО, тенге')
                    ->mergeCells('A' . $iteration . ':D' . $iteration);
                $this->sheet->setCellValue('E' . $iteration, $itog);
                $this->sheet->getStyle('E' . $iteration)->getAlignment()
                    ->setHorizontal(Alignment::HORIZONTAL_RIGHT);
                $this->sheet->getStyle('A' . $iteration . ':E' . $iteration)->getFont()->setBold(true);

                ++$iteration;
            }

            ++$iteration;
        }

        $this->setStyle($iteration - 2);

        return $this;
    }

    /**
     * Применить стили к максимальному кол-ву строк
     *
     * @param int $maxRow
     */
    protected function setStyle(int $maxRow = 3): void
    {
        $this->sheet->getStyle('A1:E' . $maxRow)->applyFromArray([
            'numberFormat' => [
                'formatCode' => self::FORMAT_NUMBER,
            ],
        ]);

        // Выравниваем по центру заголовки таблицы
        $this->setAlignment('A1:E1');

        // Выравниваем по центру нумерацию первого столбца
        $this->setAlignment('A1:A' . $maxRow);
    }
}
