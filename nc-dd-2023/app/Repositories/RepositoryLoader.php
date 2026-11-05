<?php declare(strict_types=1);

namespace App\Repositories;

use Composer\Autoload\ClassLoader;

/**
 * Загрузчик репозиториев
 *
 * @method \App\Repositories\JwtToken              getJwtToken
 * @method \App\Repositories\User                  getUser
 * @method \App\Repositories\UserTariff            getUserTariff
 * @method \App\Repositories\Rates                 getRates
 * @method \App\Repositories\Project\Project       getProject
 * @method \App\Repositories\Cache\Cache           getCache
 * @method \App\Repositories\Models\Faq            getModelsFaq
 * @method \App\Repositories\Models\Feedback       getModelsFeedback
 * @method \App\Repositories\Models\Tariff         getModelsTariff
 * @method \App\Repositories\Invoice\Invoice       getInvoice
 * @method \App\Repositories\Payment\Payment       getPayment
 * @method \App\Repositories\Company               getCompany
 * @method \App\Repositories\Models\BalanceHistory getModelsBalanceHistory
 * @method \App\Repositories\MailHog               getMailHog
 * @method \App\Repositories\Indicators            getIndicators
 * @method \App\Repositories\RequestRefill         getRequestRefill
 * @method \App\Repositories\Handbook              getHandbook
 * @method \App\Repositories\Accounting            getAccounting
 * @method \App\Repositories\Models\News           getModelsNews
 * @method \App\Repositories\Models\Category       getModelsCategory
 * @method \App\Repositories\Counterparty          getCounterparty
 * @method \App\Repositories\Courses               getCourses
 */
class RepositoryLoader
{
    /**
     * Локальный кеш объектов-репозиториев.
     *
     * @var array
     */
    protected $cache = [];

    /**
     * Список директорий репозиториев, которые нужны для корректной работы магического метода __get
     *
     * @var array
     */
    protected array $directoryMap = [
        'Cache',
        'Project',
        'Models',
        'Invoice',
        'Payment',
    ];

    /**
     * Возвращает объект репозитория для указанной сущности.
     *
     * @param  string $repository
     * @return mixed
     */
    public function getRepository(string $repository)
    {
        $basePath = 'App\Repositories';

        if (mb_strpos($repository, $basePath) === 0) {
            $className = $repository;
        } else {
            $className = $basePath . '\\' . $repository;
        }

        if (!array_key_exists($className, $this->cache)) {
            if (!class_exists($className) && !(new ClassLoader())->loadClass($className)) {

                throw new \InvalidArgumentException(sprintf(
                    'Unable to load repository %s',
                    $className
                ));
            }

            $this->cache[$className] = new $className();
        }

        return $this->cache[$className];
    }

    /**
     * Возвращает объект обработчика данных по листам для вызова метода вида getDataHandlerName
     *
     * @param  $name
     * @param  $arguments
     * @return mixed
     */
    public function __call($name, $arguments)
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

        return $this->getRepository($name);
    }
}
