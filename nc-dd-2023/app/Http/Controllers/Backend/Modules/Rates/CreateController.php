<?php declare(strict_types=1);

namespace App\Http\Controllers\Backend\Modules\Rates;

use App\Http\Controllers\Backend\BaseController;
use Carbon\Carbon;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Spatie\Permission\Models\Role;

class CreateController extends BaseController
{
    /**
     * @return Factory|View
     */
    public function __invoke()
    {
        $date = Carbon::now()->addDay()->format('Y-m-d');
        return view('backend.modules.rates.create', [
            'date' => $date
        ]);
    }
}
