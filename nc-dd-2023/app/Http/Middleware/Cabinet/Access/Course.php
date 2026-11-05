<?php declare(strict_types=1);

namespace App\Http\Middleware\Cabinet\Access;

use App\DI;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Мидлвар для проверки доступности курса для пользователя
 */
class Course
{
    /**
     * Обработка входящего запроса
     *
     * @param \Illuminate\Http\Request $request
     * @param \Closure                 $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next): mixed
    {
        $user = Auth::user();

        if (!$user->hasCourseAccess()) {
            return response()->json([
                'message' => 'Курсы не найдены'
            ], 404);
        }

        return $next($request);
    }
}
