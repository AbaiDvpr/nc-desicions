<?php declare(strict_types=1);

namespace App\Http\Controllers\Api\Handbook;

use App\DI;
use App\Http\Requests\Api\Handbook\StoreRequest;
use App\Http\Requests\Api\Handbook\GetListRequest;
use App\Models\Handbook\Forms\Handbook as HandbookForm;
use Illuminate\Http\JsonResponse;

/**
 * Контроллер для работы со справочником
 */
class HandbookController
{
    /**
     * Возвращает список
     *
     * @param  \App\Http\Requests\Api\Handbook\GetListRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getAll(GetListRequest $request): JsonResponse
    {
        $handbooks = DI::getRepository()->getHandbook()->getCommon()->getByType(
            (string) $request->get('companyUuid'),
            (int) $request->get('type')
        )->map(function ($handbook) {
            /** @var \App\Models\Handbook $handbook */
            return [
                'value' => $handbook->getId(),
                'title' => $handbook->getTitle(),
            ];
        })->toArray();

        return response()->json($handbooks);
    }

    /**
     * Создает справочник
     *
     * @param  \App\Http\Requests\Api\Handbook\StoreRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function create(StoreRequest $request): JsonResponse
    {
        $handbook = DI::getRepository()->getHandbook()->getCommon()->save(
            null,
            HandbookForm::createFromRequest($request)
        );

        if (!$handbook) {
            return response()->json([
                'message' => 'Ошибка при добавлении записи, обновите страницу или попробуйте позднее',
            ], 500);
        }

        return response()->json([
            'data' => collect($handbook->getPresenter()->getPublicData())->only(['value', 'title'])->toArray()
        ]);
    }
}
