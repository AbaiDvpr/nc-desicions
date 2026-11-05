<?php declare(strict_types=1);

namespace App\Http\Controllers\Api;

use Illuminate\Support\Facades\Artisan;

/**
 * API класс для получения курсов валют
 */
class Rates extends BaseController
{
    /**
     * Вызывает консольную команду для получения курсов валют
     *
     * @return int
     */
    public function getRatesAction(): int
    {
        return Artisan::call('get:rates');
    }
}
