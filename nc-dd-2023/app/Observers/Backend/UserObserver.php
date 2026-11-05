<?php declare(strict_types=1);

namespace App\Observers\Backend;

use App\Models\User;
use Illuminate\Support\Facades\Cache;

class UserObserver
{
    /**
     * Handle the user "created" event.
     *
     * @param User $user
     */
    public function created(User $user)
    {
        //
    }

    /**
     * Handle the user "updated" event.
     *
     * @param User $user
     */
    public function updated(User $user)
    {
        //
    }

    /**
     * Handle the user "deleted" event.
     *
     * @param User $user
     */
    public function deleted(User $user)
    {
        //
    }

    /**
     * Handle the user "restored" event.
     *
     * @param User $user
     */
    public function restored(User $user)
    {
        //
    }

    /**
     * Handle the user "force deleted" event.
     *
     * @param User $user
     */
    public function forceDeleted(User $user)
    {
        //
    }
}
