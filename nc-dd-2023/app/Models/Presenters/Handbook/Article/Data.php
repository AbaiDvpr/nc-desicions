<?php declare(strict_types=1);

namespace App\Models\Presenters\Handbook\Article;

use App\Models\Handbook\Article;

/**
 * Презентер для обработки данных справочника статей
 */
class Data
{
    /**
     * Базовая модель банка
     *
     * @var \App\Models\Handbook\Article
     */
    private Article $article;

    /**
     * Конструктор
     *
     * @param \App\Models\Handbook\Article $article
     */
    public function __construct(Article $article)
    {
        $this->article = $article;
    }

    /**
     * Возвращает публичные данные компании
     *
     * @return array
     */
    public function getPublicData(): array
    {
        return [
            'uuid'       => $this->article->getId(),
            'value'      => $this->article->getId(),
            'title'      => $this->article->getTitle(),
            'created_at' => $this->article->getCreatedAt()->format(DATE_ATOM),
        ];
    }
}
