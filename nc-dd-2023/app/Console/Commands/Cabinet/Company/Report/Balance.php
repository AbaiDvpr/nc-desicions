<?php declare(strict_types=1);

namespace App\Console\Commands\Cabinet\Company\Report;

use App\DI;
use App\Models\Company;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Console\Command\Command;

/**
 * Консольный класс для работы с Балансом
 *
 * php artisan company:report --type=balance --uuid={uuid}
 */
class Balance implements TypeInterface
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
            $data   = DI::getRepository()->getCompany()->getBalance()->getForCompany($uuid, $period);
            $report = DI::getDH()->getCompany()->getBalance()->getFact()->calculate($uuid, $data);

            DI::getRepository()->getCompany()->getBalanceReport()->createOrUpdate($uuid, $report);

            // После сохранения фактических данных Баланса обновляем факт ДДС
            Artisan::call('company:report', [
                '--uuid' => $uuid,
                '--type' => Company::TYPE_DDS
            ]);
        } catch (\Throwable $throwable) {
            Log::error('[BalanceReport] Ошибка запуска обработчика отчета', [
                'exception' => $throwable
            ]);

            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }
}
