<?php declare(strict_types=1);

namespace App\Repositories\Handbook;

use App\Models\Handbook\Article as ArticleModel;
use App\Models\Handbook\Forms\Article as ArticleForm;
use App\Repositories\Handbook;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Репозиторий для работы со справочником банков
 */
class Article extends Handbook
{
    /**
     * Возвращает статью по её идентификатору
     *
     * @param  string                            $id
     * @return \App\Models\Handbook\Article|null
     */
    public function getOne(string $id): ?ArticleModel
    {
        return (new ArticleModel())->find($id);
    }

    /**
     * Возвращает список всех статей
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getAll(): Collection
    {
        return (new ArticleModel())->get();
    }

    /**
     * Сохраняет статью в справочник
     *
     * @param \App\Models\Handbook\Forms\Article $form
     * @param string|null                        $id
     * @return \App\Models\Handbook\Article|null
     */
    public function save(ArticleForm $form, ?string $id = null): ?ArticleModel
    {
        return DB::transaction(function () use ($form, $id) {
            $article = $id ? $this->getOne($id) : new ArticleModel();

            $article->setTitle($form->getTitle());
            $article->setModerated(false);

            if (!$article->save()) {
                Log::error('Ошибка сохранения статьи доходов/расходов', [
                    'form' => $form->toArray()
                ]);

                return null;
            }

            return $article->refresh();
        }, 2);
    }

    /**
     * Удаление статьи доходов/расходов
     *
     * @param \App\Models\Handbook\Article $article
     * @return bool
     */
    public function delete(ArticleModel $article): bool
    {
        return DB::transaction(function () use ($article) {
            return (bool) $article->delete();
        });
    }

    /**
     * Получить список статей доходов/расходов из справочника с постраничной навигацией
     *
     * @param  array                                       $params
     * @return \Illuminate\Pagination\LengthAwarePaginator
     */
    public function getWithPaginate(array $params): LengthAwarePaginator
    {
        $articles = new ArticleModel();

        if (isset($params['title'])) {
            $articles = $articles->where('title', (string)$params['title']);
        }

        return $articles->latest('created_at')->paginate((int) $params['limit']);
    }
}
