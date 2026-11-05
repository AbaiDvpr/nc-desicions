<?php declare(strict_types=1);

namespace App\Repositories\Company;

use App\Models\Company\Opu as OpuModel;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Репозиторий ОПУ
 */
class Opu
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
                return OpuModel::insert($data);
            }, 2);
        } catch (\Exception $exception) {
            Log::error('[Opu] Ошибка сохранение импорт данных в базу', [
                'exception' => $exception
            ]);
        }

        return false;
    }

    public function getFactByPeriod(string $uuid, Carbon $period): ?OpuModel
    {
        return OpuModel::where('companyUuid', $uuid)
            ->where('fillType', config('company.fillType.input'))
            ->whereYear('period', $period->format('Y'))
            ->whereMonth('period', $period->format('m'))
            ->latest()
            ->first();
    }

    /**
     * Возвращает ОПУ по uuid компании и периоду
     *
     * @param  string                                        $uuid
     * @param  null|string                                   $period
     * @return \Illuminate\Database\Eloquent\Collection|null
     */
    public function getForCompany(string $uuid, ?string $period = null): ?Collection
    {
        try {
            $opu = OpuModel::where('companyUuid', $uuid);

            if ($period) {
                $opu = $opu->where('period', $period);
            }

            return $opu->latest()->get();
        } catch (\Exception $exception) {
            Log::error('Ошибка при получении ОПУ', [
                'exception' => $exception
            ]);
        }

        return null;
    }

    /**
     * Возвращает ОПУ для формирования факта
     *
     * @param  string                                        $uuid
     * @param  null|string                                   $period
     * @return \Illuminate\Database\Eloquent\Collection|null
     */
    public function getForReport(string $uuid, ?string $period = null): ?Collection
    {
        $dbRawValue = collect(OpuModel::CODE_FIELDS)
            ->map(function (string $code) {
                return 'SUM(' . $code . ') as ' . $code;
            })
            ->join(',');

        try {
            $opu = OpuModel::select('*', DB::raw($dbRawValue))->where('companyUuid', $uuid);

            if ($period) {
                $period = Carbon::parse($period);

                $opu = $opu
                    ->whereYear('period', $period->format('Y'))
                    ->whereMonth('period', $period->format('m'));
            }

            return $opu->latest()->get();
        } catch (\Exception $exception) {
            Log::error('Ошибка при получении ОПУ', [
                'exception' => $exception
            ]);
        }

        return null;
    }
}
