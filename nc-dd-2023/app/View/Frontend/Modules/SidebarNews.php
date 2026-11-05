<?php declare(strict_types=1);
namespace App\View\Frontend\Modules;

use App\DI;
use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

/**
 * Компонент списка последних новостей
 */
class SidebarNews extends Component
{
    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View|Closure|string
    {
        return view('components.modules.sidebar-news', [
            'news' => DI::getRepository()->getModelsNews()->getLatest()
        ]);
    }
}
