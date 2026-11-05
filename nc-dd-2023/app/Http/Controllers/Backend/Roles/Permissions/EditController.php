<?php declare(strict_types=1);

namespace App\Http\Controllers\Backend\Roles\Permissions;

use App\Http\Controllers\Backend\BaseController;
use Spatie\Permission\Models\Permission;
use Illuminate\Http\Request;

class EditController extends BaseController
{
    /**
     * @param Request $request
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View
     */
    public function __invoke(Request $request)
    {
        $permission = Permission::find((int)$request->permission);

        $guards = collect(config('auth.guards'))->map(function ($guard, $guardData) {
            return head([$guardData => $guardData]);
        })->toArray();

        return view('backend.roles.permissions.edit', compact('permission', 'guards'));
    }
}
