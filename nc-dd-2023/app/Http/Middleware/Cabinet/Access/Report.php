<?php declare(strict_types=1);

namespace App\Http\Middleware\Cabinet\Access;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class Report
{
    /**
     * Проверяем имеет ли доступ пользователь к скачиванию данного отчета по проекту
     *
     * @param  \Illuminate\Http\Request $request
     * @param  \Closure                 $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        $user    = Auth::user();
        $project = $user->projects()->find($request->uuid);

        if ($user->hasRole('administrator') || !is_null($project)) {
            return $next($request);
        }

        return abort(404);
    }
}
