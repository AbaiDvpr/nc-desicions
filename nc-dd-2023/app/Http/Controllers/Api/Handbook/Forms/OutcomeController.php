<?php declare(strict_types=1);
namespace App\Http\Controllers\Api\Handbook\Forms;

use App\Forms\Company\Outcome;
use App\Http\Controllers\Api\Handbook\BaseController;
use Illuminate\Http\JsonResponse;

/**
 * Класс для справочников формы заполнения доходов
 */
class OutcomeController extends BaseController
{
    /**
     * Возвращает список справочников для формы "Доходы"
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getTypesAction(): JsonResponse
    {
        return response()->json((new Outcome())->getTypes());
    }
}
