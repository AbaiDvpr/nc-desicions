<?php declare(strict_types=1);

namespace App\View\Frontend\Components;

use Illuminate\Http\Request;
use Illuminate\View\Component;

class NavHeader extends Component
{
    /**
     * @var
     */
    public $class;

    /**
     * NavHeader constructor.
     *
     * @param string $class
     */
    public function __construct(string $class = '')
    {
        $this->class = $class;
    }

    /**
     * Get the view / contents that represent the component.
     *
     * @return \Illuminate\Contracts\View\View|string
     */
    public function render()
    {
        $user = auth()->user();

        return view('frontend.components.nav-header', [
            'class'   => $this->class,
            'user'    => $user ?? null,
            'cabinet' => $user ? $user->getCabinetId() : null,
        ]);
    }
}
