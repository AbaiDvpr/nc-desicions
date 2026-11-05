<?php declare(strict_types=1);

namespace App\Http\Controllers\Backend\Modules\Pages;

use App\Http\Controllers\Backend\BaseController;
use App\Models\Pages;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;

class UpdateController extends BaseController
{
    /**
     * @param Request $request
     * @return RedirectResponse
     */
    public function __invoke(Request $request): RedirectResponse
    {
        try {
            $post = Arr::collapse([$request->only(['title', 'text', 'alias', 'good']), [
                'updated_id' => $this->user->id
            ]]);

            $page = Pages::findOrFail((int)$request->page);

            $page->draft = false;
            $page->update($post);

            return redirect()->route('backend.modules.pages.index')
                ->with(['success' => 'Страница сохранена.']);
        } catch (\Exception $exception) {
            return redirect()->back()->withErrors([
                'Произошла непредвиденная ошибка. Ошибка: ' . $exception->getMessage()
            ]);
        }
    }
}
