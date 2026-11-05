<?php declare(strict_types=1);

namespace App\Http\Controllers\Backend\Roles\Permissions;

use App\Http\Requests\Backend\Roles\Permissions\UpdateRequest;
use App\Http\Controllers\Backend\BaseController;
use Illuminate\Http\RedirectResponse;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class UpdateController extends BaseController
{
    /**
     * @param UpdateRequest $request
     * @return RedirectResponse
     */
    public function __invoke(UpdateRequest $request):RedirectResponse
    {
        $permission = Permission::find((int) $request->permission);

        $permission->update($request->input());

        return redirect()->route('backend.roles.permissions.index')->with([
            'success' => "Правило успешно обновлено"
        ]);
    }
}
