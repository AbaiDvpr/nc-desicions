<?php declare(strict_types=1);

namespace App\Http\Controllers\Api\Handbook;

use App\DI;
use App\Http\Controllers\Api\BaseController;
use App\Http\Requests\Api\Handbook\Direction\CreateRequest;
use App\Http\Requests\Api\Handbook\Direction\GetByCompanyRequest;
use App\Models\Handbook\Forms\Direction as DirectionForm;
use Illuminate\Http\JsonResponse;

/**
 * Контроллер для работы с направлениями компании
 */
class DirectionController extends BaseController
{
    /**
     * Возвращает список направлений компании
     *
     * @param  \App\Http\Requests\Api\Handbook\Direction\GetByCompanyRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getAll(GetByCompanyRequest $request): JsonResponse
    {
        $directions = DI::getRepository()->getHandbook()->getDirection()->getByCompany($request->get('companyUuid'))
            ->map(function ($direction) {
            /** @var \App\Models\Handbook\Direction $direction */
            return [
                'value' => $direction->getId(),
                'title' => $direction->getTitle(),
            ];
        })->toArray();

        return response()->json($directions);
    }

    /**
     * Создает направление из запроса
     *
     * @param  \App\Http\Requests\Api\Handbook\Direction\CreateRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function create(CreateRequest $request): JsonResponse
    {
        $direction = DI::getRepository()->getHandbook()->getDirection()->save(
            null,
            DirectionForm::createFromRequest($request)
        );

        if (!$direction) {
            return response()->json([
                'message' => 'Не удалось добавить направление в справочник, обновите страницу или попробуйте позднее',
            ], 500);
        }

        return response()->json([
            'data' => collect($direction->getPresenter()->getPublicData())->only(['value', 'title'])->toArray()
        ]);
    }
}
