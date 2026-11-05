<?php declare(strict_types=1);

namespace App\Http\Controllers\Backend\Modules\Pages;

use App\Http\Controllers\Backend\BaseController;
use App\Models\Pages;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class IndexController extends BaseController
{

    /**
     * @var Request
     */
    private $request;

    /**
     * IndexController constructor.
     * @param Pages $pages
     * @param Request $request
     */
    public function __construct(Pages $pages, Request $request)
    {
        parent::__construct();

        $this->request = $request;

        $this->pages = $this->getPages($pages->newQuery());
    }

    public function __invoke()
    {
        return view('backend.modules.pages.index', [
            'pages' => $this->pages->paginate(30)
        ]);
    }


    /**
     * Get users by filtered
     *
     * @param $pages
     * @return Builder
     */
    protected function getPages($pages): Builder
    {
        if ($this->request->input('name')) {
            $pages = $pages->where('name', 'LIKE', '%' . $this->request->input('name') . '%');
        }

        if ($this->request->input('alias')) {
            $pages = $pages->where('alias', $this->request->input('alias'));
        }

        if (in_array($this->request->input('draft'), ["0", "1"])) {
            $pages = $pages->where('draft', $this->request->input('draft'));
        }

        if (in_array($this->request->input('good'), ["0", "1"])) {
            $pages = $pages->where('good', $this->request->input('good'));
        }

        if (in_array($this->request->input('deleted'), ["0", "1"])) {
            if ($this->request->input('deleted') === "1") {
                $pages = $pages->whereNull("deleted_at");
            } else {
                $pages = $pages->whereNotNull("deleted_at");
            }
        }
        return $pages;
    }
}
