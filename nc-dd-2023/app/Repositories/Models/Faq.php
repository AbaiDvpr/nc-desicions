<?php declare(strict_types=1);

namespace App\Repositories\Models;

use App\Jobs\Frontend\Faq\FaqAdminSend;
use App\Jobs\Frontend\Faq\FaqUserSend;
use App\Repositories\BaseRepository;
use Illuminate\Database\Eloquent\Collection;
use App\Models\Faq as FaqModel;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class Faq extends BaseRepository
{
    /**
     * Получение определенного вопроса
     *
     * @param  int                  $id
     * @return \App\Models\Faq|null
     */
    public function getOne(int $id): ?FaqModel
    {
        return $this->getCache()->getCached(
            $this->getCacheKey($id),
            function () use ($id) {
                return FaqModel::find($id);
            },
            mt_rand(21600, 43200),
            $this->getTags($id)
        );
    }

    /**
     * Сохранение вопроса и сброс кеша для него
     *
     * @param  int   $id
     * @param  array $fields
     * @return bool
     */
    public function save(int $id, array $fields): bool
    {
        $faq = $this->getOne($id);

        if (is_null($faq)) {
            throw new ModelNotFoundException('Вопрос не найден');
        }

        $this->getCache()->deleteByTags(
            $this->getTags($faq->id)
        );

        return $faq->update($fields);
    }

    /**
     * Удаление вопроса
     *
     * @param  \App\Models\Faq $faq
     * @return bool|null
     */
    public function delete(FaqModel $faq): ?bool
    {
        $this->getCache()->deleteByTags($this->getTags($faq->id));

        return $faq->delete();
    }

    /**
     * Возвращает весь список вопросов
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getAll(): Collection
    {
        return FaqModel::all();
    }

    /**
     * Запрос за получением списка опубликованных вопросов на определенном языке
     *
     * @return mixed
     */
    public function getPublished(): mixed
    {
        return FaqModel::where('lang', app()->getLocale())->wherePublished(true);
    }

    /**
     * Возвращает список вопросов для главной страницы
     *
     * @return mixed
     */
    public function getMainQuestions(): mixed
    {
        return $this->getCache()->getCached(
            $this->getCacheKey('main', app()->getLocale()),
            function () {
                return FaqModel::where('lang', app()->getLocale())
                    ->where('main', true)
                    ->where('published', true)
                    ->limit(5)
                    ->get();
            },
            mt_rand(21600, 43200),
            $this->getTags('main')
        );
    }

    /**
     * Возвращает набор тегов для кеша
     *
     * @param  ...$args
     * @return array
     */
    protected function getTags(...$args): array
    {
        return [
            get_class($this),
            get_class($this) . ':' . implode('_', $args)
        ];
    }

    /**
     * Создание записи
     *
     * @param  array $data
     * @return bool
     */
    public function storeFromArray(array $data): bool
    {
        try {
            $faq = DB::transaction(function () use ($data) {
                return FaqModel::create($data);
            }, 2);

            if ($faq) {
                FaqAdminSend::dispatch($faq->id);
                FaqUserSend::dispatch($faq->id);

                return true;
            }
        } catch (\Throwable $throwable) {
            Log::error('[FAQ] Ошибка при сохранении нового вопроса', [
                'exception' => $throwable,
                'data'      => $data
            ]);
        }

        return false;
    }
}
