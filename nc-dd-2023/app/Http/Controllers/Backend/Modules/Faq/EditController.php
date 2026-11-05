<?php declare(strict_types=1);

namespace App\Http\Controllers\Backend\Modules\Faq;

use App\DI;
use App\Http\Controllers\Backend\BaseController;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class EditController extends BaseController
{
    /**
     * Страница редактирования вопроса
     *
     * @param  Request                         $request
     * @return \Illuminate\Contracts\View\View
     */
    public function __invoke(Request $request): View
    {
        $faq = DI::getRepository()->getModelsFaq()->getOne((int) $request->faq);

        if (is_null($faq)) {
            return abort(404);
        }

        return view('backend.modules.faq.edit', compact('faq'));
    }
}
