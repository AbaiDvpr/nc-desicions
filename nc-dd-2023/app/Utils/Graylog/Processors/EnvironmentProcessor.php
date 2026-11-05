<?php declare(strict_types=1);

namespace App\Utils\Graylog\Processors;

use Monolog\LogRecord;
use Monolog\Processor\ProcessorInterface;

/**
 * Добавление в контекст логера переменной окружения
 */
class EnvironmentProcessor implements ProcessorInterface
{
    /**
     * Добавляет переменную окружения в логгер
     *
     * @param  \Monolog\LogRecord $record
     * @return \Monolog\LogRecord
     */
    public function __invoke(LogRecord $record): LogRecord
    {
        $context = $record->context;

        if (!array_key_exists('env', $context)) {
            $context['env'] = config('app.env');
        }

        return $record->with(context: $context);
    }
}