<?php declare(strict_types=1);

namespace App\Repositories\Invoice;

use App\Models\Invoice as InvoiceModel;
use App\Models\Invoice\Form\Payment as PaymentForm;
use App\Models\Tariff;
use App\Models\User;
use App\Modules\Payment\Wooppay\Objects\Request\CreateInvoice2Extended;
use App\Repositories\BaseRepository;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Illuminate\Support\Str;

/**
 * Репозиторий счетов
 */
class Invoice extends BaseRepository
{
    /**
     * Возвращает счёт по его идентификатору
     *
     * @param  int                      $id
     * @param  bool                     $useCache
     * @return \App\Models\Invoice|null
     */
    public function getOne(int $id, bool $useCache = true): ?InvoiceModel
    {
        $func = function () use ($id) {
            return (new InvoiceModel())->find($id);
        };

        if ($useCache) {
            return $this->getCache()->getCached(
                $this->getCacheKey($id),
                $func,
                mt_rand(3600, 14400),
                $this->getCacheTags($id)
            );
        }

        return $func();
    }

    /**
     * Создает счёт в нашей базе и возвращает созданный объект
     *
     * @param  \App\Models\Tariff               $tariff
     * @param  \App\Models\User                 $user
     * @param  \App\Models\Invoice\Form\Payment $form
     * @return \App\Models\Invoice
     */
    public function create(Tariff $tariff, User $user, PaymentForm $form): InvoiceModel
    {
        $lastInvoice = $this->getLastForUser($user->id);

        if ($lastInvoice) {
            return $lastInvoice;
        }

        return InvoiceModel::create(
            $this->getParametersForInvoice($user, $tariff, $form)
        );
    }

    /**
     * Сохраняет ID операции по счёту из платежной системы
     *
     * @param  \App\Models\Invoice $invoice
     * @param  int                 $operationId
     * @return bool
     */
    public function saveOperationId(InvoiceModel $invoice, int $operationId): bool
    {
        return $invoice->update(['operationId' => $operationId]);
    }

    /**
     * Сохраняет ID операции по счёту из платежной системы
     *
     * @param  \App\Models\Invoice $invoice
     * @return bool
     */
    public function setPaid(InvoiceModel $invoice): bool
    {
        $this->getCache()->deleteByTags(
            $this->getCacheTags($invoice->getId())
        );

        return $invoice->save();
    }

    /**
     * Возвращает последний найденный счёт для пользователя, созданный за последние 5 секунд
     *
     * @param  int                      $userId
     * @return \App\Models\Invoice|null
     */
    public function getLastForUser(int $userId): ?InvoiceModel
    {
        try {
            return InvoiceModel::where('user_id', $userId)
                ->where('created_at', '>=', Carbon::now()->subSeconds(5)->format('Y-m-d H:i:s'))
                ->latest()
                ->first();
        } catch (\Throwable $throwable) {
            Log::error('[Invoice] ошибка при получении счёта', [
                'exception' => $throwable
            ]);
        }

        return null;
    }

    /**
     * Возвращает параметры для сохранения в счёт
     *
     * @param  \App\Models\User                 $user
     * @param  \App\Models\Tariff               $tariff
     * @param  \App\Models\Invoice\Form\Payment $form
     * @return array
     */
    private function getParametersForInvoice(User $user, Tariff $tariff, PaymentForm $form): array
    {
        return [
            'user_id'     => $user->getAuthIdentifier(),
            'serviceType' => CreateInvoice2Extended::DEFAULT_SERVICE_TYPE,
            'service'     => Str::limit($tariff->getName(), 200),
            'tariffId'    => $tariff->getId(),
            'amount'      => $tariff->getPrice(),
            'email'       => $form->getEmail(),
            'phone'       => $form->getPhone(),
            'description' => $form->getDescription(),
            'deathDate'   => Carbon::now()->addWeek()->format('Y-m-d H:i:s'),
        ];
    }

    /**
     * Тэги кеша для счёта
     *
     * @param  int   $id
     * @param  array $keys
     * @return array
     */
    public function getCacheTags(int $id, array $keys = []): array
    {
        return array_merge([
            sprintf('App\Modules\Payment\Models\Invoice:%s', $id)
        ], $keys);
    }
}
