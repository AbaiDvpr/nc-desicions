<?php declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\BaseController as Base;
use App\Models\Seo;
use Illuminate\Support\Facades\View;

/**
 * Главная страница апи проекта
 */
class IndexController extends Base
{
    public function index()
    {
        View::share('seo', Seo::first());

        return view('api.index');
    }
}
