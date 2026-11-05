<?php declare(strict_types=1);

namespace App\Repositories\Company;

use App\Models\Company\Balance as BalanceModel;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Репозиторий Баланса
 */
class Balance
{
    /**
     * Сохранение данных в базу
     *
     * @param  array $data
     * @return bool
     */
    public function storeFromArray(array $data): bool
    {
        try {
            return DB::transaction(function () use ($data) {
                return BalanceModel::insert($data);
            }, 2);
        } catch (\Exception $exception) {
            Log::error('[Balance] Ошибка сохранение импорт данных в базу', [
                'exception' => $exception
            ]);
        }

        return false;
    }

    /**
     * Возвращает фактические показатели баланса за определенный период
     *
     * @param  string                           $uuid
     * @param  \Illuminate\Support\Carbon       $period
     * @return \App\Models\Company\Balance|null
     */
    public function getFactByPeriod(string $uuid, Carbon $period): ?BalanceModel
    {
        return BalanceModel::where('companyUuid', $uuid)
            ->where('fillType', config('company.fillType.input'))
            ->whereYear('period', $period->format('Y'))
            ->whereMonth('period', $period->format('m'))
            ->latest()
            ->first();
    }

    /**
     * Возвращает Баланс по uuid компании и периоду
     *
     * @param  string                                        $uuid
     * @param  null|string                                   $period
     * @return \Illuminate\Database\Eloquent\Collection|null
     */
    public function getForCompany(string $uuid, ?string $period = null): ?Collection
    {
        try {
            $balance = BalanceModel::where('companyUuid', $uuid);

            if ($period) {
                $balance = $balance->where('period', $period);
            }

            return $balance->latest()->get();
        } catch (\Exception $exception) {
            Log::error('Ошибка при получении Баланса', [
                'exception' => $exception
            ]);
        }

        return null;
    }

    /**
     * Возвращает Баланс по uuid компании и периоду
     *
     * @param  string                                        $uuid
     * @param  null|string                                   $period
     * @return \Illuminate\Database\Eloquent\Collection|null
     */
    public function getForReport(string $uuid, ?string $period = null): ?Collection
    {
        $dbRawValue = collect(BalanceModel::CODE_FIELDS)
            ->map(function (string $code) {
                return 'SUM(' . $code . ') as ' . $code;
            })
            ->join(',');

        try {
            $balance = BalanceModel::select('*', DB::raw($dbRawValue))->where('companyUuid', $uuid);;

            if ($period) {
                $period = Carbon::parse($period);

                $balance = $balance
                    ->whereYear('period', $period->format('Y'))
                    ->whereMonth('period', $period->format('m'));
            }

            return $balance->latest()->get();
        } catch (\Exception $exception) {
            Log::error('Ошибка при получении Баланса', [
                'exception' => $exception
            ]);
        }

        return null;
    }
}
