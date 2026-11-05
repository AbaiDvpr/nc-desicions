<?php declare(strict_types=1);
namespace App\Http\Controllers\Api\Handbook\Forms;

use App\Forms\Company\Income;
use App\Http\Controllers\Api\Handbook\BaseController;
use Illuminate\Http\JsonResponse;

/**
 * Класс для справочников формы заполнения доходов
 */
class IncomeController extends BaseController
{
    /**
     * Возвращает список справочников для формы "Доходы"
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getTypesAction(): JsonResponse
    {
        return response()->json((new Income())->getTypes());
    }
}
