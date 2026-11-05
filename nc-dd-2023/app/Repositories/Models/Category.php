<?php declare(strict_types=1);
namespace App\Repositories\Models;

use App\Models\Categories;
use App\Repositories\BaseRepository;

/**
 * Репозиторий категорий новостей
 */
class Category extends BaseRepository
{
    /**
     * @param  string                      $slug
     * @return \App\Models\Categories|null
     */
    public function getOneBySlug(string $slug): ?Categories
    {
        return Categories::whereSlug($slug)->first();
    }
}
