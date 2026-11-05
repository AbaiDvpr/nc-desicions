<?php declare(strict_types=1);

namespace App\Http\Controllers\Backend\Roles\Permissions;

use App\Http\Controllers\Backend\BaseController;
use Spatie\Permission\Models\Permission;


class IndexController extends BaseController
{
    /**
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View
     */
    public function __invoke()
    {
        $permissions = Permission::all();

        return view('backend.roles.permissions.index', compact('permissions'));
    }
}
