<?php declare(strict_types=1);

namespace App\Http\Controllers\Backend\Modules\Categories;

use App\Http\Controllers\Backend\BaseController;
use App\Models\Categories;
use Illuminate\Http\RedirectResponse;

class CreateController extends BaseController
{
    /**
     * @return RedirectResponse
     */
    public function __invoke(): RedirectResponse
    {
        try {
            $category = Categories::create([
                'created_id' => $this->user->id,
                'updated_id' => $this->user->id
            ]);

            return redirect()->route('backend.modules.categories.edit', ['category' => $category->id]);
        } catch (\Exception $exception) {
            return redirect()->route('backend.modules.categories.index')
                ->withErrors(['Произошла ошибка. Обратитесь к администратору']);
        }
    }
}
