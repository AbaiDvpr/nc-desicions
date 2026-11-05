<?php declare(strict_types=1);

namespace App\Components\Report;

use App\Components\Report\Sheets\BalanceSheet;
use App\Components\Report\Sheets\ConsumptionSheet;
use App\Components\Report\Sheets\CostPriceSheet;
use App\Components\Report\Sheets\DdsSheet;
use App\Components\Report\Sheets\EfficiencyAnalysisSheet;
use App\Components\Report\Sheets\FinancingSheet;
use App\Components\Report\Sheets\FotSheet;
use App\Components\Report\Sheets\IncomeSheet;
use App\Components\Report\Sheets\InvestmentsSheet;
use App\Components\Report\Sheets\OpiuSheet;
use App\Components\Report\Sheets\OriginalSheet;
use App\Components\Report\Sheets\PaymentScheduleSheet;
use App\Components\Report\Sheets\WorkingCapitalSheet;
use App\Components\Report\Traits\GlobalVariables;
use App\DI;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\Exception;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Writer\IWriter;
use Symfony\Component\HttpFoundation\StreamedResponse;
use PhpOffice\PhpSpreadsheet\IOFactory;

/**
 * Класс для генерации отчетов по проектам
 */
class Report
{
    use GlobalVariables;

    /**
     * Расширение файла отчёта
     */
    const REPORT_EXTENSION = '.Xlsx';

    /**
     * Стили для всех листов
     *
     * @var array[]
     */
    public array $styleByAllSheets = [
        'font' => [
            'name' => 'Times New Roman',
            'size' => 10,
        ],
        'alignment' => [
            'vertical' => Alignment::VERTICAL_CENTER,
        ],
    ];

    /**
     * Генерируем и сохраняем новый отчет
     *
     * @throws Exception
     * @throws \PhpOffice\PhpSpreadsheet\Writer\Exception
     */
    public function generate($uuid): bool
    {
        $project     = DI::getRepository()->getProject()->getOneByUuid($uuid);
        $spreadsheet = IOFactory::load(resource_path('report/example-report.xlsx'));
        $spreadsheet->getDefaultStyle()->applyFromArray($this->styleByAllSheets);

        $sheet = (new FinancingSheet($spreadsheet, $project, $this))                         ->generate()->push();
        $sheet = (new OriginalSheet($sheet->spreadsheet, $project, $sheet->report))          ->generate()->push();
        $sheet = (new InvestmentsSheet($sheet->spreadsheet, $project, $sheet->report))       ->generate()->push();
        $sheet = (new CostPriceSheet($sheet->spreadsheet, $project, $sheet->report))         ->generate()->push();
        $sheet = (new WorkingCapitalSheet($sheet->spreadsheet, $project, $sheet->report))    ->generate()->push();
        $sheet = (new IncomeSheet($sheet->spreadsheet, $project, $sheet->report))            ->generate()->push();
        $sheet = (new FotSheet($sheet->spreadsheet, $project, $sheet->report))               ->generate()->push();
        $sheet = (new ConsumptionSheet($sheet->spreadsheet, $project, $sheet->report))       ->generate()->push();
        $sheet = (new PaymentScheduleSheet($sheet->spreadsheet, $project, $sheet->report))   ->generate()->push();
        $sheet = (new OpiuSheet($sheet->spreadsheet, $project, $sheet->report))              ->generate()->push();
        $sheet = (new DdsSheet($sheet->spreadsheet, $project, $sheet->report))               ->generate()->push();
        $sheet = (new BalanceSheet($sheet->spreadsheet, $project, $sheet->report))           ->generate()->push();
        $sheet = (new EfficiencyAnalysisSheet($sheet->spreadsheet, $project, $sheet->report))->generate()->push();

        // Удаляем с документа лист "Себестоимость если он пустой"
        if (count($sheet->report->costPriceTotal) === 0) {
            $spreadsheet->removeSheetByIndex(CostPriceSheet::CURRENT_SHEET);
        }

        return $this->saveFileToStorage(
            IOFactory::createWriter($sheet->spreadsheet, 'Xlsx'),
            $uuid
        );
    }

    /**
     * Скачивает отчет
     *
     * @param  string                                             $uuid
     * @return \Symfony\Component\HttpFoundation\StreamedResponse
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     * @throws \PhpOffice\PhpSpreadsheet\Writer\Exception
     */
    public function downloadReport(string $uuid): StreamedResponse
    {
        $project = DI::getRepository()->getProject()->getOneByUuid($uuid);

        if (!$project) {
            return abort(404);
        }

        if (!app()->isProduction()) {
            $this->generate($uuid);
        }

        // Если документа не оказалось, пере создаем его заново
        if (!Storage::fileExists($this->getReportPath($uuid))) {
            $this->generate($uuid);
        }

        return Storage::download(
            $this->getReportPath($uuid),
            sprintf('%s%s', $project->getName(), strtolower(self::REPORT_EXTENSION))
        );
    }

    /**
     * Сохраняет файл на диск
     *
     * @param  IWriter   $writer
     * @param  string    $uuid
     * @return bool
     * @throws Exception
     */
    protected function saveFileToStorage(IWriter $writer, string $uuid): bool
    {
        try {
            ob_start();
            $writer->save('php://output');
            $content = ob_get_contents();
            ob_end_clean();

            if (Storage::put($this->getReportPath($uuid), $content)) {
                return true;
            }
        } catch (\Exception $exception) {
            Log::error('Не удалось сгенерировать отсчет', [
                'exception' => $exception
            ]);

            throw $exception;
        }

        return false;
    }

    /**
     * Получить путь к файлу на диске
     *
     * @param  string $uuid
     * @return string
     */
    protected function getReportPath(string $uuid): string
    {
        return 'reports/' . $uuid . self::REPORT_EXTENSION;
    }
}
