<?php declare(strict_types=1);

namespace App\Http\Controllers\Frontend\Cabinet;

use App\DI;
use App\Http\Controllers\Frontend\BaseController;
use App\Http\Requests\Frontend\Cabinet\Company\GetOneRequest;
use App\Http\Requests\Frontend\Cabinet\Company\ImportRequest;
use App\Http\Requests\Frontend\Cabinet\Company\Indicators\DeleteRequest as IndicatorDeleteRequest;
use App\Http\Requests\Frontend\Cabinet\Company\Indicators\StoreRequest as IndicatorStoreRequest;
use App\Http\Requests\Frontend\Cabinet\Company\Indicators\UpdateRequest as IndicatorUpdateRequest;
use App\Http\Requests\Frontend\Cabinet\Company\SaveRequest;
use App\Models\Company\Filters\PagerParams;
use App\Models\Company\Filters\ReportParams;
use App\Models\Company\Forms\Company as CompanyForm;
use App\Models\Company\Forms\Indicator as IndicatorForm;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Контроллер для работы с компаниями в личном кабинете
 */
class CompanyController extends BaseController
{
    /**
     * Формирует страницу со списком компаний
     *
     * @param  \Illuminate\Http\Request      $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getListAction(Request $request): JsonResponse
    {
        $params = PagerParams::createFromRequest($request);
        $params->setUserId($this->client->getAuthIdentifier());

        return response()->json(
            DI::getRepository()->getCompany()->getFromParams($params->toArray())
        );
    }

    /**
     * Возвращает компанию
     *
     * @param  \App\Http\Requests\Frontend\Cabinet\Company\GetOneRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getOneAction(GetOneRequest $request): JsonResponse
    {
        return response()->json(
            DI::getRepository()->getCompany()->getByUuid((string) $request->input('uuid'))
        );
    }

    /**
     * Создает новую или обновляет существующую компанию
     *
     * @param  \App\Http\Requests\Frontend\Cabinet\Company\SaveRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function saveAction(SaveRequest $request): JsonResponse
    {
        $form    = CompanyForm::createFromRequest($request);
        $company = DI::getRepository()->getCompany()->save($this->client, $form);

        if (!$company) {
            return response()->json([
                'message' => 'Произошла ошибка во время сохранения, пожалуйста попробуйте позднее'
            ], 500);
        }

        return response()->json([
            'uuid' => $company->getUuid()
        ]);
    }

    /**
     * Загружает импорт файл
     *
     * @param  \App\Http\Requests\Frontend\Cabinet\Company\ImportRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function uploadImportFileAction(ImportRequest $request): JsonResponse
    {
        return response()->json([
            'id' => DI::getRepository()->getCompany()->getImport()->save($request)
        ]);
    }

    /**
     * Возвращает статус загрузки импорт файла
     *
     * @param  \Illuminate\Http\Request      $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getImportFileStatusAction(Request $request): JsonResponse
    {
        return response()->json([
            'status' => !DI::getRepository()->getCompany()->getImport()->getUncompleted($request)->count()
        ]);
    }

    /**
     * Возвращает отчет по ОПУ
     *
     * @param  \Illuminate\Http\Request      $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getOpuReportAction(Request $request): JsonResponse
    {
        $params = ReportParams::createFromRequest($request);
        $fact   = DI::getRepository()->getCompany()
            ->getOpuReport()
            ->getFromParams($params);

        return response()->json(
            DI::getDH()->getWidget()->getDataForReport(
                $fact,
                config('company.report.opu'),
                $params->getPeriodFrom(),
                $params->getPeriodTo()
            )
        );
    }

    /**
     * Возвращает план по ОПУ
     *
     * @param  \Illuminate\Http\Request      $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getOpuReportPlanAction(Request $request): JsonResponse
    {
        $params = ReportParams::createFromRequest($request);
        $plan   = DI::getRepository()->getCompany()
            ->getOpuReport()
            ->getPlan()
            ->getFromParams($params);

        return response()->json(
            DI::getDH()->getWidget()->getDataForReport(
                $plan,
                config('company.report.opu'),
                $params->getPeriodFrom(),
                $params->getPeriodTo()
            )
        );
    }

    /**
     * Возвращает отчет по ДДС
     *
     * @param  \Illuminate\Http\Request      $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getDdsReportAction(Request $request): JsonResponse
    {
        $params = ReportParams::createFromRequest($request);
        $fact   = DI::getRepository()->getCompany()
            ->getDdsReport()
            ->getFromParams($params);

        return response()->json(
            DI::getDH()->getWidget()->getDataForReport(
                $fact,
                config('company.report.dds'),
                $params->getPeriodFrom(),
                $params->getPeriodTo()
            )
        );
    }

    /**
     * Возвращает план по ДДС
     *
     * @param  \Illuminate\Http\Request      $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getDdsReportPlanAction(Request $request): JsonResponse
    {
        $params = ReportParams::createFromRequest($request);
        $plan   = DI::getRepository()->getCompany()
            ->getDdsReport()
            ->getPlan()
            ->getFromParams($params);

        return response()->json(
            DI::getDH()->getWidget()->getDataForReport(
                $plan,
                config('company.report.dds'),
                $params->getPeriodFrom(),
                $params->getPeriodTo()
            )
        );
    }

    /**
     * Возвращает отчет по Балансу
     *
     * @param  \Illuminate\Http\Request      $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getBalanceReportAction(Request $request): JsonResponse
    {
        $params = ReportParams::createFromRequest($request);
        $fact   = DI::getRepository()->getCompany()
            ->getBalanceReport()
            ->getFromParams($params);

        return response()->json(
            DI::getDH()->getWidget()->getDataForReport(
                $fact,
                config('company.report.balance'),
                $params->getPeriodFrom(),
                $params->getPeriodTo()
            )
        );
    }

    /**
     * Возвращает план по Балансу
     *
     * @param  \Illuminate\Http\Request      $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getBalanceReportPlanAction(Request $request): JsonResponse
    {
        $params = ReportParams::createFromRequest($request);
        $plan   = DI::getRepository()->getCompany()
            ->getBalanceReport()
            ->getPlan()
            ->getFromParams($params);

        return response()->json(
            DI::getDH()->getWidget()->getDataForReport(
                $plan,
                config('company.report.balance'),
                $params->getPeriodFrom(),
                $params->getPeriodTo()
            )
        );
    }

    /**
     * Возвращает список показателей
     *
     * @param  \Illuminate\Http\Request      $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getIndicatorsAction(Request $request): JsonResponse
    {
        $indicators = DI::getRepository()->getCompany()->getIndicators()->getList(
            $request->input('companyUuid')
        );

        return response()->json(
            collect($indicators)->map(function ($indicator) {
                /** @var \App\Models\Company\Indicator $indicator */
                return $indicator->getPresenter()->getPublicData();
            })
        );
    }

    /**
     * Создает показатель
     *
     * @param  \App\Http\Requests\Frontend\Cabinet\Company\Indicators\StoreRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function createIndicatorsAction(IndicatorStoreRequest $request): JsonResponse
    {
        $indicator = DI::getRepository()->getCompany()->getIndicators()->create(
            IndicatorForm::createFromRequest($request)
        );

        return response()->json($indicator->getPresenter()->getPublicData());
    }

    /**
     * Обновляет показатель
     *
     * @param  int                                                                  $id
     * @param  \App\Http\Requests\Frontend\Cabinet\Company\Indicators\UpdateRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateIndicatorsAction(int $id, IndicatorUpdateRequest $request): JsonResponse
    {
        $indicator = DI::getRepository()->getCompany()->getIndicators()->getOne($id);

        if (is_null($indicator)) {
            return response()->json([
                'error' => 'Показатель не найден'
            ], 404);
        }

        return response()->json([
            'status' => DI::getRepository()->getCompany()->getIndicators()->save(
                $indicator,
                IndicatorForm::createFromRequest($request)
            )
        ]);
    }

    /**
     * Удаляет показатель
     *
     * @param  int                                                                  $id
     * @param  \App\Http\Requests\Frontend\Cabinet\Company\Indicators\DeleteRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function deleteIndicatorsAction(int $id, IndicatorDeleteRequest $request): JsonResponse
    {
        $indicator = DI::getRepository()->getCompany()->getIndicators()->getOne($id);

        if (is_null($indicator)) {
            return response()->json([
                'error' => 'Показатель не найден'
            ], 404);
        }

        return response()->json([
            'status' => DI::getRepository()->getCompany()->getIndicators()->delete($indicator)
        ]);
    }
}
