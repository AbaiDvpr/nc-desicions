<?php declare(strict_types=1);

namespace App\Http\Controllers\Backend\Modules\Rates;

use App\Http\Requests\Backend\Rates\StoreRequest;
use App\Http\Controllers\Backend\BaseController;
use App\Models\Rates;
use Illuminate\Http\RedirectResponse;

class StoreController extends BaseController
{
    /**
     * @param StoreRequest $request
     * @return RedirectResponse
     */
    public function __invoke(StoreRequest $request): RedirectResponse
    {
        try {
            $allRates = Rates::whereRelevant($request->relevant);
            if ($allRates->count() < 1) {
                $post = $request->only(['relevant', 'amount_USD', 'amount_EUR', 'amount_RUB']);

                $rates = Rates::create($post);

                return redirect()->route('backend.rates.index')->with(['success' => 'Курс успешно добавлен']);
            }else{
                return redirect()->back()->withErrors(['Курс на выбранную дату уже получен']);
            }
        } catch (\Exception $exception) {
            return redirect()->back()->withErrors(['Произошла непредвиденная ошибка. Ошибка: ' . $exception->getMessage()]);
        }
    }
}
