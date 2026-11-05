<?php declare(strict_types=1);

namespace App\Http\Controllers\Api\Handbook;

use App\Http\Controllers\Api\BaseController as Base;

/**
 * Класс для проверки доступности сервисов
 */
class BaseController extends Base
{
    /**
     * Возвращает список параметров формы по умолчанию
     *
     * @return array
     */
    protected function getDefaultParameters(): array
    {
        return config('handbook.common');
    }
}
