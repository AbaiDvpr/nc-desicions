<?php declare(strict_types=1);

namespace App\Http\Controllers\Backend\Modules\Categories;

use App\Http\Controllers\Backend\BaseController;
use App\Models\Categories;
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
            $post = Arr::collapse([$request->only(['title', 'description', 'slug', 'good']), [
                'updated_id' => $this->user->id
            ]]);

            $category = Categories::findOrFail((int)$request->category);

            $category->draft = false;
            $category->update($post);

            return redirect()->route('backend.modules.categories.index')
                ->with(['success' => 'Категория сохранена.']);
        } catch (\Exception $exception) {
            return redirect()->back()->withErrors([
                'Произошла непредвиденная ошибка. Ошибка: ' . $exception->getMessage()
            ]);
        }
    }
}
