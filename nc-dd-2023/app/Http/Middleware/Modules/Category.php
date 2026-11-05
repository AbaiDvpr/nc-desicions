<?php declare(strict_types=1);
namespace App\Http\Middleware\Modules;

use App\DI;
use Closure;
use Illuminate\Http\Request;

/**
 * Middleware на проверку категории новостей
 */
class Category
{
    /**
     * Проверяет, существует ли категория новостей
     *
     * @param  \Illuminate\Http\Request $request
     * @param  \Closure                 $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next): mixed
    {
        if (!$request->slug) {
            abort(404);
        }

        $category = DI::getRepository()->getModelsCategory()->getOneBySlug($request->slug);

        if (!$category) {
            abort(404);
        }

        return $next($request);
    }
}
