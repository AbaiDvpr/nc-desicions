<?php declare(strict_types=1);
namespace App\Http\Controllers\Api\Cabinet\Companies;

use App\DI;
use App\Http\Controllers\Api\BaseController;
use App\Http\Requests\Api\Cabinet\Companies\Accounting\DeleteRequest;
use App\Http\Requests\Api\Cabinet\Companies\Accounting\GetOneRequest;
use App\Http\Requests\Api\Cabinet\Companies\Accounting\SaveRequest;
use App\Models\Accounting\Filters\PagerParams;
use App\Models\Accounting\Forms\Base;
use App\Repositories\Company\Exceptions\NotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Контроллер для работы с ежедневным учётом
 */
class AccountingController extends BaseController
{
    /**
     * Возвращает список статей доходов/расходов для компании
     *
     * @param \App\Http\Requests\Api\Cabinet\Companies\Accounting\GetOneRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getOne(GetOneRequest $request): JsonResponse
    {
        $accounting = DI::getRepository()->getAccounting()->getOne((string) $request->post('uuid'));
        if (!$accounting) {
            return response()->json([
                'error' => 'Запись не найдена'
            ], 404);
        }

        return response()->json($accounting);
    }

    /**
     * Возвращает список статей доходов/расходов для компании
     *
     * @param  string                                                 $uuid
     * @param  \Illuminate\Http\Request                               $request
     * @return \Illuminate\Http\JsonResponse
     * @throws \App\Repositories\Company\Exceptions\NotFoundException
     */
    public function getList(string $uuid, Request $request): JsonResponse
    {
        $company = DI::getRepository()->getCompany()->getByUuid($uuid);

        if (!$company || $company->getUserId() !== $this->client->getAuthIdentifier()) {
            throw new NotFoundException('Компания не найдена');
        }

        $filter     = PagerParams::createFromRequest($request);
        $accounting = DI::getRepository()->getAccounting()->getFromParams($company, $filter);

        return response()->json([
            'data' => $accounting
        ]);
    }

    /**
     * Создание или обновление статьи доходов/расходов
     *
     * @param  \App\Http\Requests\Api\Cabinet\Companies\Accounting\SaveRequest $request
     * @return \Illuminate\Http\JsonResponse
     * @throws \Exception
     */
    public function saveAction(SaveRequest $request): JsonResponse
    {
        $form       = (new Base())->createFromRequest($request);
        $accounting = DI::getRepository()->getAccounting()->save($form, $form->getUuid());

        if (!$accounting) {
            return response()->json([
                'error' => 'Ошибка при добавлении записи. Попробуйте позднее'
            ], 500);
        }

        return response()->json([
            'id' => $accounting->getId()
        ]);
    }

    /**
     * Метод для удаления статьи доходов/расходов
     *
     * @param  \App\Http\Requests\Api\Cabinet\Companies\Accounting\DeleteRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function deleteAction(DeleteRequest $request): JsonResponse
    {
        $accounting = DI::getRepository()->getAccounting()->getOne($request->get('uuid'));
        if (is_null($accounting)) {
            return response()->json([
                'error' => 'Запись не найдена',
            ], 404);
        }

        return response()->json([
            'status' => DI::getRepository()->getAccounting()->delete($accounting->getId())
        ]);
    }
}
