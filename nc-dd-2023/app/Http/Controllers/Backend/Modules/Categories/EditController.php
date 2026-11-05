<?php declare(strict_types=1);

namespace App\Http\Controllers\Backend\Modules\Categories;

use App\Http\Controllers\Backend\BaseController;
use App\Models\Categories;
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
        $category = Categories::findOrFail((int)$request->category);

        return view('backend.modules.categories.edit', compact('category'));
    }
}
