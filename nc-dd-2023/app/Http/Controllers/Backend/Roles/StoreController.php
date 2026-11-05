<?php declare(strict_types=1);

namespace App\Http\Controllers\Backend\Roles;

use App\Http\Controllers\Backend\BaseController;
use App\Http\Requests\Backend\Roles\StoreRequest;
use Illuminate\Http\RedirectResponse;
use Spatie\Permission\Models\Role;

class StoreController extends BaseController
{
    /**
     * @param  StoreRequest $request
     * @return RedirectResponse
     */
    public function __invoke(StoreRequest $request):RedirectResponse
    {
        $role = Role::create($request->only(['name', 'guard_name']));

        $role->syncPermissions($request->input('permissions', []));

        return redirect()->route('backend.roles.index')->with([
            'success' => "Роль успешно добавлена"
        ]);
    }
}
