<?php declare(strict_types=1);

namespace App\Http\Controllers\Frontend\Modules;

use App\Http\Controllers\Frontend\BaseController;
use App\Services\PageService;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;

class PagesController extends BaseController
{
    /**
     * @param string $slug
     * @return Application|Factory|View
     */
    public function __invoke(string $slug)
    {
        $page = (new PageService())->findBySlug($slug);

        return view('frontend.modules.pages.index', compact('page'));
    }
}
