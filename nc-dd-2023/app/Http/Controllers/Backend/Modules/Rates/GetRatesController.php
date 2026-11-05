<?php declare(strict_types=1);

namespace App\Http\Controllers\Backend\Modules\Rates;

use App\Http\Controllers\Backend\BaseController;
use App\Jobs\Backend\Rates\GetRatesJob;
use Illuminate\Http\RedirectResponse;

/**
 * Ручной запуск получения курсов валют
 */
class GetRatesController extends BaseController
{
    /**
     * @return RedirectResponse
     */
    public function __invoke(): RedirectResponse
    {
        try {
            GetRatesJob::dispatch()->onQueue('parsing')->delay(now()->addSeconds(5));

            return redirect()->back()->with([
                'success' => 'Запрос на получение валюты отправлен. Уведомление о получении курсов получите позднее.'
            ]);
        } catch (\Exception $exception) {
            return redirect()->back()->withErrors(['Произошла непредвиденная ошибка. Ошибка: ' . $exception->getMessage()]);
        }
    }
}
