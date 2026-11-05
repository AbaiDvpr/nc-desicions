<?php declare(strict_types=1);

namespace App\Http\Controllers\Backend\Modules\Faq;

use App\Http\Controllers\Backend\BaseController;
use App\Models\Faq;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class IndexController extends BaseController
{
    /**
     * Страница со списком вопросов
     *
     * @return \Illuminate\Contracts\View\View
     */
    public function __invoke(Request $request)
    {
        $faq = $this->getFaq(new Faq(), $request);

        return view('backend.modules.faq.index', [
            'faq' => $faq->paginate(30)
        ]);
    }

    /**
     * Получение всех вопросов с возможностью фильтрации
     *
     * @param  \App\Models\Faq          $faq
     * @param  \Illuminate\Http\Request $request
     * @return Builder
     */
    protected function getFaq(Faq $faq, Request $request): Builder
    {
        if ($request->input('email')) {
            $faq = $faq->where('email', 'LIKE', '%' . $request->input('email') . '%');
        }

        if ($request->input('lang')) {
            $faq = $faq->where('lang', $request->input('lang'));
        }

        if (in_array($request->input('main'), ["0", "1"])) {
            $faq = $faq->where('main', $request->input('main'));
        }

        if (in_array($request->input('published'), ["0", "1"])) {
            $faq = $faq->where('published', $request->input('published'));
        }

        return $faq->latest();
    }
}
