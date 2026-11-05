<?php

namespace App\Http\Controllers\Backend\Notifications;

use App\Http\Controllers\Backend\BaseController;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;

class IndexController extends BaseController
{

    /**
     * @return Factory|View
     */
    public function __invoke()
    {
        $notifications = $this->user->unreadNotifications();

        return view('backend.notifications.index', [
            'notifications' => $notifications->paginate(30)
        ]);
    }
}
