<?php declare(strict_types=1);

namespace App\Jobs\Cabinet\Company;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;

/**
 * Джоба для работы с отчетами ОПУ, ДДС и Баланса
 */
class Report implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Uuid компании
     *
     * @var string
     */
    private string $uuid;

    /**
     * Тип отчета
     *
     * @var string
     */
    private string $type;

    /**
     * Период отчета
     *
     * @var string
     */
    private string $period;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(string $uuid, string $type, string $period = '')
    {
        $this->uuid   = $uuid;
        $this->type   = $type;
        $this->period = $period;
    }

    /**
     * Execute the job.
     *
     * @return int|null
     */
    public function handle(): ?int
    {
        try {
            return Artisan::call('company:report', [
                '--uuid'   => $this->uuid,
                '--type'   => $this->type,
                '--period' => $this->period,
            ]);
        } catch (\Throwable $throwable) {
            Log::error('[ReportJob] Ошибка запуска обработчика отчета', [
                'exception' => $throwable
            ]);
        }

        return null;
    }
}
