<?php declare(strict_types=1);

namespace App\Repositories;

use App\Models\Accounting as AccountingModel;
use App\Models\Accounting\Filters\PagerParams;
use App\Models\Accounting\Forms\Base;
use App\Models\Company as CompanyModel;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Репозиторий для работы со статьями доходов/расходов
 */
class Accounting extends BaseRepository
{
    /**
     * Ключ кеша для компаний
     */
    const CACHE_KEY = 'accounting';

    /**
     * Возвращает одну запись учёта по её ID
     *
     * @param string $uuid
     * @param bool   $useCache
     * @return \App\Models\Accounting|null
     */
    public function getOne(string $uuid, bool $useCache = false): ?AccountingModel
    {
        $func = function() use ($uuid) {
            return AccountingModel::find($uuid);
        };

        if ($useCache) {
            return $this->getCache()->getCached(
                $this->getCacheKey(self::CACHE_KEY, $uuid),
                $func,
                3600
            );
        }

        return $func();
    }

    /**
     * Удаление статьи доходов/расходов
     *
     * @param  string $uuid
     * @return bool
     */
    public function delete(string $uuid): bool
    {
        $accounting = $this->getOne($uuid);
        if (is_null($accounting)) {
            return false;
        }

        return DB::transaction(function () use ($accounting) {
            // TODO: сделать очистку кеша

            return $accounting->delete();
        }, 2);
    }

    /**
     * Сохраняет новую или обновляет существующую запись
     *
     * @param  \App\Models\Accounting\Forms\Base $form
     * @param  string|null                       $uuid
     * @return \App\Models\Accounting|null
     */
    public function save(Base $form, ?string $uuid = null): ?AccountingModel
    {
        return DB::transaction(function () use ($form, $uuid) {
            $accounting = !$uuid ? new AccountingModel() : $this->getOne($uuid);
            $accounting->fill($form->toArray());

            if (!$accounting->save()) {
                Log::error('Ошибка сохранения ежедневного учёта', [
                    'error' => DB::getQueryLog(),
                ]);
            }

            // TODO: добавить очистку кеша после редактирования

            return $accounting->refresh();
        }, 3);
    }

    /**
     * Возвращает список ежедневного учёта по параметрам фильтра
     *
     * @param \App\Models\Company                        $company
     * @param \App\Models\Accounting\Filters\PagerParams $filter
     * @return array
     */
    public function getFromParams(CompanyModel $company, PagerParams $filter): array
    {
        $records = (new AccountingModel())
            ->where('companyUuid', $company->getUuid());

        if ($filter->getOperation()) {
            $records = $records->where('operation', $filter->getOperation());
        }

        if ($filter->getAccountCash()) {
            $records = $records->where('accountCash', $filter->getAccountCash());
        }

        if ($filter->getKindActivity()) {
            $records = $records->where('kindActivity', $filter->getKindActivity());
        }

        if ($filter->getReceiptDate()) {
            $date = Carbon::parse($filter->getReceiptDate());

            $records = $records->whereBetween('receiptDate', [
                (clone $date)->setTime(0, 0)->format(DATE_ATOM),
                $date->setTime(23, 59, 59)->format(DATE_ATOM)
            ]);
        }

        $records = $records
            ->offset($filter->getOffset())
            ->limit($filter->getLimit())
            ->latest()
            ->get();

        return collect($records)->map(function ($record) {
            /**  @var \App\Models\Accounting $record */
            return $record->getPresenter()->getPublicData();
        })->toArray();
    }
}
