<?php
namespace App\Services\Interfaces;


interface ModelPageInterfaces
{
    /**
     * @param string $slug
     * @return mixed
     */
    public function findBySlug(string $slug);

    /**
     * @param int $id
     * @return mixed
     */
    public function findById(int $id);

    /**
     * @return mixed
     */
    public function findAll();
}
