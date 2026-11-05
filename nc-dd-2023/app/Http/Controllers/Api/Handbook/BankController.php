<?php declare(strict_types=1);

namespace App\Http\Controllers\Api\Handbook;

use App\DI;
use App\Http\Controllers\Api\BaseController;
use Illuminate\Http\JsonResponse;

/**
 * Класс для проверки доступности сервисов
 */
class BankController extends BaseController
{
    /**
     * Возвращает список параметров формы по умолчанию
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getAll(): JsonResponse
    {
        $banks = DI::getRepository()->getHandbook()->getBank()->getAll();

        $asd = collect($banks)->map(function ($bank) {
            return [
                'value' => $bank['_id'],
                'title' => $bank['name'],
            ];
        })->toArray();

        return response()->json($asd);
    }

    /**
     * Получение данных банка
     *
     * @param  string                       $uuid
     * @return \Illuminate\Http\JsonResponse
     */
    public function getOne(string $uuid): JsonResponse
    {
        $bank = DI::getRepository()->getHandbook()->getBank()->getOne($uuid);

        if (!$bank) {
           return response()->json([
               'message' => 'Банк не найден',
           ], 500);
        }

        return response()->json($bank->getPresenter()->getPublicData());
    }
}
