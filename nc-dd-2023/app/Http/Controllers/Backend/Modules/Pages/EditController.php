<?php declare(strict_types=1);

namespace App\Http\Controllers\Backend\Modules\Pages;

use App\Http\Controllers\Backend\BaseController;
use App\Models\Pages;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class EditController extends BaseController
{
    /**
     * @param Request $request
     * @return Factory|View
     */
    public function __invoke(Request $request)
    {
        $page = Pages::findOrFail((int)$request->page);

        return view('backend.modules.pages.edit', compact('page'));
    }
}
