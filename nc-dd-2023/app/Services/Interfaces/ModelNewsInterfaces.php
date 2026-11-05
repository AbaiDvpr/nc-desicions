<?php
namespace App\Services\Interfaces;


interface ModelNewsInterfaces
{
    /**
     * @param $slug
     * @return mixed
     */
    public function findByCategorySlug($slug);
}
