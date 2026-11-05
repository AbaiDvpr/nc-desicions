<?php declare(strict_types=1);

namespace App\Http\Middleware\Cabinet\Access;

use App\DI;
use Closure;
use Illuminate\Http\Request;

/**
 * Мидлвар для проверки проекта на принадлежность текущему пользователю
 */
class Project
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
        $project = DI::getRepository()->getProject()->getOneByUuid((string) $request->uuid);

        if (!$project || $project->getUserId() !== auth()->id()) {
            return response()->json([
                'message' => 'Финансовая модель не найдена'
            ], 404);
        }

        return $next($request);
    }
}
