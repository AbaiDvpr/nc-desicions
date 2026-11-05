<?php

namespace App\Http\Controllers\Backend\Notifications;

use App\Http\Controllers\Backend\BaseController;
use Illuminate\Http\RedirectResponse;

class MarkAsReadController extends BaseController
{

    /**
     * @param $notify_id
     * @return RedirectResponse
     */
    public function __invoke($notify_id): RedirectResponse
    {
        $this->user
            ->unreadNotifications
            ->when($notify_id, function ($query) use ($notify_id) {
                return $query->where('id', $notify_id);
            })
            ->markAsRead();

        return back();
    }
}
