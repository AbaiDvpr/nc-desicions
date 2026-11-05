<?php declare(strict_types=1);

namespace App\DataHandlers;

use Composer\Autoload\ClassLoader;

/**
 * Базовый класс загрузку DataHandlers
 *
 * @method \App\DataHandlers\Company getCompany
 * @method \App\DataHandlers\Project getProject
 * @method \App\DataHandlers\Widget  getWidget
 */
class DataHandlerLoader
{
    /**
     * Локальный кеш объектов DH
     *
     * @var array
     */
    protected array $cache = [];

    /**
     * Список директорий DH, которые нужны для корректной работы магического метода __get
     *
     * @var array
     */
    protected array $directoryMap = [];

    /**
     * Возвращает объект DH для указанной сущности
     *
     * @param  string                                 $dataHandler
     * @return \App\DataHandlers\DataHandlerInterface
     */
    public function getDH(string $dataHandler): DataHandlerInterface
    {
        $basePath = 'App\DataHandlers';

        if (mb_strpos($dataHandler, $basePath) === 0) {
            $className = $dataHandler;
        } else {
            $className = $basePath . '\\' . $dataHandler;
        }

        if (!array_key_exists($className, $this->cache)) {
            if (!class_exists($className) && !(new ClassLoader())->loadClass($className)) {
                throw new \InvalidArgumentException(sprintf(
                    'Unable to load DataHandler %s',
                    $className
                ));
            }

            $this->cache[$className] = new $className();
        }

        return $this->cache[$className];
    }

    /**
     * Возвращает объект обработчика данных для вызова метода вида getDataHandlerName
     *
     * @param                                         $name
     * @param                                         $arguments
     * @return \App\DataHandlers\DataHandlerInterface
     */
    public function __call($name, $arguments): DataHandlerInterface
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

        return $this->getDH($name);
    }
}
