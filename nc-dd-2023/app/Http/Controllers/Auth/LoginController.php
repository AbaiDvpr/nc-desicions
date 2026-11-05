<?php declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\DI;
use App\Http\Controllers\Frontend\BaseController;
use App\Http\Requests\Auth\PostLoginRequest;
use App\Repositories\User\Exceptions\NotFoundException;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\Log;

/**
 * Контроллер для авторизации пользователя
 */
class LoginController extends BaseController
{
    /**
     * Отображение главной страницы авторизации
     *
     * @return \Illuminate\Contracts\View\View
     */
    public function index(): View
    {
        return view('frontend.auth.login');
    }

    /**
     * Авторизация пользователя
     *
     * @param  \App\Http\Requests\Auth\PostLoginRequest                        $request
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Http\JsonResponse
     */
    public function login(PostLoginRequest $request): RedirectResponse|JsonResponse
    {
        $credentials = $request->only('email', 'password');
        $credentials = Arr::collapse([$credentials, ['admin' => false]]);

        if (Auth::attempt($credentials)) {
            $user = Auth::user();

            if (!$user->hasApiToken()) {
                // Создаем токен для запросов в API если не был ранее установлен
                $user = DI::getRepository()->getUser()->createApiToken($user);

                if (!$user) {
                    Log::error('Не удалось создать API токен', $user->getLogData());
                }
            }

            if ($request->isJson()) {
                return response()->json([
                    'user' => $user->getUserData()->getPublicData(),
                ]);
            }

            return redirect()->route('frontend.cabinet.home');
        }

        return redirect()->back()->withInput()->withErrors(['Проверьте, все ли данные указаны верно']);
    }

    /**
     * Авторизация пользователя через подписанный URL
     *
     * @param  \Illuminate\Http\Request         $request
     * @param  int                              $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function loginBySignature(Request $request, int $id): RedirectResponse
    {
        if (!$request->hasValidSignature(false)) {
            abort(403);
        }

        if (Auth::check()) {
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            Auth::logout();
        }

        try {
            $user = DI::getRepository()->getUser()->findById($id);

            if (!$user) {
                throw new NotFoundException('Пользователь не найден');
            }

            Auth::login($user);

            if (Auth::check() && !$user->hasApiToken()) {
                $user = DI::getRepository()->getUser()->createApiToken($user);

                if (!$user) {
                    Log::error('Не удалось создать API токен', $user->getLogData());
                }
            }

            return redirect()
                ->route('frontend.cabinet.home')
                ->withCookie('cabinetHack', true, config('session.lifetime'));
        } catch (NotFoundException $exception) {
            Log::error('[Auth] пользователь не найден', [
                'exception' => $exception,
                'userId'    => $id
            ]);
        }

        return redirect()->to(config('app.url'))->withCookie(Cookie::forget('cabinetHack'));
    }
}
