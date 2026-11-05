<?php declare(strict_types=1);

namespace App\Http\Controllers\Backend\Modules\Tariff;

use App\DI;
use App\Http\Controllers\Backend\BaseController;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use App\Models\Tariff;

class DeleteController extends BaseController
{
    /**
     * @param Request $request
     * @return RedirectResponse
     */
    public function __invoke(Request $request): RedirectResponse
    {
        try {
            $tariff = DI::getRepository()->getModelsTariff()->getOne((int) $request->tariff);

            if (is_null($tariff)) {
                return abort(404);
            }

            DI::getRepository()->getModelsTariff()->delete($tariff);

            return redirect()->back()->with(['success' => 'Тариф успешно удален']);
        } catch (\Exception $exception) {
            return redirect()->back()->withErrors(['Произошла непредвиденная ошибка. Ошибка: ' . $exception->getMessage()]);
        }
    }
}
