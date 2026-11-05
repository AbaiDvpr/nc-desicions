<?php declare(strict_types=1);

namespace App\Repositories\Project\Objects;

use App\Models\Project as ProjectModel;
use Illuminate\Database\Eloquent\Collection;

class Fot
{
    use \App\Traits\Cache\Cacheable;

    /**
     * Проект
     *
     * @var \App\Models\Project|null
     */
    protected ?ProjectModel $project;

    /**
     * Конструктор
     *
     * @param \App\Models\Project|null $project
     */
    public function __construct(?ProjectModel $project)
    {
        $this->project = $project;
    }

    /**
     * Возвращает всех сотрудников включая ФОТ и ПП/ВП
     *
     * @param  bool                                          $useCache
     * @return \Illuminate\Database\Eloquent\Collection|null
     */
    public function getAll(bool $useCache = true): ?Collection
    {
        if (is_null($this->project)) {
            return null;
        }

        $func = function () {
            return $this->project->salaryFund()->get();
        };

        if ($useCache) {
            return $this->getCache()->getCached(
                $this->getCacheKey('all', $this->project->getUuid()),
                $func,
                mt_rand(3600, 14400),
                $this->getProjectCacheTags($this->project->getUuid())
            );
        }

        return $func();
    }

    /**
     * Возвращает всех сотрудников АУП
     *
     * @param  bool                                          $useCache
     * @return \Illuminate\Database\Eloquent\Collection|null
     */
    public function getManagerialStaff(bool $useCache = true): ?Collection
    {
        if (is_null($this->project)) {
            return null;
        }

        $func = function () {
            return $this->project->salaryFund()->where('industrial', false)->oldest()->get();
        };

        if ($useCache) {
            return $this->getCache()->getCached(
                $this->getCacheKey('managerialStaff', $this->project->getUuid()),
                $func,
                mt_rand(3600, 14400),
                $this->getProjectCacheTags($this->project->getUuid())
            );
        }

        return $func();
    }

    /**
     * Возвращает всех сотрудников ПП/ВП
     *
     * @param  bool                                          $useCache
     * @return \Illuminate\Database\Eloquent\Collection|null
     */
    public function getIndustrial(bool $useCache = true): ?Collection
    {
        if (is_null($this->project)) {
            return null;
        }

        $func = function () {
            return $this->project->salaryFund()->where('industrial', true)->oldest()->get();
        };

        if ($useCache) {
            return $this->getCache()->getCached(
                $this->getCacheKey('industrial', $this->project->getUuid()),
                $func,
                mt_rand(3600, 14400),
                $this->getProjectCacheTags($this->project->getUuid())
            );
        }

        return $func();
    }
}
