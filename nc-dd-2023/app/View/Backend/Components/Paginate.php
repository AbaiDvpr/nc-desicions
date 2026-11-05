<?php declare(strict_types=1);

namespace App\View\Backend\Components;

use Illuminate\View\Component;

class Paginate extends Component
{
    /**
     * @var
     */
    public $paginate;

    /**
     * Create a new component instance.
     *
     * @param string $paginate
     */
    public function __construct($paginate = '')
    {
        $this->paginate = $paginate;
    }

    /**
     * Get the view / contents that represent the component.
     *
     * @return \Illuminate\Contracts\View\View|string
     */
    public function render()
    {
        return view('backend.components.paginate');
    }
}
