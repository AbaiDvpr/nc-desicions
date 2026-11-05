<?php declare(strict_types=1);

namespace App\Services;

use App\Models\Pages;
use App\Services\Interfaces\ModelPageInterfaces;
use Illuminate\Database\Eloquent\Collection;


class PageService implements ModelPageInterfaces
{
    /**
     * Найти страницу по Алиасу
     * @param string $slug
     * @return mixed
     */
    public function findBySlug(string $slug)
    {
       return Pages::whereAlias($slug)->whereGood(true)->firstOrFail();
    }

    /**
     * Найти страницу по айди
     * @param int $id
     * @return mixed
     */
    public function findById(int $id)
    {
       return Pages::find($id);
    }

    /**
     * Найти все страницы
     * @return Pages[]|Collection|mixed
     */
    public function findAll()
    {
       return Pages::all();
    }

}
