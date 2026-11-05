<?php declare(strict_types=1);

namespace App\Http\Controllers\Frontend\Cabinet\Projects\Form;

use App\DI;
use App\Http\Controllers\Frontend\BaseController;
use App\Http\Controllers\Frontend\Cabinet\Projects\Form\Fields\EquipmentList;
use App\Http\Controllers\Frontend\Cabinet\Projects\Form\Fields\OverhaulList;
use App\Http\Controllers\Frontend\Cabinet\Projects\Form\Fields\ReadyPremisesList;
use App\Http\Controllers\Frontend\Cabinet\Projects\Form\Fields\SalaryFund;
use App\Http\Controllers\Frontend\Cabinet\Projects\Form\Fields\SmrList;
use App\Http\Controllers\Frontend\Cabinet\Projects\Form\Fields\VariableCosts;
use App\Http\Controllers\Frontend\Cabinet\Projects\Form\Fields\VolumeSales;
use App\Models\Project;
use Illuminate\Http\Request;

class FormController extends BaseController
{
    /**
     * @var Request
     */
    private $request;

    /**
     * @var ProjectForm
     */
    private $projectForm;

    /**
     * @var Project
     */
    private $project;

    /**
     * FormController constructor.
     *
     * @param Request $request
     * @param ProjectForm $projectForm
     */
    public function __construct(Request $request, ProjectForm $projectForm)
    {
        parent::__construct();

        if ($request->uuid) {
            $this->project     = Project::findOrFail((string) $request->uuid);
            $this->request     = $request;
            $this->projectForm = $projectForm;
        }
    }

    /**
     * Получает поля для конкретного шага
     *
     * @return array
     */
    public function getFormByStep(): array
    {
        // TODO: Временное решение, подумать как лучше организовать
        if (!$this->project->isPossibleToCorrect()) {
            return [];
        }

        $step = (int) $this->request->step ?? 1;

        // TODO: Понаблюдать, чтоб нигде не аукнулось
        if ($this->project->getStep() !== $step) {
            $this->project->setStep($step);
            $this->project->save();
        }

        return $this->projectForm->getElementsByStep($this->project, $step);
    }

    /**
     * Получить общее кол-во шагов
     *
     * @return int
     */
    public function getCountSteps(): int
    {
        return $this->projectForm->getCountSteps();
    }

    /**
     * Получить номер текущего шага
     *
     * @return int
     */
    public function getCurrentStep(): int
    {
        // Если проект завершенный и если есть возможность редактировать то,
        // перекидываем проект на первый шаг и ставим статус edited
        if ($this->project->isCompleted() && $this->project->isPossibleToCorrect()) {
            $this->project->setStep();
            $this->project->setStatus(Project::STATUS_EDITED);
            $this->project->save();
        }

        return $this->project->getStep();
    }

    /**
     * Сохраняет элемент в "Перечень приобретаемого оборудования"
     */
    public function saveEquipmentItem()
    {
        return EquipmentList::saveEquipmentItem($this->request, $this->project);
    }

    /**
     * Удаляет элемент из "Перечень приобретаемого оборудования"
     *
     * @return bool|mixed|null
     * @throws \Exception
     */
    public function deleteEquipmentItem()
    {
        return EquipmentList::deleteEquipmentItem($this->request, $this->project);
    }

    /**
     * Сохраняет элемент в "Фонд заработной платы"
     */
    public function saveSalaryFundItem()
    {
        return SalaryFund::saveSalaryFundItem($this->request, $this->project);
    }

    /**
     * Удаляет элемент из "Фонд заработной платы"
     *
     * @return bool|mixed|null
     * @throws \Exception
     */
    public function deleteSalaryFundItem()
    {
        return SalaryFund::deleteSalaryFundItem($this->request, $this->project);
    }

    /**
     * Сохраняет элемент в "Планируемый объем продаж в месяц"
     */
    public function saveVolumeSalesItem()
    {
        return VolumeSales::saveVolumeSalesItem($this->request, $this->project);
    }

    /**
     * Удаляет элемент из "Планируемый объем продаж в месяц"
     *
     * @return bool|mixed|null
     * @throws \Exception
     */
    public function deleteVolumeSalesItem()
    {
        return VolumeSales::deleteVolumeSalesItem($this->request, $this->project);
    }

    /**
     * Сохраняет элемент в "Переменные расходы (себестоимость)"
     */
    public function saveVariableCostsItem()
    {
        return VariableCosts::saveVariableCostsItem($this->request, $this->project);
    }

    /**
     * Удаляет элемент из "Переменные расходы (себестоимость)"
     * @return bool|mixed|null
     * @throws \Exception
     */
    public function deleteVariableCostsItem()
    {
        return VariableCosts::deleteVariableCostsItem($this->request, $this->project);
    }

    /**
     * Сохраняет элемент в "Перечень приобретаемой недвижимости"
     */
    public function saveReadyPremisesItem()
    {
        return ReadyPremisesList::saveReadyPremisesItem($this->request, $this->project);
    }

    /**
     * Удаляет элемент из "Перечень приобретаемой недвижимости"
     *
     * @return bool|mixed|null
     * @throws \Exception
     */
    public function deleteReadyPremisesItem()
    {
        return ReadyPremisesList::deleteReadyPremisesItem($this->request, $this->project);
    }

    /**
     * Сохраняет элемент в "Перечень по СМР"
     */
    public function saveSmrItem()
    {
        return SmrList::saveSmrItem($this->request, $this->project);
    }

    /**
     * Удаляет элемент из "Перечень по СМР"
     *
     * @return bool|mixed|null
     * @throws \Exception
     */
    public function deleteSmrItem()
    {
        return SmrList::deleteSmrItem($this->request, $this->project);
    }

    /**
     * Сохраняет элемент в "Перечень по Капитальному ремонту"
     */
    public function saveOverhaulItem()
    {
        return OverhaulList::saveOverhaulItem($this->request, $this->project);
    }

    /**
     * Удаляет элемент из "Перечень по Капитальному ремонту"
     *
     * @return bool|mixed|null
     * @throws \Exception
     */
    public function deleteOverhaulItem()
    {
        return OverhaulList::deleteOverhaulItem($this->request, $this->project);
    }
}
