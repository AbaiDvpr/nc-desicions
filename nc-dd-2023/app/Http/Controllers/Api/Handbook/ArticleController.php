<?php declare(strict_types=1);

namespace App\Http\Controllers\Api\Handbook;

use App\DI;
use App\Http\Controllers\Api\BaseController;
use App\Models\Handbook\Forms\Article;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Класс для проверки доступности сервисов
 */
class ArticleController extends BaseController
{
    /**
     * Возвращает список статей доходов/расходов
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getAll(): JsonResponse
    {
        $articles = DI::getRepository()->getHandbook()->getArticle()->getAll()->map(function ($article) {
            /** @var \App\Models\Handbook\Article $article */
            return [
                'value' => $article->getId(),
                'title' => $article->getTitle(),
            ];
        })->toArray();

        return response()->json($articles);
    }

    /**
     * Создает статью из запроса
     *
     * @param  \Illuminate\Http\Request      $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function create(Request $request): JsonResponse
    {
        $article = DI::getRepository()->getHandbook()->getArticle()->save(
            Article::createFromRequest($request)
        );

        if (!$article) {
            return response()->json([
                'message' => 'Не удалось добавить статью в справочник, обновите страницу или попробуйте позднее',
            ], 500);
        }

        return response()->json([
            'data' => collect($article->getPresenter()->getPublicData())->only(['value', 'title'])->toArray()
        ]);
    }
}
