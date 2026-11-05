<?php declare(strict_types=1);

namespace App\Repositories\Models;

use App\Repositories\BaseRepository;
use \Illuminate\Database\Eloquent\Collection;
use App\Models\News as NewsModel;
use Illuminate\Database\RecordsNotFoundException;
use Illuminate\Support\Facades\DB;

/**
 * Репозиторий для работы с новостями
 */
class News extends BaseRepository
{
    /**
     * Ключ кеша для новостей
     */
    const CACHE_KEY = 'news';

    /**
     * Время жизни кеша (1 день)
     */
    const CACHE_LIVE_TIME = 86400;

    /**
     * Получает новость по ID
     *
     * @param  int                   $id
     * @param  bool                  $useCache
     * @return \App\Models\News|null
     */
    public function getOne(int $id, bool $useCache = true): ?NewsModel
    {
        $func = function() use ($id) {
            return NewsModel::where('id', $id)->first();
        };

        if ($useCache) {
            return $this->getCache()->getCached(
                $this->getCacheKey(self::CACHE_KEY, $id),
                $func,
                self::CACHE_LIVE_TIME,
                $this->getCacheTags()
            );
        }

        return $func();
    }

    /**
     * Получает опубликованную новость для просмотра на сайте
     *
     * @param  int                   $id
     * @param  bool                  $useCache
     * @return \App\Models\News|null
     */
    public function getOnePublished(int $id, bool $useCache = true): ?NewsModel
    {
        $func = function() use ($id) {
            return NewsModel::where('id', $id)
                ->whereDraft(false)
                ->whereGood(true)
                ->with(['category', 'media'])
                ->first();
        };

        if ($useCache) {
            return $this->getCache()->getCached(
                $this->getCacheKey('newsPublished:', $id),
                $func,
                self::CACHE_LIVE_TIME,
                $this->getCacheTags()
            );
        }

        return $func();
    }

    /**
     * Сохраняет новость
     *
     * @param  \App\Models\News $news
     * @param  array            $data
     * @return bool
     */
    public function save(NewsModel $news, array $data): bool
    {
        return DB::transaction(function () use ($news, $data) {
            $this->getCache()->deleteByTags(
                $this->getCacheTags()
            );

            return $news->update($data);
        });
    }

    /**
     * Создает новость
     *
     * @param  array            $data
     * @return \App\Models\News
     */
    public function create(array $data): NewsModel
    {
        return DB::transaction(function () use ($data) {
            return NewsModel::create($data);
        });
    }

    /**
     * Удаляет новость
     *
     * @param  int  $id
     * @return bool
     */
    public function delete(int $id): bool
    {
        $record = $this->getOne($id);

        if (is_null($record)) {
            throw new RecordsNotFoundException('Новость не найдена');
        }

        return DB::transaction(function () use ($record) {
            $this->getCache()->deleteByTags(
                $this->getCacheTags()
            );

            return $record->delete();
        });
    }

    /**
     * Возвращает промо новости на главную страницу
     *
     * @param  int                                      $limit
     * @param  bool                                     $useCache
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getPromo(int $limit = 3, bool $useCache = true): Collection
    {
        $func = function() use ($limit) {
            return NewsModel::whereDraft(false)
                ->whereGood(true)
                ->latest()
                ->limit($limit)
                ->with(['category', 'media'])
                ->get();
        };

        if ($useCache) {
             return $this->getCache()->getCached(
                $this->getCacheKey('promo'),
                $func,
                self::CACHE_LIVE_TIME,
                $this->getCacheTags()
            );
        }

        return $func();
    }

    /**
     * Получает список последних новостей
     *
     * @return mixed
     */
    public function getLatest(int $limit = 3, bool $useCache = true): Collection
    {
        $func = function() use ($limit) {
            return NewsModel::whereDraft(false)
                ->whereGood(true)
                ->limit($limit)
                ->with(['category', 'media'])
                ->latest()
                ->get();
        };

        if ($useCache) {
            return $this->getCache()->getCached(
                $this->getCacheKey('latest-news'),
                $func,
                self::CACHE_LIVE_TIME,
                $this->getCacheTags()
            );
        }

        return $func();
    }

    /**
     * Возвращает тэги кеша по которым разделяются новости
     *
     * @return string[]
     */
    private function getCacheTags(): array
    {
        return [
            'news-promo',
            'published',
            'latest-news'
        ];
    }
}
