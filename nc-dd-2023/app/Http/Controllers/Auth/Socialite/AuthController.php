<?php declare(strict_types=1);

namespace App\Http\Controllers\Auth\Socialite;

use App\DI;
use App\Http\Controllers\Frontend\BaseController;
use App\Models\User as UserModel;
use Faker\Factory;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\InvalidStateException;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Laravel\Socialite\Contracts\User as SocialiteUser;

/**
 * Контроллер для авторизации через соц сети
 */
class AuthController extends BaseController
{
    /**
     * Редирект на страницу авторизации в соц сети
     *
     * @param  string           $client
     * @return RedirectResponse
     */
    public function login(string $client = SocialiteConfig::SOCIALITE_GOOGLE): RedirectResponse
    {
        if (in_array($client, SocialiteConfig::ALL_SOCIALITE_DRIVERS, true)) {
            return Socialite::driver($client)->redirect();
        }

        return Socialite::driver(SocialiteConfig::SOCIALITE_GOOGLE)->redirect();
    }

    /**
     * Принимает результат авторизации в Google
     *
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function googleAction(): RedirectResponse
    {
        try {
            $socialiteUser = Socialite::driver(SocialiteConfig::SOCIALITE_GOOGLE)->user();

            $user = $this->getUserBySocialite($socialiteUser, SocialiteConfig::SOCIALITE_GOOGLE);

            return $this->authorizeUser($user);
        } catch (\Throwable $throwable) {
            Log::error('[Auth] Ошибка авторизации через google', [
                'exception' => $throwable
            ]);
        }

        return redirect()->route('frontend.auth.login')->withErrors([
            'Не удалось авторизоваться, попробуйте пожалуйста позднее'
        ]);
    }

    /**
     * Принимает результат авторизации в Github
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function githubAction(): RedirectResponse
    {
        try {
            $socialiteUser = Socialite::driver(SocialiteConfig::SOCIALITE_GITHUB)->user();

            $user = $this->getUserBySocialite($socialiteUser, SocialiteConfig::SOCIALITE_GITHUB);

            return $this->authorizeUser($user);
        } catch (\Throwable $throwable) {
            Log::error('[Auth] Ошибка авторизации через github', [
                'exception' => $throwable
            ]);
        }

        return redirect()->route('frontend.auth.login')->withErrors([
            'Не удалось авторизоваться, попробуйте пожалуйста позднее'
        ]);
    }

    /**
     * Принимает результат авторизации в Facebook
     *
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function facebookAction(): RedirectResponse
    {
        try {
            $socialiteUser = Socialite::driver(SocialiteConfig::SOCIALITE_FACEBOOK)->user();

            $user = $this->getUserBySocialite($socialiteUser, SocialiteConfig::SOCIALITE_FACEBOOK);

            return $this->authorizeUser($user);
        } catch (\Throwable $throwable) {
            Log::error('[Auth] Ошибка авторизации через facebook', [
                'exception' => $throwable
            ]);
        }

        return redirect()->route('frontend.auth.login')->withErrors([
            'Не удалось авторизоваться, попробуйте пожалуйста позднее'
        ]);
    }

    /**
     * Возвращает или создает нового пользователя
     *
     * @param  \Laravel\Socialite\Contracts\User $socialiteUser
     * @param  string                            $driver
     * @return \App\Models\User|null
     */
    protected function getUserBySocialite(SocialiteUser $socialiteUser, string $driver = 'web'): ?UserModel
    {
        $userRepo = DI::getRepository()->getUser();
        $user     = $userRepo->findByEmail($socialiteUser->getEmail());

        if (is_null($user)) {
            try {
                $user = $userRepo->create([
                    'email'             => $socialiteUser->getEmail(),
                    'nickname'          => $socialiteUser->getNickname(),
                    'name'              => $socialiteUser->getName() ?? $socialiteUser->getNickname() ?? $socialiteUser->getEmail(),
                    'driver'            => $driver,
                    'email_verified_at' => now(),
                    'password'          => Factory::create()->password(6, 8),
                ]);

                $user->applyTariff(DI::getRepository()->getModelsTariff()->getDefault());
            } catch (\Throwable $throwable) {
                Log::error('[Socialite] Не удалось зарегистрировать пользователя', [
                    'exception'    => $throwable,
                    'socialite_id' => $socialiteUser->getId(),
                    'email'        => $socialiteUser->getEmail(),
                    'nickname'     => $socialiteUser->getNickname(),
                    'name'         => $socialiteUser->getName(),
                    'driver'       => $driver,
                ]);

                throw new InvalidStateException('Не удалось пройти авторизацию');
            }
        }

        // Назначаем пользователю роль клиента
        if (!$user->hasRole(UserModel::ROLE_CLIENT)) {
            $user->assignRole(UserModel::ROLE_CLIENT);
        }

        if (!$user->hasApiToken()) {
            $user = DI::getRepository()->getUser()->createApiToken($user);

            if (!$user) {
                Log::error('Не удалось создать api токен', $user->getLogData());
            }
        }

        return $user;
    }

    /**
     * Авторизует пользователя
     *
     * @param  \App\Models\User                  $user
     * @return \Illuminate\Http\RedirectResponse
     */
    protected function authorizeUser(UserModel $user): RedirectResponse
    {
        Auth::login($user);

        if (!Auth::check()) {
            Log::error('[Auth] Внутренняя ошибка авторизации', [
                'user' => $user->getLogData()
            ]);

            return redirect()->route('frontend.auth.login')->withErrors([
                'Авторизация не удалась'
            ]);
        }

        return redirect()->route('frontend.cabinet.home');
    }
}
