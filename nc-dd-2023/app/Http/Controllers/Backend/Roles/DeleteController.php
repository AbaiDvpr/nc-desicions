<?php declare(strict_types=1);

namespace App\Http\Controllers\Backend\Roles;

use App\Http\Controllers\Backend\BaseController;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class DeleteController extends BaseController
{
    /**
     * @param Request $request
     * @return RedirectResponse
     */
    public function __invoke(Request $request):RedirectResponse
    {
        $role = Role::find((int) $request->role);

        if ($role) {
            $role->delete();

            return redirect()->route('backend.roles.index')->with([
                'success' => "Роль успешно удалена"
            ]);
        }

        return redirect()->back()->withErrors([
            "Ошибка при удалении, роль не найдена"
        ]);
    }
}
