<?php declare(strict_types=1);

namespace App\Http\Controllers\Auth\Backend;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\PostLoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;

class PostLoginController extends Controller
{

    /**
     * Контроллер для авторизации пользователей
     *
     * @param PostLoginRequest $request
     * @return RedirectResponse
     */
    public function __invoke(PostLoginRequest $request): RedirectResponse
    {
        $credentials = $request->only('email', 'password');

        if (Auth::attempt($credentials, true)) {
            return redirect()->intended();
        }

        return redirect()->back()->withInput();
    }
}
