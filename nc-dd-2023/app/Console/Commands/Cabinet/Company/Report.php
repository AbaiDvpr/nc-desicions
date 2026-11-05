<?php declare(strict_types=1);

namespace App\Console\Commands\Cabinet\Company;

use App\Console\Commands\Cabinet\Company\Report\Balance;
use App\Console\Commands\Cabinet\Company\Report\Dds;
use App\Console\Commands\Cabinet\Company\Report\Opu;
use App\Console\Commands\Cabinet\Company\Report\TypeInterface;
use Illuminate\Console\Command;
use App\Models\Company;
use App\Traits\Cache\Cacheable;

/**
 * Консольный класс для работы с отчетами ОПУ, ДДС и Баланса
 *
 * php artisan company:report --uuid={uuid} --type={type} --period={period}
 */
class Report extends Command
{
    use Cacheable;

    /**
     * @var string
     */
    protected $signature = 'company:report {--uuid=} {--type=} {--period=}';

    /**
     * @var string
     */
    protected $description = 'Генерирует отчет о прибыли для ОПУ, ДДС и Баланса';

    /**
     * Обработчик
     *
     * @return int
     * @throws \ErrorException
     */
    public function handle(): int
    {
        $type   = (string) $this->option('type');
        $uuid   = (string) $this->option('uuid');
        $period = (string) $this->option('period');

        return $this->getInstance($type)->handle($uuid, $period);
    }

    /**
     * Возвращает инстанс типа отчета
     *
     * @throws \ErrorException
     */
    protected function getInstance(string $type): TypeInterface
    {
        switch ($type) {
            case Company::TYPE_OPU:
                return new Opu();
            case Company::TYPE_DDS:
                return new Dds();
            case Company::TYPE_BALANCE:
                return new Balance();
        }

        throw new \ErrorException('[Report] Тип отчета не определен');
    }
}
