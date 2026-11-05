<?php declare(strict_types=1);

namespace App\Repositories\Models;

use App\Models\BalanceHistory as BalanceHistoryModel;
use App\Models\Invoice as InvoiceModel;
use App\Repositories\BaseRepository;
use App\Models\User as UserModel;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

/**
 * Репозиторий для работы с историей пополнения и списания баланса
 */
class BalanceHistory extends BaseRepository
{
    /**
     * Получить список истории пополнения
     *
     * @param  array $params
     * @return array
     */
    public function getFromParams(array $params): array
    {
        $histories = new BalanceHistoryModel();

        if (isset($params['userId'])) {
            $histories = $histories->where('user_id', (int)$params['userId']);
        }

        if (isset($params['tariffId'])) {
            $histories = $histories->where('tariffId', (int)$params['tariffId']);
        }

        $histories = $histories->offset((int)$params['offset'])->limit((int)$params['limit'])->latest('id')->get();

        return collect($histories)->map(function ($history) {
            /** @var \App\Models\BalanceHistory $history */
            return $history->getPresenter()->getPublicData();
        })->toArray();
    }

    /**
     * Сохраняет запись в историю изменения баланса пользователя
     *
     * @param  \App\Models\User                          $user
     * @param  \App\Models\Invoice                       $invoice
     * @param  string|null                               $comment
     * @return \Illuminate\Database\Eloquent\Model|false
     */
    public function save(UserModel $user, InvoiceModel $invoice, ?string $comment): Model|bool
    {
        return DB::transaction(function () use ($user, $invoice, $comment) {
            return $user->balanceHistories()->save(new BalanceHistoryModel([
                'operation'  => BalanceHistoryModel::OPERATION_CUT,
                'paid'       => $invoice->getAmount(),
                'residue'    => $user->getBalance() - $invoice->getAmount(),
                'paid_at'    => Carbon::now(),
                'comment'    => $comment,
                'created_id' => $user->getAuthIdentifier(),
            ]));
        });
    }
}
