<?php declare(strict_types=1);

namespace App\Http\Controllers\Backend;

use App\DI;
use App\Models\Project\Filters\PagerParams;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

/**
 * Контроллер для главной страницы Админки
 */
class IndexController extends BaseController
{
    /**
     * Отображение главной страницы
     *
     * @param  Request                         $request
     * @return \Illuminate\Contracts\View\View
     */
    public function __invoke(Request $request): View
    {
        $params = PagerParams::createFromRequest($request);
        $params->setLimit(5);

        return view('backend.index', [
            'rate'               => DI::getRepository()->getRates()->findByDate(Carbon::now()->format('Y-m-d')),
            'users'              => DI::getRepository()->getUser()->getLatest(5),
            'usersCount'         => DI::getRepository()->getUser()->getCountByPeriod(),
            'usersCountLastWeek' => DI::getRepository()->getUser()->getCountByPeriod(Carbon::now()->subWeek()),
            'projects'           => DI::getRepository()->getProject()->getWithPaginate($params),
            'projectsCount'      => DI::getRepository()->getProject()->getCountStats(),
        ]);
    }
}
