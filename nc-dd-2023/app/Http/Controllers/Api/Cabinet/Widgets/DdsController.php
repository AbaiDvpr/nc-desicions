<?php declare(strict_types=1);

namespace App\Http\Controllers\Api\Cabinet\Widgets;

use App\DI;
use App\Http\Controllers\Api\BaseController;
use App\Models\Company\Filters\ReportParams;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Контроллер для работы с виджетами ДДС
 */
class DdsController extends BaseController
{
    /**
     * Возвращает денежные средства на конец периода для графика donut
     *
     * @param  \Illuminate\Http\Request     $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getCashByEndPeriodDonutAction(Request $request): JsonResponse
    {
        $params = ReportParams::createFromRequest($request);
        $fact   = DI::getRepository()->getCompany()->getDdsReport()->getFromParams($params);

        return response()->json([
            'amount' => round((float) collect($fact)->sum('cashAtEndPeriod'))
        ]);
    }

    /**
     * Возвращает денежные средства на конец периода для графика area
     *
     * @param  \Illuminate\Http\Request     $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getCashByEndPeriodAreaAction(Request $request)
    {
        $params = ReportParams::createFromRequest($request);
        $fact   = DI::getRepository()->getCompany()->getDdsReport()->getFromParams($params);
        $config = [
            'cashAtEndPeriod' => config('company.widgets.cashAtEndPeriod'),
        ];
        $data   = DI::getDH()->getWidget()->getData(
            $fact,
            $config,
            $params->getPeriodFrom(),
            $params->getPeriodTo()
        );

        return response()->json($data['cashAtEndPeriod'] ?? []);
    }
}
