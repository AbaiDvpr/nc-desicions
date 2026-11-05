<?php declare(strict_types=1);

namespace App\Http\Controllers\Backend\Seo;

use App\Http\Controllers\Backend\BaseController;
use App\Http\Requests\Backend\Seo\UpdateRequest;
use App\Models\Seo;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Arr;

class UpdateController extends BaseController
{
    /**
     * @param UpdateRequest $request
     * @param int $seo_id
     * @return RedirectResponse
     */
    public function __invoke(UpdateRequest $request, int $seo_id): RedirectResponse
    {
        try {

            $post = Arr::collapse([$request->only(['title', 'description', 'keywords']), [
                'updated_id' => $this->user->id
            ]]);

            $seo = Seo::findOrFail((int)$seo_id);

            $seo->update($post);

            return redirect()->route('backend.seo.index')->with(['success' => 'Настройки сайта успешно сохранены']);
        } catch (\Exception $exception) {
            return redirect()->back()->withErrors([
                'Произошла непредвиденная ошибка. Ошибка: ' . $exception->getMessage()
            ]);
        }
    }
}
