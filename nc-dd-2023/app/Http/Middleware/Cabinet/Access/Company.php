<?php declare(strict_types=1);

namespace App\Http\Middleware\Cabinet\Access;

use App\DI;
use Closure;
use Illuminate\Http\Request;

/**
 * Мидлвар для проверки компании на принадлежность текущему пользователю
 */
class Company
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
        $uuid    = $request->companyUuid ?? $request->uuid;
        $company = DI::getRepository()->getCompany()->getByUuid((string) $uuid);

        if (!$company || $company->getUserId() !== auth()->id()) {
            return response()->json([
                'message' => 'Компания не найдена'
            ], 404);
        }

        return $next($request);
    }
}
