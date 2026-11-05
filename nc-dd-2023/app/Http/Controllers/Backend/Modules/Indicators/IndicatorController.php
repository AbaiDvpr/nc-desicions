<?php declare(strict_types=1);

namespace App\Http\Controllers\Backend\Modules\Indicators;

use App\DI;
use App\Http\Controllers\Backend\BaseController;
use App\Http\Requests\Backend\Indicators\StoreRequest;
use App\Http\Requests\Backend\Indicators\UpdateRequest;
use App\Jobs\Backend\Indicators\Indicators as IndicatorsJob;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Log;

/**
 * Контроллер для работы с показателями
 */
class IndicatorController extends BaseController
{
    /**
     * Открывает страницу списка записей
     *
     * @return \Illuminate\Contracts\View\View
     */
    public function index(): View
    {
        return view('backend.modules.indicators.index', [
            'indicators' => DI::getRepository()->getIndicators()->getLatest()
        ]);
    }

    /**
     * Открывает страницу на создание записи
     *
     * @return \Illuminate\Contracts\View\View
     */
    public function create(): View
    {
        return view('backend.modules.indicators.create', [
            'date' => Carbon::now()->addDay()->format('Y-m-d')
        ]);
    }

    /**
     * Сохраняет запись
     *
     * @param  \App\Http\Requests\Backend\Indicators\StoreRequest $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(StoreRequest $request): RedirectResponse
    {
        try {
            $data = $request->only([
                'relevant',
                'inflation',
                'annualInflation',
                'baseRate',
                'tonia'
            ]);

            DI::getRepository()->getIndicators()->storeFromArray($data);

            return redirect()->route('backend.indicators.index')
                ->with(['success' => 'Показатель успешно добавлен']);
        } catch (\Throwable $throwable) {
            Log::error('[Indicators] Ошибка при сохранении', [
                'exception' => $throwable
            ]);

            return redirect()->back()->withErrors(['Произошла непредвиденная ошибка']);
        }
    }

    /**
     * Открывает страницу на редактирование записи
     *
     * @param  int                             $indicator
     * @return \Illuminate\Contracts\View\View
     */
    public function edit(int $indicator): View
    {
        return view('backend.modules.indicators.edit', [
            'indicator' => DI::getRepository()->getIndicators()->getById($indicator)
        ]);
    }

    /**
     * Обновляет запись
     *
     * @param  \App\Http\Requests\Backend\Indicators\UpdateRequest $request
     * @param  int                                                 $indicator
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(UpdateRequest $request, int $indicator): RedirectResponse
    {
        try {
            $data = $request->only([
                'inflation',
                'annualInflation',
                'baseRate',
                'tonia'
            ]);

            DI::getRepository()->getIndicators()->updateFromArray($indicator, $data);

            return redirect()->route('backend.indicators.index')
                ->with(['success' => 'Показатель успешно изменен']);
        } catch (\Throwable $throwable) {
            Log::error('[Indicators] Ошибка при обновлении', [
                'exception' => $throwable
            ]);

            return redirect()->back()->withErrors(['Произошла непредвиденная ошибка']);
        }
    }

    /**
     * Удаляет запись по ID
     *
     * @param  int                              $indicator
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy(int $indicator): RedirectResponse
    {
        try {
            DI::getRepository()->getIndicators()->removeById($indicator);

            return redirect()->back()
                ->with(['success' => 'Показатель успешно удален']);
        } catch (\Throwable $throwable) {
            Log::error('[Indicators] Ошибка при удалении', [
                'exception' => $throwable
            ]);

            return redirect()->back()->withErrors(['Произошла непредвиденная ошибка']);
        }
    }

    /**
     * Запускает джобу для получения данных
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function getIndicators(): RedirectResponse
    {
        try {
            IndicatorsJob::dispatch()->onQueue('parsing')->delay(now()->addSeconds(5));

            return redirect()->back()
                ->with([
                    'success' => 'Запрос на получение показателя отправлен. Уведомление о получении показателя получите позднее.'
                ]);
        } catch (\Throwable $throwable) {
            Log::error('[Indicators] Ошибка при запуске job на получение данных', [
                'exception' => $throwable
            ]);

            return redirect()->back()->withErrors(['Произошла непредвиденная ошибка']);
        }
    }
}
