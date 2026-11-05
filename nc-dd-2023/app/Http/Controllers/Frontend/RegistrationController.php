<?php declare(strict_types=1);

namespace App\Http\Controllers\Frontend;

use App\DI;
use App\Http\Requests\Frontend\Registration\RegistrationRequest;
use App\Http\Requests\Frontend\Registration\RestorePasswordRequest;
use App\Http\Requests\Frontend\Registration\RestoreRequest;
use App\Jobs\Frontend\Registration\RegisterVerificationJob;
use App\Jobs\Frontend\Registration\RestorePasswordJob;
use App\Repositories\User\Exceptions\NotFoundException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

/**
 * Контроллер для работы с регистрацией
 */
class RegistrationController extends BaseController
{
    /**
     * Главная страница с формой регистрации
     *
     * @return \Illuminate\Contracts\View\View
     */
    public function index(): View
    {
        return view('frontend.registration.index');
    }

    /**
     * Обработчик формы регистрации
     *
     * @param  \App\Http\Requests\Frontend\Registration\RegistrationRequest $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(RegistrationRequest $request): RedirectResponse
    {
        $post     = Arr::collapse([$request->input(), ['driver' => 'web']]);
        $userRepo = DI::getRepository()->getUser();

        try {
            Arr::forget($post, 'password_confirmation');
            Arr::forget($post, 'g-recaptcha-response');

            $user = $userRepo->create($post);

            $user->assignRole('client'); // Назначаем пользователю роль клиента

            $userRepo->update($user->id, [
                'verification_code' => md5($user->id . $user->email . microtime()),
            ]);

            RegisterVerificationJob::dispatch($user->id);

            return redirect()->back()->with(['success' => true]);
        } catch (\Throwable $throwable) {
            Log::error('Ошибка при регистрации пользователя', [
                'exception' => $throwable
            ]);
        }

        return redirect()->back()->withErrors([
            'Произошла ошибка во время регистрации, пожалуйста попробуйте позднее'
        ]);
    }

    /**
     * Обработчик подтверждения регистрации по ссылке из письма
     *
     * @param  string|null                    $code
     * @return \Illuminate\Contracts\View\View
     */
    public function verification(string $code = null)
    {
        $status   = false;
        $userRepo = DI::getRepository()->getUser();

        try {
            $user = $userRepo->findByVerificationCode($code);

            if ($user) {
                $userRepo->update($user->getAuthIdentifier(), [
                    'verification_code' => null,
                    'email_verified_at' => now(),
                    'apiToken'          => $user->createToken('apiToken')->plainTextToken,
                ]);

                $user->applyTariff(DI::getRepository()->getModelsTariff()->getDefault());

                $userRepo->clearCache($user);

                $status = true;
            }
        } catch (\Throwable $throwable) {
            Log::error('Ошибка при верификации пользователя.', [
                'exception' => $throwable
            ]);
        }

        return view('frontend.registration.verification', compact('status'));
    }

    /**
     * Форма восстановления пароля
     *
     * @return \Illuminate\Contracts\View\View
     */
    public function restore(): View
    {
        return view('frontend.registration.restore');
    }

    /**
     * Обработчик формы восстановления пароля
     *
     * @param  \App\Http\Requests\Frontend\Registration\RestoreRequest $request
     * @return RedirectResponse
     */
    public function postRestore(RestoreRequest $request): RedirectResponse
    {
        $userRepo = DI::getRepository()->getUser();

        try {
            $user = $userRepo->findByEmail($request->input('email'));

            if (is_null($user)) {
                throw new NotFoundException('Ошибка, пользователь не найден');
            }

            $userRepo->update($user->id, [
                'verification_code' => md5($user->id . $user->email . microtime())
            ]);

            RestorePasswordJob::dispatch($user->id);

            return redirect()->back()->with(['success' => true]);
        } catch (\Throwable $throwable) {
            Log::error('Ошибка во время восстановления пароля', [
                'exception' => $throwable
            ]);
        }

        return redirect()->back()->withErrors([
            'Произошла ошибка при восстановлении пароля, попробуйте позднее'
        ]);
    }

    /**
     * Экран для отображения формы смены пароля после перехода по ссылке из письма
     *
     * @param  string                          $code
     * @return \Illuminate\Contracts\View\View
     */
    public function updatePassword(string $code): View
    {
        $user = DI::getRepository()->getUser()->findByVerificationCode($code);

        return view('frontend.registration.restore-password', [
            'status' => (bool) $user,
            'code'   => $code,
        ]);
    }

    /**
     * Форма обновления пароля после перехода по ссылке
     *
     * @param  \App\Http\Requests\Frontend\Registration\RestorePasswordRequest    $request
     * @param  string                                                             $code
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View
     */
    public function postUpdatePassword(RestorePasswordRequest $request, string $code)
    {
        $status   = false;
        $userRepo = DI::getRepository()->getUser();

        try {
            $user = $userRepo->findByVerificationCode($code);

            if (is_null($user)) {
                throw new NotFoundException('Пользователь не определен');
            }

            $userRepo->update($user->id, [
                'password'          => $request->input('password'),
                'verification_code' => null,
            ]);

            $status = true;
        } catch (\Throwable $throwable) {
            Log::error('Ошибка после перехода по ссылке на смену пароля', [
                'exception' => $throwable,
            ]);
        }

        return view('frontend.registration.update-password', compact('status'));
    }
}
