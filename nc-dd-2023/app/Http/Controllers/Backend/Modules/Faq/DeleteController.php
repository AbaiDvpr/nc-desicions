<?php declare(strict_types=1);

namespace App\Http\Controllers\Backend\Modules\Faq;

use App\DI;
use App\Http\Controllers\Backend\BaseController;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class DeleteController extends BaseController
{
    /**
     * Удаление вопроса
     *
     * @param  Request          $request
     * @return RedirectResponse
     */
    public function __invoke(Request $request): RedirectResponse
    {
        try {
            $faq = DI::getRepository()->getModelsFaq()->getOne((int) $request->faq);

            if (is_null($faq)) {
                return abort(404);
            }

            DI::getRepository()->getModelsFaq()->delete($faq);

            return redirect()->back()->with(['success' => 'Вопрос успешно удален']);
        } catch (\Exception $exception) {
            return redirect()->back()->withErrors([
                'Произошла непредвиденная ошибка. Ошибка: ' . $exception->getMessage()
            ]);
        }
    }
}
