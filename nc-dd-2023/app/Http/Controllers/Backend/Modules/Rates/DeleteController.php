<?php declare(strict_types=1);

namespace App\Http\Controllers\Backend\Modules\Rates;

use App\Http\Controllers\Backend\BaseController;
use App\Models\Rates;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class DeleteController extends BaseController
{
    /**
     * @param int $rate
     * @return RedirectResponse
     */
    public function __invoke(int $rate): RedirectResponse
    {
        try {
            $rate = Rates::findOrFail((int)$rate);

            $rate->delete();

            return redirect()->back()->with(['success' => 'Курс успешно удален']);
        } catch (\Exception $exception) {
            return redirect()->back()->withErrors(['Произошла непредвиденная ошибка. Ошибка: ' . $exception->getMessage()]);
        }
    }
}
