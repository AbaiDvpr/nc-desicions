<?php declare(strict_types=1);
namespace App\Http\Controllers\Backend\Modules;

use App\DI;
use App\Http\Controllers\Backend\BaseController;
use App\Models\Categories;
use App\Models\News;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;

/**
 * Контроллер для работы с новостями
 */
class NewsController extends BaseController
{
    /**
     * Главная страница с новостями
     *
     * @param  \Illuminate\Http\Request        $request
     * @return \Illuminate\Contracts\View\View
     */
    public function index(Request $request): View
    {
        return view('backend.modules.news.index', [
            'news'       => $this->getNews($request)->latest()->paginate(30),
            'categories' => Categories::whereGood(true)->get(),
        ]);
    }

    /**
     * Создает пустую новость
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function create(): RedirectResponse
    {
        $news = DI::getRepository()->getModelsNews()->create([
            'created_id' => $this->user->id,
            'updated_id' => $this->user->id
        ]);

        return redirect()->route('backend.modules.news.edit', ['news' => $news->id]);
    }

    /**
     * Страница для редактирования новости
     *
     * @param  int                             $id
     * @return \Illuminate\Contracts\View\View
     */
    public function edit(int $id): View
    {
        $record     = DI::getRepository()->getModelsNews()->getOne($id);
        $categories = Categories::whereGood(true)->get();

        return view('backend.modules.news.edit', [
            'record'     => $record,
            'categories' => $categories,
        ]);
    }

    /**
     * Обновляет запись
     *
     * @param  int                               $id
     * @param  \Illuminate\Http\Request          $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(int $id, Request $request)
    {
        try {
            $post = Arr::collapse([$request->only(['title', 'short', 'full', 'good']), [
                'updated_id' => $this->user->id
            ]]);

            $record = DI::getRepository()->getModelsNews()->getOne($id);

            if ($request->input('category_id') != 0) {
                $record->setCategoryId((int) $request->input('category_id'));
            }

            $record->setDraft(false);

            DI::getRepository()->getModelsNews()->save($record, $post);

            return redirect()->route('backend.modules.news.index')
                ->with(['success' => 'Новость сохранена.']);
        } catch (\Exception $exception) {
            return redirect()->back()->withErrors([
                'Произошла непредвиденная ошибка. Ошибка: ' . $exception->getMessage()
            ]);
        }
    }

    /**
     * Удаление новости
     *
     * @param  int                               $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy(int $id): RedirectResponse
    {
        try {
            DI::getRepository()->getModelsNews()->delete($id);

            return redirect()->back()->with([
                'success' => 'Новость успешно удалена'
            ]);
        } catch (\Exception $exception) {
            Log::error('Ошибка удаления новости', [
                'exception' => $exception,
                'id'        => $id,
            ]);

            return redirect()->back()->withErrors([
                'Не удалось удалить новость, попробуйте позднее'
            ]);
        }
    }

    /**
     * Подготавливает запрос на получение списка новостей
     *
     * @param \Illuminate\Http\Request $request
     * @return Builder
     */
    protected function getNews(Request $request): Builder
    {
        $news = (new News())->newQuery();

        if ($request->input('title')) {
            $news = $news->where('title', 'LIKE', '%' . $request->input('title') . '%');
        }

        if ($request->input('category_id')) {
            $news = $news->where('category_id', $request->input('category_id'));
        }

        if (in_array($request->input('draft'), ["0", "1"])) {
            $news = $news->where('draft', $request->input('draft'));
        }

        if (in_array($request->input('good'), ["0", "1"])) {
            $news = $news->where('good', $request->input('good'));
        }

        if (in_array($request->input('deleted'), ["0", "1"])) {
            if ($request->input('deleted') === "1") {
                $news = $news->whereNull("deleted_at");
            } else {
                $news = $news->whereNotNull("deleted_at");
            }
        }

        return $news;
    }
}