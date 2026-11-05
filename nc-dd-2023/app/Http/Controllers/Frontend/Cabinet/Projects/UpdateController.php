<?php declare(strict_types=1);

namespace App\Http\Controllers\Frontend\Cabinet\Projects;

use App\Components\Report\Report;
use App\DI;
use App\Http\Controllers\Frontend\BaseController;
use App\Http\Controllers\Frontend\Cabinet\Projects\Form\Fields\AdministrativeExpenses;
use App\Http\Controllers\Frontend\Cabinet\Projects\Form\Fields\AttractingInvestments;
use App\Http\Controllers\Frontend\Cabinet\Projects\Form\Fields\EquipmentList;
use App\Http\Controllers\Frontend\Cabinet\Projects\Form\Fields\InvestmentAmount as InvestmentAmountForm;
use App\Http\Controllers\Frontend\Cabinet\Projects\Form\Fields\InvestPanel;
use App\Http\Controllers\Frontend\Cabinet\Projects\Form\Fields\MarketingExpenses;
use App\Http\Controllers\Frontend\Cabinet\Projects\Form\Fields\OverhaulList;
use App\Http\Controllers\Frontend\Cabinet\Projects\Form\Fields\OwnInvestment as OwnInvestmentForm;
use App\Http\Controllers\Frontend\Cabinet\Projects\Form\Fields\PlannedCapacity;
use App\Http\Controllers\Frontend\Cabinet\Projects\Form\Fields\ReadyPremisesList;
use App\Http\Controllers\Frontend\Cabinet\Projects\Form\Fields\SalaryFund;
use App\Http\Controllers\Frontend\Cabinet\Projects\Form\Fields\SmrList;
use App\Http\Controllers\Frontend\Cabinet\Projects\Form\Fields\Turnover;
use App\Http\Controllers\Frontend\Cabinet\Projects\Form\Fields\VariableCosts;
use App\Http\Controllers\Frontend\Cabinet\Projects\Form\Fields\VariableCostsOther;
use App\Http\Controllers\Frontend\Cabinet\Projects\Form\Fields\VolumeSales;
use App\Http\Controllers\Frontend\Cabinet\Projects\Form\ProjectForm;
use App\Models\Project;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use PhpOffice\PhpSpreadsheet\Exception;
use Throwable;

class UpdateController extends BaseController
{
    /**
     * Обновление данных проекта
     *
     * @param  \Illuminate\Http\Request                  $request
     * @return  array
     * @throws \Illuminate\Validation\ValidationException
     */
    public function __invoke(Request $request): array
    {
        $step  = (int) $request->step;
        $rules = (new ProjectForm())->getValidateRules($step);
        $form  = $this->validate($request, $rules, __('project/validation'));

        unset($form[InvestmentAmountForm::FIELD_NAME]);
        unset($form[AttractingInvestments::FIELD_NAME]);
        unset($form[AdministrativeExpenses::FIELD_NAME]);
        unset($form[MarketingExpenses::FIELD_NAME]);
        unset($form[VolumeSales::FIELD_NAME]);
        unset($form[EquipmentList::FIELD_NAME]);
        unset($form[SalaryFund::FIELD_NAME]);
        unset($form[ReadyPremisesList::FIELD_NAME]);
        unset($form[SmrList::FIELD_NAME]);
        unset($form[OverhaulList::FIELD_NAME]);
        unset($form[VariableCosts::FIELD_NAME]);
        unset($form[InvestPanel::FIELD_NAME]);

        $project = Project::find($request->uuid);

        DI::getRepository()->getProject()->clearCache($project);

        $project->fill($this->prepareFormData($form))->save();

        PlannedCapacity::saveForm($request, $project);
        Turnover::saveForm($request, $project);
        OwnInvestmentForm::saveForm($request, $project);
        InvestmentAmountForm::saveForm($request, $project);
        AttractingInvestments::saveForm($request, $project);
        VariableCosts::saveForm($request, $project);
        VariableCostsOther::saveForm($request, $project);
        AdministrativeExpenses::saveForm($request, $project);
        MarketingExpenses::saveForm($request, $project);

        // увеличиваем шаг, только после того как все сохранилось
        if ((new ProjectForm())->getCountSteps() === $step) {
            DI::getRepository()->getProject()->getHistory()->increaseCorrection($project);

            $project->setStatus(Project::STATUS_COMPLETED);
            $project->save();
        }

        if ((new ProjectForm())->getCountSteps() === $step) {
            try {
                (new Report())->generate($project->getUuid());
            } catch (Throwable|Exception $exception) {
                Log::error('[Project] Ошибка при генерации и сохранении отчета', [
                    'exception' => $exception,
                ]);
            }
        }

        return [
            'success' => array_merge(
                [
                    'step'   => ++$project->step,
                    'status' => $project->status,
                ],
                array_filter([
                    'downloadLink' => DI::getRepository()->getProject()->getDownloadLink($project),
                    'message'      => $project->isCompleted() ? 'Рассчет фин. модели завершен' : null
                ])
            )
        ];
    }

    /**
     * @param  array $validated
     * @return array
     */
    protected function prepareFormData(array $validated = []): array
    {
        return collect($validated)->map(function ($item, $key) {
            $value = $item['values'] ?? $item['value'];

            if (in_array($key, ['salesStartDate', 'plannedVolume'])) {
                $value = Carbon::parse($item['value'])->setTimezone(config('app.timezone'))->format('Y-m-d');
            }

            if ($key === 'planning') {
                return [
                    'from' => Carbon::parse($item['values']['from'])->setTimezone(config('app.timezone'))->format('Y-m-d'),
                    'to'   => Carbon::parse($item['values']['to'])->setTimezone(config('app.timezone'))->format('Y-m-d')
                ];
            }

            return $value;
        })->toArray();
    }
}
