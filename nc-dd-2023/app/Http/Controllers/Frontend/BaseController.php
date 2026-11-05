<?php declare(strict_types=1);

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\BaseController as Base;
use App\Models\Seo;
use Illuminate\Support\Facades\View;

class BaseController extends Base
{
    /**
     * Frontend BaseController constructor.
     */
    public function __construct()
    {
        parent::__construct();

        View::share('seo', Seo::first());
    }
}
