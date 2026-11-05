<?php declare(strict_types=1);

namespace App\Http\Controllers\Backend\Modules\Pages;

use App\Http\Controllers\Backend\BaseController;
use App\Models\Pages;
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
            $page = Pages::findOrFail((int)$request->page);

            $page->delete();

            return redirect()->back()->with(['success' => 'Страница успешно удалена']);
        } catch (\Exception $exception) {
            return redirect()->back()->withErrors(['Произошла непредвиденная ошибка. Ошибка: ' . $exception->getMessage()]);
        }
    }
}
