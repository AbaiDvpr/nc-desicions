<?php declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\BaseController;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;

/**
 * Контроллер для разавторизации пользователя
 */
class LogoutController extends BaseController
{
    /**
     * Разлогинивает пользователя из системы
     *
     * @param  \Illuminate\Http\Request          $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function __invoke(Request $request): RedirectResponse
    {
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        Auth::logout();

        return redirect()->to(config('app.url'))->withCookie(Cookie::forget('cabinetHack'));
    }
}
