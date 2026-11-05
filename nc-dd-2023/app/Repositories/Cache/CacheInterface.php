<?php declare(strict_types=1);

namespace App\Repositories\Cache;

interface CacheInterface
{
    /**
     * Проверяет присутствие элемента в кеше
     *
     * @param  string $key
     * @return bool
     */
    public function has(string $key): bool;

    /**
     * Получить элемент из кеша
     *
     * @param  string $key
     * @param  null   $default
     * @return mixed
     */
    public function get(string $key, $default = null);

    /**
     * Сохранение элементов в кеш
     *
     * @param  string $key
     * @param  $value
     * @param  int    $ttl (в секундах)
     * @return mixed
     */
    public function put(string $key, $value, int $ttl = 10): bool;

    /**
     * Получить элемент из кеша или сохранить его
     *
     * @param  string         $key
     * @param  \Closure       $callback
     * @param  int            $ttl
     * @param  array|mixed    $tags
     * @return \Closure|mixed
     */
    public function getCached(string $key, \Closure $callback, int $ttl = 10, $tags = null);

    /**
     * Удаление элемента из кеша
     *
     * @param  string $key
     * @return mixed
     */
    public function forget(string $key): bool;
}
