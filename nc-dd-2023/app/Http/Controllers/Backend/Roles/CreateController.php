<?php declare(strict_types=1);

namespace App\Http\Controllers\Backend\Roles;

use App\Http\Controllers\Backend\BaseController;
use Spatie\Permission\Models\Permission;

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

        $permissions = Permission::all();

        return view('backend.roles.create', compact('guards', 'permissions'));
    }
}
