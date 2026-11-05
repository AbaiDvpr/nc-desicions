<?php declare(strict_types=1);

namespace App\Http\Controllers\Backend\Roles;

use App\Http\Controllers\Backend\BaseController;
use App\Http\Requests\Backend\Roles\UpdateRequest;
use Illuminate\Http\RedirectResponse;
use Spatie\Permission\Models\Role;

class UpdateController extends BaseController
{
    /**
     * @param UpdateRequest $request
     * @return RedirectResponse
     */
    public function __invoke(UpdateRequest $request):RedirectResponse
    {
        $role = Role::find((int) $request->role);

        $role->update($request->only(['name', 'guard_name']));

        $role->syncPermissions($request->input('permissions', []));

        return redirect()->route('backend.roles.index')->with([
            'success' => "Роль успешно обновлена"
        ]);
    }
}
