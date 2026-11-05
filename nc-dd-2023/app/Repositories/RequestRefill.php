<?php declare(strict_types=1);

namespace App\Repositories;

use App\Models\RequestRefill\RequestRefill as RequestRefillModel;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Репозиторий для работы с пополнением через каспи
 */
class RequestRefill extends BaseRepository
{
    /**
     * Возвращает одну запись по её ID
     *
     * @param  int                                          $id
     * @return \App\Models\RequestRefill\RequestRefill|null
     */
    public function getOneById(int $id): ?RequestRefillModel
    {
        return RequestRefillModel::find($id);
    }

    /**
     * Обновляет запись по её ID
     *
     * @param  int                                          $id
     * @param  array                                        $attributes
     * @return \App\Models\RequestRefill\RequestRefill|null
     */
    public function updateById(int $id, array $attributes): ?RequestRefillModel
    {
        $requestRefill = $this->getOneById($id);

        if (!$requestRefill) {
            return null;
        }

        return DB::transaction(function () use ($requestRefill, $attributes) {
            $requestRefill->fill($attributes);

            if (!$requestRefill->save()) {
                Log::error('Не удалось обновить запись', [
                    'id'         => $requestRefill->getId(),
                    'attributes' => $attributes,
                ]);

                return null;
            }

            return $requestRefill->refresh();
        }, 2);
    }

    /**
     * Помечает заявку как оплачена/не оплачена
     *
     * @param  int                                          $id
     * @param  bool|null                                    $paid
     * @return \App\Models\RequestRefill\RequestRefill|null
     */
    public function setPaid(int $id, ?bool $paid = null): ?RequestRefillModel
    {
        $requestRefill = $this->getOneById($id);

        if (!$requestRefill) {
            return null;
        }

        return $this->updateById($id, [
            'isPaid' => !is_null($paid) ? $paid : !$requestRefill->isPaid()
        ]);
    }

    /**
     * Возвращает список записей с постраничной навигацией
     *
     * @param  array                                      $params
     * @return \Illuminate\Pagination\LengthAwarePaginator
     */
    public function getWithPaginate(array $params): LengthAwarePaginator
    {
        $records = new RequestRefillModel();

        if (isset($params['userId'])) {
            $records = $records->where('userId', (int) $params['userId']);
        }

        if (isset($params['phone'])) {
            $records = $records->where('phone', (int) $params['phone']);
        }

        return $records->latest()->paginate($params['limit'] ?? 15);
    }

    /**
     * Создает запрос на пополнение через Kaspi
     *
     * @param  array                                        $fields
     * @return \App\Models\RequestRefill\RequestRefill|null
     */
    public function create(array $fields): ?RequestRefillModel
    {
        return DB::transaction(function () use ($fields) {
            $refill = new RequestRefillModel();
            $refill->fill($fields);

            if (!$refill->save()) {
                Log::error('Не удалось сохранить запрос на пополнение', [
                    'fields' => $fields
                ]);

                return null;
            }

            return $refill->refresh();
        }, 2);
    }
}
