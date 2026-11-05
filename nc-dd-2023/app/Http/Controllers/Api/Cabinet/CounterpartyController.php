<?php declare(strict_types = 1);

namespace App\Http\Controllers\Api\Cabinet;

use App\DI;
use App\Http\Controllers\Api\BaseController;
use App\Http\Requests\Api\Cabinet\Counterparty\SaveRequest;
use App\Models\Counterparty\Filter\PagerParams;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Models\Counterparty\Forms\Counterparty as CounterpartyForm;

/**
 * Контроллер для работы с контрагентами
 */
class CounterpartyController extends BaseController
{
    /**
     * Получаем список контрагентов пользователя с пагинацией
     *
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $params = PagerParams::createFromRequest($request);
        $params->setUserId($this->client->getAuthIdentifier());

        $counterparties = DI::getRepository()->getCounterparty()->getFromParams($params);

        $counterparties = collect($counterparties)->map(function ($counterparty) {
            /** @var \App\Models\Counterparty $counterparty */
            return $counterparty->getPresenter()->getPublicData();
        })->toArray();

        return response()->json($counterparties);
    }

    /**
     * Создаем контрагента
     *
     * @param  \App\Http\Requests\Api\Cabinet\Counterparty\SaveRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function save(SaveRequest $request): JsonResponse
    {
        $form         = CounterpartyForm::createFromRequest($request);
        $counterparty = DI::getRepository()->getCounterparty()->save(
            $this->client,
            $form->getUuid(),
            $form,
        );

        if (!$counterparty) {
            return response()->json([
                'error' => 'Ошибка при добавлении записи. Попробуйте позднее',
            ], 500);
        }

        return response()->json([
            'id' => $counterparty->getId(),
        ]);
    }

    /**
     * Удаляем контрагента по id
     *
     * @param  string $uuid
     * @return \Illuminate\Http\JsonResponse
     */
    public function delete(string $uuid): JsonResponse
    {
        $counterparty = DI::getRepository()->getCounterparty()->getOne($uuid);

        if (is_null($counterparty)) {
            return response()->json([
                'message' => 'Контрагент не найден',
            ], 404);
        }

        return response()->json([
            'status' => DI::getRepository()->getCounterparty()->delete($counterparty),
        ]);
    }
}