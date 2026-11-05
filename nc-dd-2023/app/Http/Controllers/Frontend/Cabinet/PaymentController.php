<?php declare(strict_types=1);

namespace App\Http\Controllers\Frontend\Cabinet;

use App\DI;
use App\Http\Controllers\Frontend\BaseController;
use App\Http\Requests\Frontend\Cabinet\Payment\PayRequest;
use App\Http\Requests\Frontend\Cabinet\Payment\RequestRefill;
use App\Http\Requests\Frontend\Tariff\GetRequest;
use App\Models\BalanceHistory\Filters\PagerParams;
use App\Models\Invoice\Form\Payment as PaymentForm;
use App\Models\RequestRefill\Form\Refill as RefillForm;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Контроллер для работы с платежами в личном кабинете
 */
class PaymentController extends BaseController
{
    /**
     * Экран с историей по счёту
     *
     * @param  \Illuminate\Http\Request      $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $params = PagerParams::createFromRequest($request);
        $params->setUserId($this->client->getAuthIdentifier());

        return response()->json(DI::getRepository()->getModelsBalanceHistory()->getFromParams($params->toArray()));
    }

    /**
     * Экран подтверждения выбора тарифного плана
     *
     * @param  int                             $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function getTariffAction(int $id)
    {
        $tariff = DI::getRepository()->getModelsTariff()->getOne($id);

        if (is_null($tariff)) {
            return response()->json([
                'message' => 'Тариф не найден'
            ], 404);
        }

        return response()->json($tariff->getPresenter()->getPublicData());
    }

    /**
     * Возвращает список активных тарифов
     *
     * @param  \App\Http\Requests\Frontend\Tariff\GetRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getTariffsAction(GetRequest $request)
    {
        return response()->json(
            DI::getRepository()->getModelsTariff()->getLastThreeActive(
                (int) $request->input('type')
            )
        );
    }

    /**
     * Отправляет запрос на пополнение через Kaspi
     *
     * @param  \App\Http\Requests\Frontend\Cabinet\Payment\RequestRefill $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function sendRequestRefillAction(RequestRefill $request): JsonResponse
    {
        $form = RefillForm::createFromRequest($request);
        $form->setUserId($this->client->getAuthIdentifier());

        if ($form->save()) {
            return response()->json([
                'success' =>
                    'Ваша заявка успешно отправлена, в ближайшее время Вам будет выставлен счёт на указанный номер',
            ]);
        }

        return response()->json([
            'message' => 'Не удалось отправить запрос. Попробуйте позднее',
        ], 500);
    }

    /**
     * Создание счёта на оплату и отправка на экран платежной системы
     *
     * @param  int                                                    $id
     * @param  \App\Http\Requests\Frontend\Cabinet\Payment\PayRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function pay(int $id, PayRequest $request): JsonResponse
    {
        $tariff = DI::getRepository()->getModelsTariff()->getOne($id);

        if (is_null($tariff)) {
            return response()->json([
                'message' => 'Ошибка получения тарифного плана'
            ], 404);
        }

        try {
            $invoice = DI::getRepository()->getInvoice()->create(
                $tariff,
                $this->client,
                PaymentForm::createFromRequest($request)
            );

            $wooppayInvoice = DI::getRepository()->getPayment()->createInvoice($invoice);

            return response()->json([
                'url' => $wooppayInvoice['url']
            ]);
        } catch (\Throwable $throwable) {
            Log::error('Ошибка при проведении оплаты', [
                'exception' => $throwable,
            ]);
        }

        return response()->json([
            'message' => 'Произошла ошибка во время проведения оплаты. Мы уже знаем об ошибке, пожалуйста попробуйте позднее',
        ], 500);
    }
}
