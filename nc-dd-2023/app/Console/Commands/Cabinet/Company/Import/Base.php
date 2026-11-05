<?php declare(strict_types=1);

namespace App\Console\Commands\Cabinet\Company\Import;

use App\Models\Company\Import;
use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use League\Flysystem\UnableToDeleteFile;
use League\Flysystem\UnableToWriteFile;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Reader\Exception;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Throwable;

/**
 * Базовый класс для работы с импортом
 */
abstract class Base
{
    /**
     * @var \PhpOffice\PhpSpreadsheet\Spreadsheet
     */
    protected Spreadsheet $spreadsheet;

    /**
     * Данные полученные с файла
     *
     * @var array
     */
    protected array $sheet = [];

    /**
     * Наименование листа
     *
     * @var string
     */
    protected string $sheetName = '';

    /**
     * Шапка файла
     *
     * @var array
     */
    protected array $sheetHeader = [];

    /**
     * Финальные данные которые сохранятся в базу
     *
     * @var array
     */
    protected array $finalSheet = [];

    /**
     * Индекс ячейки с которого начинаются данные периодов
     *
     * @var int
     */
    protected int $periodStartIndex = 0;

    /**
     * @var \App\Models\Company\Import
     */
    protected Import $import;

    /**
     * Инициализация
     *
     * @return void
     */
    protected function initialize(): void
    {
        $this->copyFileFromRemoteStorage();

        $this->spreadsheet = IOFactory::load(storage_path('app/public/' . $this->getTmpPath()));
    }

    /**
     * Возвращает данные листа по индексу для сохранения
     *
     * @param  int                                       $sheetIndex
     * @param  string                                    $sheetName
     * @return array
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     * @throws \PhpOffice\PhpSpreadsheet\Reader\Exception
     * @throws \Throwable
     */
    protected function getDataFromSheet(int $sheetIndex, string $sheetName): array
    {
        $this->sheetName        = $sheetName;
        $this->sheet            = [];
        $this->sheetHeader      = [];
        $this->finalSheet       = [];
        $this->periodStartIndex = 0;

        $names = $this->spreadsheet->getSheetNames();

        if (
            !count($names) ||
            mb_strtolower($names[$sheetIndex]) !== mb_strtolower(config('company.importSheet.name.' . $sheetName))
        ) {
            throw new Exception('Invalid sheet name', 500);
        }

        $this->buildSheet($this->spreadsheet->getSheet($sheetIndex));
        $this->removeRowsWithoutCodes();
        $this->buildPeriods();
        $this->buildFinalData();

        return $this->finalSheet;
    }

    /**
     * Копирует файл из удаленного хранилища в локальное хранилище
     *
     * @return void
     */
    protected function copyFileFromRemoteStorage(): void
    {
        $tmpPath = $this->getTmpPath();

        try {
            $remoteFile = Storage::get($this->import->getPath());

            if (!$remoteFile) {
                throw new FileNotFoundException('Не удалось получить файл импорта');
            }

            $isCopied = Storage::disk('public')->put($tmpPath, $remoteFile);

            if (!$isCopied) {
                throw new UnableToWriteFile('Не удалось скопировать полученный файл');
            }
        } catch (Throwable $throwable) {
            Log::error(
                '[Import] Ошибка копирования файла из удаленного хранилища в локальное',
                array_merge($this->import->getLogData(), [
                    'tmpPath'   => $tmpPath,
                    'exception' => $throwable,
                ])
            );
        }
    }

    /**
     * Удаляет файл из локального хранилища
     *
     * @return void
     */
    protected function removeFileInLocalStorage(): void
    {
        $tmpPath = $this->getTmpPath();

        try {
            $isRemoved = Storage::disk('public')->delete($tmpPath);

            if (!$isRemoved) {
                throw new UnableToDeleteFile('Не удалось удалить временный файл импорта');
            }
        } catch (Throwable $throwable) {
            Log::error(
                '[Import] Ошибка удаления файла из временного хранилища',
                array_merge($this->import->getLogData(), [
                    'tmpPath'   => $tmpPath,
                    'exception' => $throwable,
                ])
            );
        }
    }

    /**
     * Собирает массив из файла
     *
     * @param  \PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $sheet
     * @return void
     */
    protected function buildSheet(Worksheet $sheet): void
    {
        foreach ($sheet->getRowIterator() as $row) {
            $cellIterator = $row->getCellIterator();
            $cellIterator->setIterateOnlyExistingCells(true);
            $cells = [];

            foreach ($cellIterator as $cell) {
                if (Date::isDateTime($cell)) {
                    $cells[] = $this->convertExcelDate($cell->getValue());

                    continue;
                }

                $cells[] = $cell->getValue();
            }

            if (count($cells)) {
                $this->sheet[] = $cells;
            }
        }

        $this->sheetHeader = array_shift($this->sheet);
    }

    /**
     * Собирает периоды
     *
     * @return void
     */
    protected function buildPeriods(): void
    {
        foreach ($this->sheetHeader as $cellKey => $cellVal) {
            if (strtotime($cellVal)) {
                $this->finalSheet[$cellVal] = [
                    'companyUuid' => $this->import->getCompanyUuid(),
                    'period'      => $cellVal,
                    'fillType'    => config('company.fillType.import'),
                ];

                $this->setPeriodStartIndex($cellKey);
            }
        }
    }

    /**
     * Собирает финальный массив для сохранения в базу
     *
     * @return void
     */
    protected function buildFinalData(): void
    {
        foreach ($this->sheet as $row) {
            $code      = $this->getCode($row);
            $cellIndex = $this->periodStartIndex;

            foreach ($this->finalSheet as $periodKey => $periodValue) {
                $this->finalSheet[$periodKey]['nc_' . $code] = $row[$cellIndex];
                $cellIndex++;
            }
        }
    }

    /**
     * Удаляет строки в которых отсутствуют коды
     *
     * @return void
     */
    protected function removeRowsWithoutCodes(): void
    {
        $this->sheet = array_filter($this->sheet, function ($k) {
            return (bool) $this->getCode($this->sheet[$k]);
        }, ARRAY_FILTER_USE_KEY);
    }

    /**
     * Устанавливает индекс ячейки с которого начинаются данные периодов
     *
     * @param  int  $index
     * @return void
     */
    protected function setPeriodStartIndex(int $index): void
    {
        if (!$this->periodStartIndex) {
            $this->periodStartIndex = $index;
        }
    }

    /**
     * Возвращает код строки
     *
     * @param  array  $row
     * @return string
     */
    protected function getCode(array $row): string
    {
        return (string) collect($row)->first(function ($cell) {
            return array_key_exists($cell, config('company.' . $this->sheetName));
        });
    }

    /**
     * Возвращает временный путь к файлу
     *
     * @return string
     */
    protected function getTmpPath(): string
    {
        return 'tmp/' . $this->import->getPath();
    }

    /**
     * Конвертирует эксель дату
     *
     * @param  int    $exelDate
     * @param  string $format
     * @return string
     */
    protected function convertExcelDate(int $exelDate, string $format = 'Y-m-d'): string
    {
        return date($format, Date::excelToTimestamp($exelDate));
    }
}
