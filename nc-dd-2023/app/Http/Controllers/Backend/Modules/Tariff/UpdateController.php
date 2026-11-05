<?php declare(strict_types=1);

namespace App\Http\Controllers\Backend\Modules\Tariff;

use App\Http\Controllers\Backend\BaseController;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use App\DI;

class UpdateController extends BaseController
{
    /**
     * Обновление тарифного плана
     *
     * @param  Request $request
     * @return RedirectResponse
     */
    public function __invoke(Request $request): RedirectResponse
    {
        try {
            $tariff = DI::getRepository()->getModelsTariff()->getOne((int) $request->tariff);

            if (is_null($tariff)) {
                return abort(404);
            }

            $post = Arr::collapse([
                [
                    'updated_id' => $this->user->id,
                    'good'       => false,
                    'default'    => false,
                ],
                $request->only([
                    'good', 'default', 'type', 'name', 'description', 'price', 'correction', 'maxProjects', 'company',
                ]),
            ]);

            DI::getRepository()->getModelsTariff()->update((int) $request->tariff, $post);

            return redirect()->route('backend.modules.tariff.index')
                ->with(['success' => 'Тариф сохранен.']);
        } catch (\Exception $exception) {
            return redirect()->back()->withErrors([
                'Произошла непредвиденная ошибка. Ошибка: ' . $exception->getMessage()
            ]);
        }
    }
}
