<?php declare(strict_types=1);

namespace App\Http\Controllers\Backend\Modules\Categories;

use App\Http\Controllers\Backend\BaseController;
use App\Models\Categories;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class IndexController extends BaseController
{

    /**
     * @var Request
     */
    private $request;
    /**
     * @var Builder
     */
    private $categories;

    /**
     * IndexController constructor.
     * @param Categories $categories
     * @param Request $request
     */
    public function __construct(Categories $categories, Request $request)
    {
        parent::__construct();

        $this->request = $request;

        $this->categories = $this->getCategories($categories->newQuery());
    }

    public function __invoke()
    {
        return view('backend.modules.categories.index', [
            'categories' => $this->categories->latest()->paginate(30)
        ]);
    }


    /**
     * Get users by filtered
     *
     * @param $categories
     * @return Builder
     */
    protected function getCategories($categories): Builder
    {
        if ($this->request->input('name')) {
            $categories = $categories->where('name', 'LIKE', '%' . $this->request->input('name') . '%');
        }

        if ($this->request->input('slug')) {
            $categories = $categories->where('slug', $this->request->input('slug'));
        }

        if (in_array($this->request->input('draft'), ["0", "1"])) {
            $categories = $categories->where('draft', $this->request->input('draft'));
        }

        if (in_array($this->request->input('good'), ["0", "1"])) {
            $categories = $categories->where('good', $this->request->input('good'));
        }

        if (in_array($this->request->input('deleted'), ["0", "1"])) {
            if ($this->request->input('deleted') === "1") {
                $categories = $categories->whereNull("deleted_at");
            } else {
                $categories = $categories->whereNotNull("deleted_at");
            }
        }
        return $categories;
    }
}
