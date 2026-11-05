<?php declare(strict_types=1);

namespace App\Http\Controllers\Frontend;

use App\DI;
use App\Models\Tariff;
use Illuminate\Contracts\View\View;

/**
 * Контроллер главной страницы
 */
class IndexController extends BaseController
{
    /**
     * Отображение главной страницы сайта
     *
     * @return \Illuminate\Contracts\View\View
     */
    public function __invoke(): View
    {
        $DI = DI::getRepository();

        return view('frontend.index', [
            'mainQuestions' => $DI->getModelsFaq()->getMainQuestions(),
            'tariffs'       => $DI->getModelsTariff()->getLastThreeActive(),
            'tariffsAnnual' => $DI->getModelsTariff()->getLastThreeActive(Tariff::TYPE_ANNUAL),
            'news'          => $DI->getModelsNews()->getPromo(),
        ]);
    }
}
