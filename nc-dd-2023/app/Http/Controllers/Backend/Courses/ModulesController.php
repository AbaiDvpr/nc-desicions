<?php declare(strict_types=1);
namespace App\Http\Controllers\Backend\Courses;

use App\DI;
use App\Http\Controllers\Backend\BaseController;
use App\Models\Courses\Forms\Module as ModuleForm;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Log;

/**
 * Контроллер для работы с модулями курсов
 */
class ModulesController extends BaseController
{
    /**
     * Отображение главной страницы
     *
     * @return \Illuminate\Contracts\View\View
     */
    public function index(): View
    {
        return view('backend.courses.modules.index', [
            'modules' => DI::getRepository()->getCourses()->getModule()->getWithPaginate(),
        ]);
    }

    /**
     * Страница создания модуля
     *
     * @return \Illuminate\Contracts\View\View
     */
    public function create(): View
    {
        return view('backend.courses.modules.create');
    }

    /**
     * Страница редактирования модуля
     *
     * @param  string                         $id
     * @return \Illuminate\Contracts\View\View
     */
    public function edit(string $id): View
    {
        $module = DI::getRepository()->getCourses()->getModule()->getOne($id);

        if (is_null($module)) {
            return abort(404);
        }

        return view('backend.courses.modules.edit', compact('module'));
    }

    /**
     * Обновляет модуль
     *
     * @param  \Illuminate\Foundation\Http\FormRequest $request
     * @param  string                                  $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(FormRequest $request, string $id): RedirectResponse
    {
        try {
            DI::getRepository()->getCourses()->getModule()->save(
                ModuleForm::createFromRequest($request),
                $id
            );
        } catch (\Throwable $throwable) {
            Log::error('[Courses] Ошибка при обновления модуля курса', [
                'exception' => $throwable,
            ]);

            return redirect()->back()->withInput($request->all());
        }

        return redirect()->route('backend.courses.modules.index')->with([
            'success' => 'Данные сохранены',
        ]);
    }

    /**
     * Сохраняет модуль
     *
     * @param  \Illuminate\Foundation\Http\FormRequest $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(FormRequest $request)
    {
        try {
            DI::getRepository()->getCourses()->getModule()->save(
                ModuleForm::createFromRequest($request)
            );
        } catch (\Throwable $throwable) {
            Log::error('[Courses] Ошибка при сохранении модуля курса', [
                'exception' => $throwable,
            ]);

            return redirect()->back()->withInput($request->all());
        }

        return redirect()->route('backend.courses.modules.index')->with([
            'success' => 'Данные сохранены',
        ]);
    }

    /**
     * Обновляет порядок модуля
     *
     * @param  \Illuminate\Foundation\Http\FormRequest $request
     * @param  string                                  $moduleId
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateOrder(FormRequest $request, string $moduleId): JsonResponse
    {
        $module  = DI::getRepository()->getCourses()->getModule()->getOne($moduleId);
        $order   = (int) $request->input('order');
        $message = 'Произошла ошибка во время сохранения, попробуйте позднее';

        try {
            $isSaved = DI::getRepository()->getCourses()->getModule()->setOrder($module, $order);

            return response()->json([
                'success' => $isSaved,
                'message' => $isSaved ? 'Порядок обновлен' : $message
            ]);
        } catch (\Throwable $throwable) {
            Log::error('[Courses] Ошибка при сохранении порядка у модуля', [
                'exception' => $throwable
            ]);
        }

        return response()->json(['message' => $message], 500);
    }
}
