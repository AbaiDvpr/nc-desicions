<?php declare(strict_types=1);

namespace App\Http\Controllers\Backend\Companies;

use App\DI;
use App\Http\Controllers\Backend\BaseController;
use App\Models\Company\Filters\PagerParams;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Контроллер для работы с компаниями
 */
class CompanyController extends BaseController
{
    /**
     * Возвращает список компаний
     *
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\View\View
     */
    public function index(Request $request): View
    {
        $params = PagerParams::createFromRequest($request);
        $params->setLimit(30);

        $companies     = DI::getRepository()->getCompany()->getWithPaginate($params);
        $tariffOptions = DI::getRepository()->getModelsTariff()->getForFilter();

        return view('backend.companies.company.index', [
            'companies'     => $companies,
            'currency'      => config('form-data.currencies'),
            'tariffOptions' => collect($tariffOptions)->prepend('Все тарифы')->toArray(),
        ]);
    }
}
