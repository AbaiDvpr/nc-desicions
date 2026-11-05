<?php declare(strict_types=1);

namespace App\Http\Controllers\Backend\Seo;

use App\Http\Controllers\Backend\BaseController;
use App\Models\Seo;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

class IndexController extends BaseController
{

    /**
     * @return Application|Factory|View|RedirectResponse
     */
    public function __invoke()
    {
        try {
            return view('backend.seo.index', [
                'seo' => Seo::first()
            ]);
        } catch (\Exception $exception) {
            return redirect()->back()
                ->withErrors(['Произошла ошибка. Обратитесь к администратору']);
        }
    }
}
