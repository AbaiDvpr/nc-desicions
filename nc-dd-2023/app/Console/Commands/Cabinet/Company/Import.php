<?php declare(strict_types=1);

namespace App\Console\Commands\Cabinet\Company;

use App\DI;
use Illuminate\Console\Command;
use App\Traits\Cache\Cacheable;
use App\Console\Commands\Cabinet\Company\Import\Spreadsheet;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Console\Command\Command as CommandBase;

/**
 * Консольный класс для работы с импорт файлом компании
 *
 * php artisan company:import --id={id}
 */
class Import extends Command
{
    use Cacheable;

    /**
     * @var string
     */
    protected $signature = 'company:import {--id=}';

    /**
     * @var string
     */
    protected $description = 'Импортирует данные файла в базу относительно ID';

    /**
     * Обработчик
     *
     * @return int
     */
    public function handle(): int
    {
        $id     = (int) $this->option('id');
        $import = DI::getRepository()->getCompany()->getImport()->getById($id);

        if (!$import) {
            Log::error('[Import] Файл импорта не найден', [
                'id' => $id,
            ]);

            return CommandBase::FAILURE;
        }

        return (new Spreadsheet())->handle($import);
    }
}
