<?php declare(strict_types=1);

namespace App\Http\Controllers\Frontend\Modules;

use App\DI;
use App\Http\Controllers\Frontend\BaseController;
use App\Services\NewsService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\View as ViewFacade;

/**
 * Контроллер для новостей
 */
class NewsController extends BaseController
{
    /**
     * Возвращает все новости
     *
     * @param string                           $slug
     * @return \Illuminate\Contracts\View\View
     */
    public function index(string $slug): View
    {
        // TODO: Унести в репозиторий и подумать над кешированием первой страницы
        $news     = (new NewsService())->findByCategorySlug($slug)->paginate(6);
        $category = DI::getRepository()->getModelsCategory()->getOneBySlug($slug);

        return view('frontend.modules.news.index', [
           'news'     => $news,
           'category' => $category,
        ]);
    }

    /**
     * Возвращает конкретную новость
     *
     * @param string                           $slug
     * @param int                              $id
     * @return \Illuminate\Contracts\View\View
     */
    public function show(string $slug, int $id): View
    {
        $record = DI::getRepository()->getModelsNews()->getOnePublished($id);

        if (is_null($record)) {
            abort(404);
        }

        return view('frontend.modules.news.show', compact('record'));
    }
}
