<?php

namespace App\Http\Controllers\Backend\Modules\Rates;

use App\Http\Controllers\Backend\BaseController;
use App\Models\Rates;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;

class IndexController extends BaseController
{

    /**
     * @return Factory|View
     */
    public function __invoke()
    {
        $rates = Rates::latest();

        return view('backend.modules.rates.index', [
            'rates' => $rates->paginate(30)
        ]);
    }
}
