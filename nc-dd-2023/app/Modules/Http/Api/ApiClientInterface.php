<?php declare(strict_types=1);

namespace App\Modules\Http\Api;

use App\Modules\Http\RequestCreatorInterface;

interface ApiClientInterface extends RequestCreatorInterface
{
    /**
     * Проверка доступности сервиса
     *
     * @return void
     */
    public function health(): void;
}
