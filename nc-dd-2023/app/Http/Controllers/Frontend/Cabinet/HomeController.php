<?php declare(strict_types=1);

namespace App\Http\Controllers\Frontend\Cabinet;

use App\Http\Controllers\Frontend\BaseController;

/**
 * Контроллер для работы с личным кабинетом
 */
class HomeController extends BaseController
{
    public function __invoke()
    {
        return view('frontend.cabinet.index');
    }
}
