<?php declare(strict_types=1);

namespace App\Http\Controllers\Api\Cabinet\Widgets;

use App\DI;
use App\Http\Controllers\Api\BaseController;
use App\Models\Company\Filters\ReportParams;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Контроллер для работы с виджетами ОПУ
 */
class OpuController extends BaseController
{
    /**
     * Возвращает чистую прибыль для графика donut
     *
     * @param  \Illuminate\Http\Request     $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getNetProfitDonutAction(Request $request): JsonResponse
    {
        $params = ReportParams::createFromRequest($request);
        $fact   = DI::getRepository()->getCompany()->getOpuReport()->getFromParams($params);

        if (count($fact)) {
            $data = config('company.widgets.netProfitDonut');

            collect($data)->map(function($item, $key) use (&$data, $fact) {
                $data[$key]['value'] = round((float) collect($fact)->sum($key));
            });

            return response()->json([
                'data'   => $data,
                'amount' => $data['netProfit']['value'],
            ]);
        }

        return response()->json([
            'data'   => [],
            'amount' => 0,
        ]);
    }

    /**
     * Возвращает чистую прибыль для графика area
     *
     * @param  \Illuminate\Http\Request     $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getNetProfitAreaAction(Request $request)
    {
        $params = ReportParams::createFromRequest($request);
        $fact   = DI::getRepository()->getCompany()->getOpuReport()->getFromParams($params);
        $config = [
            'netProfit' => config('company.widgets.netProfit')
        ];
        $data   = DI::getDH()->getWidget()->getData(
            $fact,
            $config,
            $params->getPeriodFrom(),
            $params->getPeriodTo()
        );

        return response()->json($data['netProfit'] ?? []);
    }
}
