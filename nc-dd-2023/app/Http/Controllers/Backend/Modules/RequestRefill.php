<?php declare(strict_types=1);

namespace App\Http\Controllers\Backend\Modules;

use App\DI;
use App\Http\Controllers\Backend\BaseController;
use App\Models\RequestRefill\Filters\PagerParams;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Contracts\View\View;

class RequestRefill extends BaseController
{
    /**
     * Выводит список заявок на пополнение
     *
     * @param  \Illuminate\Http\Request        $request
     * @return \Illuminate\Contracts\View\View
     */
    public function index(Request $request): View
    {
        $params = PagerParams::createFromRequest($request);
        $params->setLimit(30);

        return view('backend.modules.request-refill.index', [
            'records' => DI::getRepository()->getRequestRefill()->getWithPaginate($params->toArray())
        ]);
    }

    /**
     * Обновляет статус пополнения заявки
     *
     * @param  int                           $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(int $id): JsonResponse
    {
        $requestRefill = DI::getRepository()->getRequestRefill()->getOneById($id);

        if (is_null($requestRefill)) {
            return response()->json(['message' => 'Запись не найдена'], 404);
        }

        $refill = DI::getRepository()->getRequestRefill()->setPaid($id);

        if (!$refill) {
            return response()->json(['message' => 'Не удалось обновить запись'], 500);
        }

        return response()->json([
            'isPaid'  => $refill->isPaid(),
            'message' => 'Статус оплаты успешно обновлен',
        ]);
    }
}
