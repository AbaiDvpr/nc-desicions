<?php declare(strict_types=1);

namespace App\Console\Commands\Cabinet\Company\Report;

/**
 * Консольный интерфейс для работы с типами отчета
 */
interface TypeInterface
{
    /**
     * Обработчик
     *
     * @param  string $uuid
     * @param  string $period
     * @return int
     */
    public function handle(string $uuid, string $period): int;
}
