<?php declare(strict_types=1);

namespace App\Utils\Graylog\Processors;

use Monolog\LogRecord;
use Monolog\Processor\ProcessorInterface;

/**
 * Добавление в контекст логгера имени пользователя
 */
class UserProcessor implements ProcessorInterface
{
    /**
     * Добавляет переменную пользователя в логгер
     *
     * @param  \Monolog\LogRecord $record
     * @return \Monolog\LogRecord
     */
    public function __invoke(LogRecord $record): LogRecord
    {
        $context = $record->context;

        if (!array_key_exists('user', $context) && !empty(config('app.user'))) {
            $context['user'] = config('app.user');
        }

        return $record->with(context: $context);
    }
}