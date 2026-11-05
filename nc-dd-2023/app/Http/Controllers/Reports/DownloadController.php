<?php declare(strict_types=1);

namespace App\Http\Controllers\Reports;

use App\Http\Controllers\BaseController;
use Illuminate\Http\Request;

class DownloadController extends BaseController
{
    /**
     * Скачивание отчета
     *
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     * @throws \PhpOffice\PhpSpreadsheet\Writer\Exception
     */
    public function __invoke(Request $request)
    {
        return \Report::downloadReport($request->uuid);
    }
}
