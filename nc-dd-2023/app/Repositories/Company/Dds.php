<?php declare(strict_types=1);

namespace App\Repositories\Company;

use App\Models\Company\Dds as DdsModel;
use App\Models\Company\Opu as OpuModel;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Репозиторий ДДС
 */
class Dds
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
                return DdsModel::insert($data);
            }, 2);
        } catch (\Exception $exception) {
            Log::error('[Dds] Ошибка сохранение импорт данных в базу', [
                'exception' => $exception
            ]);
        }

        return false;
    }

    public function getFactByPeriod(string $uuid, Carbon $period): ?DdsModel
    {
        return DdsModel::where('companyUuid', $uuid)
            ->where('fillType', config('company.fillType.input'))
            ->whereYear('period', $period->format('Y'))
            ->whereMonth('period', $period->format('m'))
            ->latest()
            ->first();
    }

    /**
     * Возвращает ДДС по uuid компании и периоду
     *
     * @param  string                                        $uuid
     * @param  null|string                                   $period
     * @return \Illuminate\Database\Eloquent\Collection|null
     */
    public function getForCompany(string $uuid, ?string $period = null): ?Collection
    {
        try {
            $dds = DdsModel::where('companyUuid', $uuid);

            if ($period) {
                $dds = $dds->where('period', $period);
            }

            return $dds->latest()->get();
        } catch (\Exception $exception) {
            Log::error('Ошибка при получении ДДС', [
                'exception' => $exception
            ]);
        }

        return null;
    }

    /**
     * Возвращает ДДС для формирования факта
     *
     * @param  string                                        $uuid
     * @param  null|string                                   $period
     * @return \Illuminate\Database\Eloquent\Collection|null
     */
    public function getForReport(string $uuid, ?string $period = null): ?Collection
    {
        $dbRawValue = collect(DdsModel::CODE_FIELDS)
            ->map(function (string $code) {
                return 'SUM(' . $code . ') as ' . $code;
            })
            ->join(',');

        try {
            $dds = DdsModel::select('*', DB::raw($dbRawValue))->where('companyUuid', $uuid);;

            if ($period) {
                $period = Carbon::parse($period);

                $dds = $dds
                    ->whereYear('period', $period->format('Y'))
                    ->whereMonth('period', $period->format('m'));
            }

            return $dds->latest()->get();
        } catch (\Exception $exception) {
            Log::error('Ошибка при получении ДДС', [
                'exception' => $exception
            ]);
        }

        return null;
    }
}
