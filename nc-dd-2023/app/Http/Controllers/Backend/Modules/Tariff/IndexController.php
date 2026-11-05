<?php declare(strict_types=1);

namespace App\Http\Controllers\Backend\Modules\Tariff;

use App\Http\Controllers\Backend\BaseController;
use App\Models\Tariff;

class IndexController extends BaseController
{
    public function __invoke()
    {
        $tariffs = Tariff::latest()->paginate(30);

        return view('backend.modules.tariff.index', compact('tariffs'));
    }
}
