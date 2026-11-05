<?php declare(strict_types=1);

namespace App\Http\Controllers\Backend\Modules\Pages;

use App\Http\Controllers\Backend\BaseController;
use App\Models\Pages;
use Illuminate\Http\RedirectResponse;

class CreateController extends BaseController
{
    /**
     * @return RedirectResponse
     */
    public function __invoke(): RedirectResponse
    {
        try {
            $page = Pages::create([
                'created_id' => $this->user->id,
                'updated_id' => $this->user->id
            ]);

            return redirect()->route('backend.modules.pages.edit', ['page' => $page->id]);
        } catch (\Exception $exception) {
            return redirect()->route('backend.modules.pages.index')
                ->withErrors(['Произошла ошибка. Обратитесь к администратору']);
        }
    }
}
