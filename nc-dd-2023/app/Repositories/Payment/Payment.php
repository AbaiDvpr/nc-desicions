<?php declare(strict_types=1);

namespace App\Repositories\Payment;

use App\Models\User;
use App\Repositories\BaseRepository;
use App\Models\Invoice;
use App\DI;
use App\Repositories\Payment\Exceptions\AccessDeniedException;
use App\Repositories\Payment\Exceptions\NotFoundException;
use App\Services\Payment\PaymentService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

/**
 * Репозиторий проведения оплаты
 */
class Payment extends BaseRepository
{
    /**
     * Создает счёт для оплаты в платежной системе и возвращает ссылку на оплату
     *
     * @throws \App\Modules\Payment\Wooppay\Objects\Exceptions\LowBalance
     * @throws \App\Modules\Payment\Wooppay\Objects\Exceptions\UnsuccessfulResponse
     * @throws \App\Modules\Payment\Wooppay\Objects\Exceptions\Exception
     * @throws \App\Modules\Payment\Wooppay\Objects\Exceptions\BadCredentials
     */
    public function createInvoice(Invoice $invoice): array
    {
        $backUrl = route('frontend.payment.confirm', [
            'token' => DI::getRepository()->getJwtToken()->encode([
                'userId'    => $invoice->getUserId(),
                'invoiceId' => $invoice->getId()
            ]),
        ]);

        $wInvoice = DI::getWooppayClient()->createInvoice2Extended(
            (string) $invoice->getId(),
            $backUrl,
            $this->getRequestUrl($invoice),
            $invoice->getDescription() ?? '',
            $invoice->getAmount(),
            $invoice->getDeathDate()->format('Y-m-d H:i:s'),
            $invoice->getServiceType(),
            $invoice->getDescription() ?? '',
            $invoice->getEmail(),
            $invoice->getPhone(),
        );

        DI::getRepository()->getInvoice()->saveOperationId($invoice, $wInvoice->getOperationId());

        return [
            'status' => 'ok',
            'url'    => $wInvoice->getOperationUrl(),
        ];
    }

    /**
     * Генерирует ссылку для обратного запроса после успешной оплаты
     *
     * @param  \App\Models\Invoice $invoice
     * @return string
     */
    protected function getRequestUrl(Invoice $invoice): string
    {
        return route('api.wooppay.confirm', [
            'token' => DI::getRepository()->getJwtToken()->encode([
                'id'      => $invoice->getId(),
                'user_id' => $invoice->getUserId(),
            ]),
        ]);
    }

    /**
     * Возвращает счёт на оплату по ID
     *
     * @param  int                                                    $id
     * @return \App\Models\Invoice|null
     * @throws \App\Repositories\Payment\Exceptions\NotFoundException
     */
    public function getInvoiceById(int $id): ?Invoice
    {
        $invoice = DI::getRepository()->getInvoice()->getOne($id);

        if ($invoice->isPaid()) {
            Log::info('[Invoice] попытка оплатить уже оплаченный счет');

            throw new NotFoundException('Счёт не найден');
        }

        return $invoice;
    }

    /**
     * Операция пополнения счёта
     *
     * @param  \App\Models\User                                           $user
     * @param  int                                                        $invoiceId
     * @return bool
     * @throws \Telegram\Bot\Exceptions\TelegramSDKException
     * @throws \App\Repositories\Payment\Exceptions\NotFoundException
     * @throws \App\Repositories\Payment\Exceptions\AccessDeniedException
     */
    public function topUpAccount(User $user, int $invoiceId): bool
    {
        $invoice = $this->getInvoiceById($invoiceId);

        if (!$invoice || $user->id !== $invoice->getUserId()) {
            throw new AccessDeniedException('Указанный счет не найден');
        }

        $comment = sprintf('Пополнение счёта %s', $user->getCabinetId());

        $paymentService = new PaymentService($user);

        $status = $paymentService->changeBalance($invoice->getAmount(), $comment);

        if ($status['code'] === PaymentService::SUCCESS_CODE) {
            $invoice->setPaidAt(Carbon::now());

            DI::getRepository()->getInvoice()->setPaid($invoice);
        }

        $this->applyTariff($user, $invoice);

        return $status['code'] === PaymentService::SUCCESS_CODE;
    }

    /**
     * Применяет выбранный тарифный план после проведения оплаты
     *
     * @param  \App\Models\User                             $user
     * @param  \App\Models\Invoice                          $invoice
     * @return \App\Models\User|null
     * @throws \Telegram\Bot\Exceptions\TelegramSDKException
     */
    public function applyTariff(User $user, Invoice $invoice): ?User
    {
        $tariff = DI::getRepository()->getModelsTariff()->getOne($invoice->getTariffId());

        if (is_null($tariff)) {
            return null;
        }

        $user = $user->applyTariff($tariff);

        if (is_null($user)) {
            Log::error('Ошибка во время применения тарифного плана', $tariff->getLogData());

            return null;
        }

        $paymentService = new PaymentService($user);

        // Сохраняем в историю операцию по списанию в счет тарифа
        $paymentService->changeBalance(
            $tariff->getPrice(),
            sprintf('Списание в счёт оплаты тарифного плана %s', $invoice->getService()),
            false
        );

        // Чистим кеш пользователя, на всякий случай
        DI::getRepository()->getUser()->clearCache($user);

        return $user;
    }

    /**
     * Возвращает данные из токена ответа
     *
     * @param  string          $token
     * @return \stdClass
     * @throws \ErrorException
     */
    public function getDataFromToken(string $token): \stdClass
    {
        $data = DI::getRepository()->getJwtToken()->decode($token);

        if (!property_exists($data, 'id') || !property_exists($data, 'user_id')) {
            throw new \ErrorException('Invalid token decode');
        }

        return $data;
    }
}
