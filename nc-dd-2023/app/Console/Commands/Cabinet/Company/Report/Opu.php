<?php declare(strict_types=1);

namespace App\Console\Commands\Cabinet\Company\Report;

use App\DI;
use http\Exception;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Console\Command\Command;

/**
 * Консольный класс для работы с ОПУ
 *
 * php artisan company:report --type=opu --uuid={uuid}
 */
class Opu implements TypeInterface
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
            $data   = DI::getRepository()->getCompany()->getOpu()->getForCompany($uuid, $period);
            $report = DI::getDH()->getCompany()->getOpu()->getFact()->calculate($uuid, $data);

            DI::getRepository()->getCompany()->getOpuReport()->createOrUpdate($uuid, $report);
        } catch (\Throwable $throwable) {
            Log::error('[OpuReport] Ошибка запуска обработчика отчета', [
                'exception' => $throwable
            ]);

            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }
}
