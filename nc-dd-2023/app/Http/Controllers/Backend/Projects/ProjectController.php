<?php declare(strict_types=1);

namespace App\Http\Controllers\Backend\Projects;

use App\DI;
use App\Http\Controllers\Backend\BaseController;
use Illuminate\Http\Request;
use Illuminate\View\View;
use App\Models\Project\Filters\PagerParams;

/**
 * Контроллер отвечающий за работу с проектами
 */
class ProjectController extends BaseController
{
    /**
     * Возвращает список проектов
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\View\View
     */
    public function index(Request $request): View
    {
        $params = PagerParams::createFromRequest($request);
        $params->setLimit(30);
        $projects      = DI::getRepository()->getProject()->getWithPaginate($params);
        $tariffOptions = DI::getRepository()->getModelsTariff()->getForFilter();

        return view('backend.projects.project.index', [
            'projects'      => $projects,
            'tariffOptions' => collect($tariffOptions)->prepend('Все тарифы')->toArray(),
            'tax_regime'    => config('projects.tax_regime'),
            'statuses'      => config('form-data.projectStatuses'),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param Request $request
     * @param $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //
    }

}
