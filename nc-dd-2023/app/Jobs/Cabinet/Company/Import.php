<?php declare(strict_types=1);

namespace App\Jobs\Cabinet\Company;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Artisan;

/**
 * Джоба для импорта данных файла в базу
 */
class Import implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * ID файла для импорта
     *
     * @var int
     */
    private int $id;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(int $id)
    {
        $this->id = $id;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle(): void
    {
        Artisan::call('company:import', ['--id' => $this->id]);
    }
}
