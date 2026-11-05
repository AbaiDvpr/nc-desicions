<?php declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Utils\Healthcheck;
use Illuminate\Http\JsonResponse;

/**
 * Класс для проверки доступности сервисов
 */
class StatusController extends BaseController
{
    /**
     * Проверяет работоспособность сайта проверяя на доступность сервисов
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function status(): JsonResponse
    {
        $healthcheck = new Healthcheck();
        $status      = $healthcheck->getStatus();

        return response()->json($status, $status['status'] === 'ok' ? 200 : 500);
    }
}
