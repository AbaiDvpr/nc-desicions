<?php declare(strict_types=1);

namespace App\Http\Controllers\Backend\Modules\Rates;

use App\Http\Controllers\Backend\BaseController;
use App\Http\Requests\Backend\Rates\UpdateRequest;
use App\Models\Rates;
use Illuminate\Http\RedirectResponse;

class UpdateController extends BaseController
{
    /**
     * @param UpdateRequest $request
     * @param int $rate
     * @return RedirectResponse
     */
    public function __invoke(UpdateRequest $request, int $rate): RedirectResponse
    {
        try {
            $post = $request->only(['amount_USD', 'amount_EUR', 'amount_RUB']);
            $rate = Rates::findOrFail((int)$rate);

            $rate->update($post);

            return redirect()->route('backend.rates.index')->with(['success' => 'Курс успешно изменен']);
        } catch (\Exception $exception) {
            return redirect()->back()->withErrors([
                'Произошла непредвиденная ошибка. Ошибка: ' . $exception->getMessage()
            ]);
        }
    }
}
