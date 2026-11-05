<?php declare(strict_types=1);

namespace App\Http\Controllers\Frontend\Payment;

use App\DI;
use App\Http\Controllers\Frontend\BaseController;
use Firebase\JWT\BeforeValidException;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Log;

/**
 * Контроллер для отображения факта оплаты
 */
class ConfirmController extends BaseController
{
    /**
     * Экран подтверждения оплаты после возвращения с платежной системы
     *
     * @param  string                          $token
     * @return \Illuminate\Contracts\View\View
     */
    public function __invoke(string $token): View
    {
        try {
            $repo = DI::getRepository();
            $data = $repo->getJwtToken()->decode($token);

            if (!$data->userId || !$data->invoiceId) {
                throw new BeforeValidException('Invalid token data');
            }

            return view('frontend.payment.confirm', [
                'user'    => $repo->getUser()->findById($data->userId)?->getUserData()->getPublicData(),
                'invoice' => $repo->getInvoice()->getOne($data->invoiceId),
            ]);
        } catch (\Throwable $throwable) {
            Log::error('[Payment] ошибка во время подтверждения оплаты', [
                'exception' => $throwable,
            ]);
        }

        return view('frontend.payment.confirm', [
            'isError' => true
        ]);
    }
}
