<?php declare(strict_types=1);

namespace App\Http\Controllers\Backend\Roles\Permissions;

use App\Http\Requests\Backend\Roles\Permissions\StoreRequest;
use App\Http\Controllers\Backend\BaseController;
use Illuminate\Http\RedirectResponse;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class StoreController extends BaseController
{
    /**
     * @param  StoreRequest $request
     * @return RedirectResponse
     */
    public function __invoke(StoreRequest $request):RedirectResponse
    {
        Permission::create($request->input());

        return redirect()->route('backend.roles.permissions.index')->with([
            'success' => "Правило успешно добавлено"
        ]);
    }
}
