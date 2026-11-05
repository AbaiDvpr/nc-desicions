<?php declare(strict_types=1);

namespace App\Http\Controllers\Backend\Modules\Categories;

use App\Http\Controllers\Backend\BaseController;
use App\Models\Categories;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class DeleteController extends BaseController
{
    /**
     * @param Request $request
     * @return RedirectResponse
     */
    public function __invoke(Request $request): RedirectResponse
    {
        try {
            $category = Categories::findOrFail((int)$request->category);

            $category->delete();

            return redirect()->back()->with(['success' => 'Категория успешно удалена']);
        } catch (\Exception $exception) {
            return redirect()->back()->withErrors(['Произошла непредвиденная ошибка. Ошибка: ' . $exception->getMessage()]);
        }
    }
}
