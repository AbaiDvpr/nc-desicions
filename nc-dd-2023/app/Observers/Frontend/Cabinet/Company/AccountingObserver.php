<?php declare(strict_types=1);

namespace App\Observers\Frontend\Cabinet\Company;

use App\DI;
use App\Models\Accounting;
use Illuminate\Support\Facades\Log;

/**
 * Слушатель для изменений в модели Accounting
 */
class AccountingObserver
{
    /**
     * Событие выполняемое при создании ежедневного учета
     *
     * @param Accounting $accounting
     */
    public function created(Accounting $accounting)
    {
        //\App\Jobs\Cabinet\Company\Accounting::dispatch($accounting)->onQueue('accounting');

        try {
            DI::getComponent()->getAccounting()->refreshFact($accounting);
        } catch (\Throwable $throwable) {
            Log::error('[Accounting] Обновление фактических показателей не удалось', [
                'exception' => $throwable
            ]);
        }
    }

    /**
     * Handle the user "updated" event.
     *
     * @param Accounting $accounting
     */
    public function updated(Accounting $accounting)
    {
        //\App\Jobs\Cabinet\Company\Accounting::dispatch($accounting)->onQueue('accounting');
    }

    /**
     * Handle the user "deleted" event.
     *
     * @param Accounting $accounting
     */
    public function deleted(Accounting $accounting)
    {
        //
    }

    /**
     * Handle the user "restored" event.
     *
     * @param Accounting $accounting
     */
    public function restored(Accounting $accounting)
    {
        //
    }

    /**
     * Handle the user "force deleted" event.
     *
     * @param Accounting $accounting
     */
    public function forceDeleted(Accounting $accounting)
    {
        //
    }
}
