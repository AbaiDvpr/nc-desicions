<?php declare(strict_types=1);

namespace App\Http\Controllers\Backend\Modules\Tariff;

use App\Http\Controllers\Backend\BaseController;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use App\DI;

class EditController extends BaseController
{
    /**
     * Страница редактирования тарифного плана
     *
     * @param  \Illuminate\Http\Request        $request
     * @return \Illuminate\Contracts\View\View
     */
    public function __invoke(Request $request): View
    {
        $tariff = DI::getRepository()->getModelsTariff()->getOne((int) $request->tariff);

        return view('backend.modules.tariff.edit', compact('tariff'));
    }
}
