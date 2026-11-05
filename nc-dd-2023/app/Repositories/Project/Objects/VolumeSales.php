<?php declare(strict_types=1);

namespace App\Repositories\Project\Objects;

use Illuminate\Database\Eloquent\Collection;
use App\Models\Project\VolumeSales as VolumeSalesModel;

/**
 * Объект для работы с элементами Объема продаж
 */
class VolumeSales extends Base
{
    /**
     * Возвращает всех сотрудников включая ФОТ и ПП/ВП
     *
     * @param  bool                                          $useCache
     * @return \Illuminate\Database\Eloquent\Collection|null
     */
    public function getAll(bool $useCache = true): ?Collection
    {
        if (!$this->project) {
            return null;
        }

        $func = function () {
            return $this->project->volumeSales;
        };

        if ($useCache) {
            return $this->getCache()->getCached(
                $this->getCacheKey($this->project->getUuid()),
                $func,
                mt_rand(3600, 14400),
                $this->getProjectCacheTags(
                    $this->project->getUuid(),
                    [$this->getCacheKey($this->project->getUuid())]
                )
            );
        }

        return $func();
    }

    /**
     * Сохраняет сущность в коллекцию Объема продаж и возвращает весь список
     *
     * @param  int|null                                      $id
     * @param  array                                         $fields
     * @return \Illuminate\Database\Eloquent\Collection|null
     */
    public function save(?int $id, array $fields): ?Collection
    {
        if (!$this->project) {
            return null;
        }

        try {
            $volumeSales = $this->project->volumeSales();

            if (is_null($id) || $id === 0) {
                $volumeSales->save(new VolumeSalesModel($fields));
            } else {
                VolumeSalesModel::findOrFail($id)->fill($fields)->save();
            }

            $this->forgetCache();
        } catch (\Throwable $throwable) {
            dump($throwable->getMessage()); exit(1);
        }

        return $this->getAll();
    }

    /**
     * Удаление элемента
     *
     * @param  int  $id
     * @return bool
     */
    public function delete(int $id): bool
    {
        $volumeSalesItem = $this->project->volumeSales()->findOrFail($id);

        if ($volumeSalesItem->delete()) {
            // удаляем и все связанные с ней значения в себестоимост
            $variableCoast = $volumeSalesItem->getVariableCoast();
            if ($variableCoast->count() > 0) {
                $variableCoast->delete();
            }

            $this->forgetCache();

            return true;
        }

        return false;
    }

    /**
     * Очищает кеш внутри для элементов данного класса
     *
     * @return bool
     */
    public function forgetCache(): bool
    {
        return $this->getCache()->deleteByTags(
            $this->getCacheKey($this->project->getUuid())
        );
    }
}
