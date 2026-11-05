<?php declare(strict_types=1);

namespace App\Http\Controllers\Frontend\Cabinet;

use App\DI;
use App\Http\Controllers\Frontend\BaseController;
use App\Http\Requests\Frontend\Cabinet\Profile\UpdateRequest;
use App\Http\Requests\Frontend\Cabinet\Profile\UploadAvatarRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;

/**
 * Контроллер для работы с профилем пользователя
 */
class ProfileController extends BaseController
{
    /**
     * Обновляет данные профиля
     *
     * @param  \App\Http\Requests\Frontend\Cabinet\Profile\UpdateRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateAction(UpdateRequest $request): JsonResponse
    {
        try {
            $post = $request->only(['name', 'email', 'mobile', 'birthday', 'password']);

            if (is_null($request->input('password'))) {
                Arr::forget($post, ['password']);
            }

            $post['mobile'] = cleanPhone($post['mobile']);

            $user = DI::getRepository()->getUser()->update($this->client->getAuthIdentifier(), $post);

            if (!is_null($request->input('password'))) {
                auth()->logout();
            }

            return response()->json([
                'user'        => $user->getUserData()->getPublicData(),
                'redirectUrl' => !is_null($request->input('password')) ? route('frontend.auth.login') : null,
            ]);
        } catch (\Throwable $throwable) {
            Log::error('[Profile] Ошибка сохранения карточки профиля', [
                'throwable' => $throwable,
            ]);
        }

        return response()->json([
            'message' => 'Произошла ошибка во время сохранения, попробуйте позднее',
        ])->setStatusCode(500);
    }

    /**
     * Загружает фотографию пользователя
     *
     * @param  \App\Http\Requests\Frontend\Cabinet\Profile\UploadAvatarRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function uploadAvatarAction(UploadAvatarRequest $request): JsonResponse
    {
        return response()->json([
            'url' => DI::getRepository()->getUser()->uploadAvatar(
                $this->client->getAuthIdentifier(),
                $request
            ),
        ]);
    }

    /**
     * Принимает пользовательское соглашение для курсов
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function applyCourseEulaAction(): JsonResponse
    {
        return response()->json([
            'success' => DI::getRepository()->getUser()->applyCourseEula(
                $this->client->getAuthIdentifier(),
            ),
        ]);
    }
}
