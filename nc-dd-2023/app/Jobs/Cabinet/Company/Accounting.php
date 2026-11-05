<?php declare(strict_types=1);
namespace App\Jobs\Cabinet\Company;

use App\DI;
use App\Models\Accounting as AccountingModel;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Джоба для обработки ручного ввода и распределения по типа ОПУ, ДДС и Баланс
 */
class Accounting implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Модель с данными ручного ввода
     *
     * @var \App\Models\Accounting
     */
    private AccountingModel $accounting;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(AccountingModel $accounting)
    {
        $this->accounting = $accounting;
    }

    /**
     * Execute the job.
     *
     * @return int|null
     */
    public function handle(): ?int
    {
        try {
            DI::getComponent()->getAccounting()->refreshFact($this->accounting);
        } catch (\Throwable $throwable) {
            Log::error('[AccountingJob] Ошибка при сохранении данных в факт', [
                'exception' => $throwable
            ]);
        }

        return null;
    }
}
