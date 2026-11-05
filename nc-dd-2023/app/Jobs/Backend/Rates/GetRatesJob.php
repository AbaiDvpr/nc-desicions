<?php declare(strict_types=1);

namespace App\Jobs\Backend\Rates;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Джоба бля отправки запроса в апи за получением курсов валют
 */
class GetRatesJob implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Execute the job.
     *
     * @return void
     * @throws ClientException|GuzzleException
     */
    public function handle(): void
    {
        try {
            $client = new Client();

            $client->request('GET', route('api.getrates'));

            return;
        } catch (ClientException $exception) {
            Log::error(
                'Ошибка при выполнении консольной задачи: ' . $exception->getCode() . ': ' . $exception->getMessage()
            );
        }
    }
}
