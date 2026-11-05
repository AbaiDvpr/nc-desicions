<?php declare(strict_types=1);

namespace App\Http\Controllers\Backend\Ajax;

use Spatie\MediaLibrary\MediaCollections\Models\Media;
use App\Http\Controllers\Backend\BaseController;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;


class RemoveController extends BaseController
{
    /**
     * @param Request $request
     * @return RedirectResponse
     */
    public function __invoke(Request $request): RedirectResponse
    {
        try {
            $media = Media::findByUuid((string)$request->uuid);

            if (!is_null($media)) {
                if ($media->delete()) {
                    return redirect()->back()->with(['success' => 'Фото профиля удалено']);
                }
            }

            return redirect()->back()->withErrors(['Удаление произошло с ошибкой.']);
        } catch (\Exception $exception) {
            return redirect()->back()->withErrors([$exception->getMessage()]);
        }
    }
}
