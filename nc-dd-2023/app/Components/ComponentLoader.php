<?php declare(strict_types=1);

namespace App\Components;

use Composer\Autoload\ClassLoader;

/**
 * Базовый класс для загрузки компонентов
 *
 * @method \App\Components\Accounting getAccounting
 */
class ComponentLoader
{
    /**
     * Локальный кеш компонентов
     *
     * @var array
     */
    protected array $cache = [];

    /**
     * Список директорий компонентов, которые нужны для корректной работы магического метода __get
     *
     * @var array
     */
    protected array $directoryMap = [];

    /**
     * Возвращает объект компонента для указанной сущности
     *
     * @param  string                             $component
     * @return \App\Components\ComponentInterface
     */
    public function getComponent(string $component): ComponentInterface
    {
        $basePath = 'App\Components';

        if (mb_strpos($component, $basePath) === 0) {
            $className = $component;
        } else {
            $className = $basePath . '\\' . $component;
        }

        if (!array_key_exists($className, $this->cache)) {
            if (!class_exists($className) && !(new ClassLoader())->loadClass($className)) {
                throw new \InvalidArgumentException(sprintf(
                    'Unable to load Component %s',
                    $className
                ));
            }

            $this->cache[$className] = new $className();
        }

        return $this->cache[$className];
    }

    /**
     * Возвращает объект компонента для вызова метода вида getComponentName
     *
     * @param                                     $name
     * @param                                     $arguments
     * @return \App\Components\ComponentInterface
     */
    public function __call($name, $arguments): ComponentInterface
    {
        if (mb_strpos($name, 'get') === false) {
            throw new \BadMethodCallException('No such method: ' . $name);
        }

        $name = mb_substr($name, 3);

        foreach ($this->directoryMap as $dir) {
            $dirClear = str_replace('\\', '', $dir);

            if (mb_strpos($name, $dirClear) === 0) {
                $name = $dir . '\\' . mb_substr($name, mb_strlen($dirClear));

                $lastCharPos = mb_strlen($name) - 1;

                if ($name[$lastCharPos] === '\\') {
                    $name .= $dir;
                }
            }
        }

        return $this->getComponent($name);
    }
}
