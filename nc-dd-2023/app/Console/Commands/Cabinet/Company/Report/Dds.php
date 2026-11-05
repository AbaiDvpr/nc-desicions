<?php declare(strict_types=1);

namespace App\Console\Commands\Cabinet\Company\Report;

use App\DI;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Console\Command\Command;

/**
 * Консольный класс для работы с ДДС
 *
 * php artisan company:report --type=dds --uuid={uuid}
 */
class Dds implements TypeInterface
{
    /**
     * Обработчик
     *
     * @param  string      $uuid
     * @param  null|string $period
     * @return int
     */
    public function handle(string $uuid, ?string $period): int
    {
        try {
            $data   = DI::getRepository()->getCompany()->getDds()->getForCompany($uuid, $period);
            $report = DI::getDH()->getCompany()->getDds()->getFact()->calculate($uuid, $data);

            DI::getRepository()->getCompany()->getDdsReport()->createOrUpdate($uuid, $report);
        } catch (\Throwable $throwable) {
            Log::error('[DdsReport] Ошибка запуска обработчика отчета', [
                'exception' => $throwable
            ]);

            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }
}
