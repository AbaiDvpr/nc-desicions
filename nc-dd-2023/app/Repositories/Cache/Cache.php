<?php declare(strict_types=1);

namespace App\Repositories\Cache;

use Illuminate\Support\Facades\Cache as CacheFacade;

/**
 * Репозиторий для работы с кешем
 *
 * TODO: по рефакторить
 */
class Cache implements CacheInterface
{
    /**
     * Разрешить запись в кеш
     *
     * @var bool
     */
    protected $enable = true;

    /**
     * Конструктор класса репозитория
     */
    public function __construct()
    {
        // Данная настройка позволит включать или выключать кеш в админке
        $this->enable = config('cache.enabled', true);
    }

    /**
     * @inheritdoc
     *
     * @param string $key
     * @return bool
     */
    public function has(string $key): bool
    {
        if ($this->enable) {
            return CacheFacade::has($key);
        }

        return true;
    }

    /**
     * @inheritdoc
     *
     * @param  string $key
     * @param  $value
     * @param  int    $ttl
     * @return bool
     */
    public function put(string $key, $value, int $ttl = 10): bool
    {
        if ($this->enable) {
            return CacheFacade::put($key, $value, $ttl);
        }

        return true;
    }

    /**
     * @inheritdoc
     *
     * @param  string  $key
     * @param  mixed   $default
     * @return mixed
     */
    public function get(string $key, $default = null)
    {
        if ($this->enable) {
            return CacheFacade::get($key, $default);
        }

        return $default;
    }

    /**
     * Возвращает значение из кэша, если оно есть, либо выполняет функцию для вычисления
     * требуемого значения и сохраняет его в кэш.
     *
     * @param  string   $key
     * @param  \Closure $callback
     * @param  int      $ttl
     * @param           $tags
     * @return mixed
     */
    public function getCached(string $key, \Closure $callback, int $ttl = 10, $tags = null): mixed
    {
        if ($this->enable) {
            if (!is_null($tags)) {
                return CacheFacade::tags($tags)->remember($key, $ttl, $callback);
            }

            return CacheFacade::remember($key, $ttl, $callback);
        }

        return $callback();
    }

    /**
     * @inheritdoc
     *
     * @param  string $key
     * @return bool
     */
    public function forget(string $key): bool
    {
        if ($this->enable) {
            return CacheFacade::forget($key);
        }

        return true;
    }

    /**
     * Удаляет объекты из кэша по тэгам
     * TODO работает только для дефолтного кеша (redis)
     *
     * @param  $tags
     * @return bool
     */
    public function deleteByTags($tags): bool
    {
        return CacheFacade::tags($tags)->flush();
    }
}
