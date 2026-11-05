<?php declare(strict_types=1);

namespace App\Traits\Cache;

use App\DI;
use App\Repositories\Cache\Cache;

trait Cacheable
{
    /**
     * @return \App\Repositories\Cache\Cache
     */
    public function getCache(): Cache
    {
        return DI::getRepository()->getCache();
    }

    /**
     * Получение ключа для кеша в зависимости от класса и аргументов где он вызывается
     *
     * @param ...$args
     * @return string
     */
    protected function getCacheKey(...$args): string
    {
        return get_class($this) . ':' . implode('_', $args);
    }

    /**
     * Тэги кеша по определенному проекту
     *
     * @param  string $uuid
     * @param  array  $keys
     * @return array
     */
    public function getProjectCacheTags(string $uuid, array $keys = []): array
    {
        return array_merge([
            sprintf('App\Repositories\Report\Report:%s', $uuid)
        ], $keys);
    }
}
