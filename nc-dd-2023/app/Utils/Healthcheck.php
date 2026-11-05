<?php declare(strict_types=1);

namespace App\Utils;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;

/**
 * Проверка работоспособности сайта
 */
class Healthcheck
{
    /**
     * Возвращает статус проверки доступности сервисов
     *
     * @return array
     */
    public function getStatus(): array
    {
        $result = [
            'time'     => date('c'),
            'host'     => gethostname(),
            'services' => [
                'cache' => $this->executeMeasured(function () {
                    return $this->redisIsAlive('cache');
                }),
                'mysql' => $this->executeMeasured(function () {
                    return $this->mysqlIsAlive('mysql');
                }),
                'mongoIsAlive' => $this->executeMeasured(function () {
                    return $this->mongoIsAlive('mongodb');
                }),
            ],
        ];

        $hasErrors = false;

        foreach ($result['services'] as &$service) {
            if (!$service['result']) {
                $hasErrors = true;

                $service['result'] = 'error';
                $service['code']   = 1;
            } else {
                $service['result'] = 'ok';
                $service['code']   = 0;
            }
        }

        $result['status'] = $hasErrors ? 'error' : 'ok';

        return $result;
    }

    /**
     * Проверяет доступность базы данных mysql.
     *
     * @param  string $connection
     * @return bool
     */
    protected function mysqlIsAlive(string $connection): bool
    {
        try {
            $options = config('database.connections')[$connection];

            $dbh = new \PDO(
                sprintf('mysql:host=%s;dbname=%s', $options['host'], $options['database']),
                $options['username'],
                $options['password']
            );

            $result = $dbh->query('SELECT 1')->rowCount() === 1;
        } catch (\Throwable $e) {
            $result = false;

            Log::error('[Healthcheck] Не удалось проверить доступность MySQL', [
                'connection'   => $connection,
                'exception' => $e,
            ]);
        }

        return $result;
    }

    /**
     * Проверяет доступность базы данных MongoDB.
     *
     * @param  string $connection
     * @return bool
     */
    protected function mongoIsAlive(string $connection): bool
    {
        try {
            $dbh = DB::connection($connection);

            $result = $dbh->getMongoClient()->listDatabaseNames()->count() > 0;
        } catch (\Throwable $e) {
            $result = false;

            Log::error('[Healthcheck] Не удалось проверить доступность MongoDb', [
                'connection'   => $connection,
                'exception' => $e,
            ]);
        }

        return $result;
    }

    /**
     * Проверяет доступность Кеша.
     *
     * @param  string $connection
     * @return bool
     */
    protected function redisIsAlive(string $connection): bool
    {
        $result = true;

        try {
            $client = Redis::connection($connection);

            if ($client) {
                $pong = $client->command('ping');

                if (0 !== strcasecmp($pong->getPayload(), 'pong')) {
                    $result = false;

                    Log::error('[Healthcheck] Не удалось проверить доступность cache', [
                        'connection' => $connection,
                        'pong'       => $pong,
                    ]);
                }
            } else {
                throw new \Exception('Не удалось подключиться к сервису кеширования');
            }
        } catch (\Throwable $e) {
            $result = false;

            Log::error('[Healthcheck] Не удалось проверить доступность cache', [
                'connection' => $connection,
                'exception'  => $e,
            ]);
        }

        return $result;
    }

    /**
     * Исполняет функцию, возвращая результат и время исполнения.
     *
     * @param  callable $callback
     * @return array
     */
    protected function executeMeasured(callable $callback): array
    {
        $time   = microtime(true);
        $result = call_user_func($callback);
        $time   = microtime(true) - $time;

        return [
            'result' => $result,
            'time'   => sprintf('%0.3f', $time * 1000),
        ];
    }
}
