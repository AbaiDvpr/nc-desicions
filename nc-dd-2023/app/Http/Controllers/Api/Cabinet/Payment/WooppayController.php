<?php declare(strict_types=1);

namespace App\Http\Controllers\Api\Cabinet\Payment;

use App\DI;
use App\Repositories\User\Exceptions\NotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Throwable;
use Whoops\Exception\ErrorException;

/**
 * Контроллер подтверждения оплаты от платежной системы
 */
class WooppayController
{
    /**
     * Подтверждает оплату и применяет выбранный тариф к профилю пользователя
     *
     * @param  string                        $token
     * @return \Illuminate\Http\JsonResponse
     */
    public function confirmAction(string $token): JsonResponse
    {
        $isPaid = 0;

        // TODO: напилить метрики для графаны
        try {
            $data = DI::getRepository()->getPayment()->getDataFromToken($token);
            $user = DI::getRepository()->getUser()->findById($data->user_id);

            if (!$user) {
                throw new NotFoundException('Пользователь не найден');
            }

            if (!DI::getRepository()->getPayment()->topUpAccount($user, $data->id)) {
                throw new ErrorException('Произошла ошибка при подтверждении оплаты');
            }

            DI::getAdminTelegram()->sendMessage([
                'chat_id'    => config('telegram.bots.admin.chat_id'),
                'parse_mode' => 'HTML',
                'text'       => view('notifications.telegram.payments.pay-notify-success', [
                    'id'   => $user->getCabinetId(),
                    'name' => $user->getName(),
                ])->render(),
            ]);

            $isPaid = 1;
        } catch (NotFoundException $exception) {
            Log::error('[Invoice] пользователь не найден', [
                'exception' => $exception,
            ]);
        } catch (Throwable $throwable) {
            Log::error('[Invoice] ошибка во время подтверждения оплаты', [
                'exception' => $throwable,
            ]);
        }

        return response()->json([
            'data' => $isPaid
        ]);
    }
}
