<?php declare(strict_types=1);

namespace App\Jobs\Backend\Indicators;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;

/**
 * Джоба для работы с показателями
 */
class Indicators implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /*
     * Execute the job.
     */
    public function handle(): int
    {
        try {
            return Artisan::call('get:indicators');
        } catch (\Throwable $throwable) {
            Log::error('[Indicators] Ошибка при выполнении консольной задачи', [
                'exception' => $throwable
            ]);

            return 0;
        }
    }
}
