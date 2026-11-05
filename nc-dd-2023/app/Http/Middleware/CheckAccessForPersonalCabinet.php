<?php declare(strict_types=1);

namespace App\Http\Middleware;

use App\DI;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

/**
 * Мидлваре которая проверяет авторизован ли пользователь
 */
class CheckAccessForPersonalCabinet
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure                  $next
     * @param  string|null               ...$guards
     * @return mixed
     */
    public function handle(Request $request, Closure $next, ...$guards): mixed
    {
        $guards = empty($guards) ? [null] : $guards;

        foreach ($guards as $guard) {
            if (Auth::guard($guard)->check()) {
                $client = DI::getRepository()->getUser()->findById(auth()->id());

                if ($client->hasRole('client') && $client->isActivated()) {
                    view()->share(['client' => $client->getUserData()->getPublicData()]);

                    return $next($request);
                }

                return redirect()->route('auth.exit');
            }

            Auth::guard($guard)->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
        }

        return redirect()->route('frontend.auth.login');
    }
}
