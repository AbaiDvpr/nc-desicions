<?php declare(strict_types=1);
namespace App\Http\Controllers\Backend\Courses;

use App\DI;
use App\Http\Controllers\Backend\BaseController;
use App\Models\Courses\Forms\Lesson as LessonForm;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Log;

/**
 * Контроллер для работы с уроками курсов
 */
class LessonsController extends BaseController
{
    /**
     * Отображение главной страницы
     *
     * @return \Illuminate\Contracts\View\View
     */
    public function index(): View
    {
        return view('backend.courses.lessons.index', [
            'lessons' => DI::getRepository()->getCourses()->getLesson()->getWithPaginate(),
        ]);
    }

    /**
     * Страница создания урока
     *
     * @return \Illuminate\Contracts\View\View
     */
    public function create(): View
    {
        return view('backend.courses.lessons.create', [
            'modules' => DI::getRepository()->getCourses()->getModule()->getAllForSelect(),
        ]);
    }

    /**
     * Страница редактирования урока
     *
     * @param  string                         $id
     * @return \Illuminate\Contracts\View\View
     */
    public function edit(string $id): View
    {
        $lesson = DI::getRepository()->getCourses()->getLesson()->getOne($id);

        if (is_null($lesson)) {
            return abort(404);
        }

        return view('backend.courses.lessons.edit', [
            'lesson'  => $lesson,
            'module'  => $lesson->module(),
            'modules' => DI::getRepository()->getCourses()->getModule()->getAllForSelect(),
        ]);
    }

    /**
     * Обновляет урок
     *
     * @param  \Illuminate\Foundation\Http\FormRequest $request
     * @param  string                                  $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(FormRequest $request, string $id): RedirectResponse
    {
        try {
            DI::getRepository()->getCourses()->getLesson()->save(
                LessonForm::createFromRequest($request),
                $id
            );
        } catch (\Throwable $throwable) {
            Log::error('[Courses] Ошибка при обновления урока курса', [
                'exception' => $throwable,
            ]);

            return redirect()->back()->withInput($request->all());
        }

        return redirect()->route('backend.courses.lessons.index')->with([
            'success' => 'Данные сохранены',
        ]);
    }

    /**
     * Сохраняет урок
     *
     * @param  \Illuminate\Foundation\Http\FormRequest $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(FormRequest $request)
    {
        try {
            DI::getRepository()->getCourses()->getLesson()->save(
                LessonForm::createFromRequest($request)
            );
        } catch (\Throwable $throwable) {
            Log::error('[Courses] Ошибка при сохранении урока курса', [
                'exception' => $throwable,
            ]);

            return redirect()->back()->withInput($request->all());
        }

        return redirect()->route('backend.courses.lessons.index')->with([
            'success' => 'Данные сохранены',
        ]);
    }

    /**
     * Обновляет порядок урока
     *
     * @param  \Illuminate\Foundation\Http\FormRequest $request
     * @param  string                                  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateOrder(FormRequest $request, string $id): JsonResponse
    {
        $lesson  = DI::getRepository()->getCourses()->getLesson()->getOne($id);
        $order   = (int) $request->input('order');
        $message = 'Произошла ошибка во время сохранения, попробуйте позднее';

        try {
            $isSaved = DI::getRepository()->getCourses()->getLesson()->setOrder($lesson, $order);

            return response()->json([
                'success' => $isSaved,
                'message' => $isSaved ? 'Порядок обновлен' : $message
            ]);
        } catch (\Throwable $throwable) {
            Log::error('[Courses] Ошибка при сохранении порядка урока', [
                'exception' => $throwable
            ]);
        }

        return response()->json(['message' => $message], 500);
    }
}
