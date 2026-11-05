<?php declare(strict_types=1);

namespace App\Http\Controllers;

use App\DI;

/**
 * Базовый контроллер для приложений
 */
class BaseController extends Controller
{
    /**
     * @var \App\Models\User|null
     */
    public $client;

    /**
     * Frontend BaseController constructor.
     */
    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            if (auth()->check()) {
                $this->client = DI::getRepository()->getUser()->findById(auth()->id());
            }

            return $next($request);
        });
    }
}
