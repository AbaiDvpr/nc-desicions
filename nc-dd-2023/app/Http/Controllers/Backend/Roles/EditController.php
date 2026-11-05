<?php declare(strict_types=1);

namespace App\Http\Controllers\Backend\Roles;

use App\Http\Controllers\Backend\BaseController;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class EditController extends BaseController
{
    /**
     * @param Request $request
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View
     */
    public function __invoke(Request $request)
    {
        $role = Role::find((int)$request->role);

        $guards = collect(config('auth.guards'))->map(function ($guard, $guardData) {
            return head([$guardData => $guardData]);
        })->toArray();

        $permissions = Permission::all();

        return view('backend.roles.edit', compact('role', 'guards', 'permissions'));
    }
}
