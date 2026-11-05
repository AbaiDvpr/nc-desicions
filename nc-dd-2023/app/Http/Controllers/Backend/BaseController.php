<?php declare(strict_types=1);

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller as Base;
use App\Models\User;
use Illuminate\Support\Facades\Cache;

class BaseController extends Base
{

    public $user;

    /**
     * BaseController constructor.
     */
    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            $this->user = auth()->user();

            view()->share([
                'authUser'           => $this->user,
                'profileSmallThumb'  => $this->getProfileThumb(),
                'notifications_head' => $this->user->unreadNotifications,
            ]);

            return $next($request);
        });
    }

    /**
     * Получить миниатюру профиля
     *
     * @return string
     */
    protected function getProfileThumb(): string
    {
        return Cache::remember('profile-avatar:' . $this->user->id, 60 * 60 * 24, function () {
            $avatar = '/admin/assets/img/avatars/no-photo.svg';

            $mediaThumb = $this->user->getFirstMedia(User::PROFILE_COLLECTION_NAME);

            if (!is_null($mediaThumb)) {
                return $mediaThumb->getUrl('smallThumb') ?? $avatar;
            }

            return $avatar;
        });
    }
}
