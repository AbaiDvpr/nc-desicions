<?php declare(strict_types=1);

namespace App\Http\Controllers\Backend\Roles\Permissions;

use App\Http\Controllers\Backend\BaseController;

class CreateController extends BaseController
{
    /**
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View
     */
    public function __invoke()
    {
        $guards = collect(config('auth.guards'))->map(function ($guard, $guardData) {
            return head([$guardData => $guardData]);
        })->toArray();

        return view('backend.roles.permissions.create', [
            'guards' => $guards
        ]);
    }
}
