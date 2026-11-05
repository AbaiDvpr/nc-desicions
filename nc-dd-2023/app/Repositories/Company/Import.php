<?php declare(strict_types=1);

namespace App\Repositories\Company;

use App\Models\Company\Import as ImportModel;
use App\Traits\Cache\Cacheable;
use Exception;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Jobs\Cabinet\Company\Import as ImportJob;

/**
 * Репозиторий импортов
 */
class Import
{
    use Cacheable;

    /**
     * Время жизни кеша (1 час)
     */
    const CACHE_LIVE_TIME = 3600;

    /**
     * Сохранение импорта и диспатч джобы
     *
     * @param  \Illuminate\Foundation\Http\FormRequest $request
     * @return int|null
     */
    public function save(FormRequest $request): ?int
    {
        try {
            $file     = $request->file('file');
            $filePath = $file->store(ImportModel::DIRECTORY_PATH);

            $import = DB::transaction(function () use ($request, $filePath) {
                $import = new ImportModel();

                $import->setCompanyUuid($request->input('companyUuid'));
                $import->setPath($filePath);
                $import->setIp($request->ip());

                if (!$import->save()) {
                    Log::error('[Import] Ошибка сохранения импорт файла', $import->getLogData());

                    return null;
                }

                return $import;
            }, 2);

            if ($import) {
                ImportJob::dispatch($import->getId())->onQueue('import');

                return $import->getId();
            }
        } catch (Exception $exception) {
            Log::error('[Import] Ошибка сохранения импорт файла', [
                'exception' => $exception
            ]);
        }

        return null;
    }

    /**
     * Возвращает импорт по ID
     *
     * @param  int                             $id
     * @param  bool                            $useCache
     * @return \App\Models\Company\Import|null
     */
    public function getById(int $id, bool $useCache = true): ?ImportModel
    {
        $func = function () use ($id) {
            return (new ImportModel())->find($id);
        };

        if ($useCache) {
            return $this->getCache()->getCached(
                $this->getCacheKey($id),
                $func,
                self::CACHE_LIVE_TIME
            );
        }

        return $func();
    }

    /**
     * Возвращает незавершенные импорты
     *
     * @param  \Illuminate\Http\Request                 $request
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getUncompleted(Request $request): Collection
    {
        return ImportModel::where('companyUuid', $request->input('companyUuid'))
            ->where('status', false)
            ->get();
    }

    /**
     * Устанавливает статус импорта завершенным
     *
     * @param  int                             $id
     * @return \App\Models\Company\Import|null
     */
    public function setCompleted(int $id): ?ImportModel
    {
        $import = $this->getById($id);

        if (is_null($import)) {
            return null;
        }

        $import->setStatus(true);
        $import->save();

        $this->getCache()->forget($this->getCacheKey($id));

        return $import;
    }

    /**
     * Удаляет импорт
     *
     * @param  \App\Models\Company\Import $import
     * @return bool
     */
    public function delete(ImportModel $import): bool
    {
        return DB::transaction(function () use ($import) {
            $this->getCache()->forget($this->getCacheKey($import->getId()));

            return (bool) $import->delete();
        }, 2);
    }
}
