<?php declare(strict_types=1);

namespace App\Http\Controllers\Backend\Roles;

use App\Http\Controllers\Backend\BaseController;
use Spatie\Permission\Models\Role;


class IndexController extends BaseController
{
    /**
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View
     */
    public function __invoke()
    {
        $roles = Role::all();

        return view('backend.roles.index', compact('roles'));
    }
}
