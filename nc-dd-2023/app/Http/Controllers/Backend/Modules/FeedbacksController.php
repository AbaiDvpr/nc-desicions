<?php declare(strict_types=1);

namespace App\Http\Controllers\Backend\Modules;

use App\DI;
use App\Http\Controllers\Backend\BaseController;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Контролер для формы "Обратная связь" в админ панели
 */
class FeedbacksController extends BaseController
{
    /**
     * Главная страница
     *
     * @return \Illuminate\Contracts\View\View
     */
    public function index(): View
    {
        return view('backend.modules.feedbacks.index', [
            'feedbacks' => DI::getRepository()->getModelsFeedback()->getWithPaginate()
        ]);
    }

    /**
     * Страница редактирования
     *
     * @param  int                            $id
     * @return \Illuminate\Contracts\View\View
     */
    public function edit(int $id): View
    {
        $feedback = DI::getRepository()->getModelsFeedback()->getOne($id);

        if (is_null($feedback)) {
            return abort(404);
        }

        return view('backend.modules.feedbacks.edit', [
            'feedback' => $feedback,
            'statuses' => config('feedbacks.statuses'),
        ]);
    }

    /**
     * Обновляет заявку
     *
     * @param  int                              $id
     * @param  \Illuminate\Http\Request         $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(int $id, Request $request): RedirectResponse
    {
        try {
            DI::getRepository()->getModelsFeedback()->update($id, $request->only(['status']));

            return redirect()->route('backend.modules.feedbacks.index')
                ->with(['success' => 'Заявка успешно обновлена']);
        } catch (\Throwable $throwable) {
            return redirect()->back()->withErrors([
                'Произошла непредвиденная ошибка. Ошибка: ' . $throwable->getMessage()
            ]);
        }
    }

    /**
     * Удаляет заявку
     *
     * @param  int                               $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy(int $id): RedirectResponse
    {
        try {
            DI::getRepository()->getModelsFeedback()->delete($id);

            return redirect()->back()->with([
                'success' => 'Заявка успешно удалена'
            ]);
        } catch (\Throwable $throwable) {
            Log::error('Ошибка во время удаления заявки', [
                'exception' => $throwable,
                'id'        => $id,
            ]);

            return redirect()->back()->withErrors([
                'Произошла ошибка во время удаления, попробуйте позднее'
            ]);
        }
    }
}
