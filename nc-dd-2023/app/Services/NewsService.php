<?php declare(strict_types=1);

namespace App\Services;

use App\Models\Categories;
use App\Models\News;
use App\Services\Interfaces\ModelNewsInterfaces;

/**
 * TODO: избавиться от этого гавна
 */
class NewsService implements ModelNewsInterfaces
{
    /**
     * Найти новости по категории которую находим по алиасу категории
     *
     * @param  string $slug
     * @return mixed
     */
    public function findByCategorySlug($slug)
    {
        $category = Categories::whereSlug((string) $slug)->firstOrFail();

        return News::where('category_id', $category->id)
            ->whereDraft(false)
            ->whereGood(true)
            ->with(['category', 'media'])
            ->latest();
    }
}
