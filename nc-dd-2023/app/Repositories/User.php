<?php declare(strict_types=1);

namespace App\Repositories;

use App\DI;
use App\Http\Requests\Frontend\Cabinet\Profile\UploadAvatarRequest;
use App\Models\User as UserModel;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Репозиторий для работы с пользователями
 */
class User extends BaseRepository
{
    /**
     * Время на которое кешируем пользователя
     */
    const CACHE_USER_TIME = 86400;

    /**
     * Создание пользователя
     *
     * @param  array $attributes
     * @return mixed
     */
    public function create(array $attributes): UserModel
    {
        return UserModel::create($attributes);
    }

    /**
     * Получение пользователя по ID
     *
     * @param  int                   $id
     * @param  bool                  $useCache
     * @return \App\Models\User|null
     */
    public function findById(int $id, bool $useCache = true): ?UserModel
    {
        $user = function () use ($id) {
            return UserModel::find($id);
        };

        if ($useCache) {
            return $this->getCache()->getCached(
                $this->getCacheKey($id),
                $user,
                self::CACHE_USER_TIME
            );
        }

        return $user();
    }

    /**
     * Получение объекта пользователя по e-mail
     *
     * @param  string $email
     * @return \App\Models\User|null
     */
    public function findByEmail(string $email): ?UserModel
    {
        return UserModel::where('email', $email)->first();
    }

    /**
     * Возвращает пользователя по коду подтверждения
     *
     * @param  string                $code
     * @return \App\Models\User|null
     */
    public function findByVerificationCode(string $code): ?UserModel
    {
        return UserModel::where('verification_code', $code)->first();
    }

    /**
     * Очищает кеш пользователя
     *
     * @param  \App\Models\User $user
     * @return bool
     */
    public function clearCache(UserModel $user): bool
    {
        if ($user->tariff()) {
            DI::getRepository()->getUserTariff()->clearCache($user->tariff());
            DI::getRepository()->getProject()->getHistory()->clearCache($user);
        }

        return $this->getCache()->forget(
            $this->getCacheKey((string) $user->getAuthIdentifier())
        );
    }

    /**
     * Создает и устанавливает токен для запросов в API
     *
     * @param  \App\Models\User      $user
     * @return \App\Models\User|null
     */
    public function createApiToken(UserModel $user): ?UserModel
    {
        return $this->update($user->getAuthIdentifier(), [
            'apiToken' => $user->createToken('apiToken')->plainTextToken,
        ]);
    }

    /**
     * Обновление данных пользователя
     *
     * @param  int                   $id
     * @param  array                 $attributes
     * @return \App\Models\User|null
     */
    public function update(int $id, array $attributes = []): ?UserModel
    {
        $user = $this->findById($id);

        if (is_null($user)) {
            return null;
        }

        DB::transaction(function () use ($user, $attributes) {
            $user->fill($attributes)->save();
        }, 2);

        $this->getCache()->forget($this->getCacheKey($id));

        return $this->findById($id);
    }

    /**
     * Удаление пользователя по ID
     *
     * @param  int  $id
     * @return bool
     */
    public function deleteUserById(int $id): bool
    {
        $user = $this->findById($id);

        if ($user instanceof UserModel) {
            $this->getCache()->forget($this->getCacheKey($id));

            return $user->forceDelete() ?? false;
        }

        return false;
    }

    /**
     * Возвращает список уведомлений для пользователя
     *
     * @param  int                                $id
     * @param  int                                $limit
     * @return \Illuminate\Support\Collection|null
     */
    public function getUnreadNotifications(int $id, int $limit = 5): ?Collection
    {
        $user = $this->findById($id);

        if (!$user) {
            return null;
        }

        $notifications = $user->unreadNotifications()->limit($limit)->get();

        return $notifications->map(function ($notification) {
            return $notification->only(['id', 'data', 'read_at', 'created_at']);
        });
    }

    /**
     * Помечает прочитанным уведомление пользователя
     *
     * @param  int    $id
     * @param  string $notificationId
     * @return bool
     */
    public function markAsReadNotification(int $id, string $notificationId): bool
    {
        $user = $this->findById($id);

        if (!$user) {
            return false;
        }

        $user->unreadNotifications()->where('id', $notificationId)->first()->markAsRead();

        return true;
    }

    /**
     * Загружает фотографию пользователя
     *
     * @param  int                                                             $id
     * @param  \App\Http\Requests\Frontend\Cabinet\Profile\UploadAvatarRequest $request
     * @return string|null
     */
    public function uploadAvatar(int $id, UploadAvatarRequest $request): string|null
    {
        $user = $this->findById($id);

        if (is_null($user)) {
            return null;
        }

        try {
            $file     = $request->file('file');
            $fileName = md5(Str::random(20) . time()) . '.' . $file->extension();

            $user->clearMediaCollection(UserModel::PROFILE_COLLECTION_NAME);
            $user->addMedia($file)
                ->usingFileName($fileName)
                ->toMediaCollection(UserModel::PROFILE_COLLECTION_NAME);

            $media = $user->getFirstMedia(UserModel::PROFILE_COLLECTION_NAME);

            $this->getCache()->forget($this->getCacheKey($id));

            return !is_null($media) ? $media->getUrl('largeThumb') : null;
        } catch (\Throwable $throwable) {
            Log::error('[Profile] Ошибка загрузки фотографии профиля', [
                'throwable' => $throwable
            ]);
        }

        return null;
    }

    /**
     * Принимает пользовательское соглашение для курсов
     *
     * @param  int $id
     * @return bool
     */
    public function applyCourseEula(int $id): bool
    {
        $user = $this->findById($id);

        if (is_null($user)) {
            return false;
        }

        $this->getCache()->forget($this->getCacheKey($id));

        return $user->update(['courseEula' => true]);
    }

    /**
     * Возвращает последних пользователей
     *
     * @param  int                            $limit
     * @return \Illuminate\Support\Collection
     */
    public function getLatest(int $limit): Collection
    {
        return UserModel::where('admin', false)
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Возвращает количество пользователей
     *
     * @param  \Illuminate\Support\Carbon|null $period
     * @return int
     */
    public function getCountByPeriod(?Carbon $period = null): int
    {
        $users = new UserModel();

        if ($period) {
            $users = $users->where('created_at', '>=', $period);
        }

        return $users->count();
    }
}
