<?php declare(strict_types=1);

namespace App\Http\Controllers\Backend\Modules\Rates;

use App\Http\Controllers\Backend\BaseController;
use App\Models\Rates;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class EditController extends BaseController
{
    /**
     * @param int $rate
     * @return Factory|View
     */
    public function __invoke(int $rate)
    {
        $rate = Rates::findOrFail((int)$rate);

        return view('backend.modules.rates.edit', ['rate' => $rate]);
    }
}
