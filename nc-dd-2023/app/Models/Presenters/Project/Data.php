<?php declare(strict_types=1);

namespace App\Models\Presenters\Project;

use App\DI;
use App\Http\Controllers\Frontend\Cabinet\Projects\Form\ProjectForm;
use App\Models\Project;

/**
 * Презентер для обработки данных проекта
 */
class Data
{
    /**
     * @var \App\Models\Project
     */
    private Project $project;

    /**
     * Конструктор
     *
     * @param \App\Models\Project $project
     */
    public function __construct(Project $project)
    {
        $this->project = $project;
    }

    /**
     * Возвращает публичные данные проекта
     *
     * @return array
     */
    public function getPublicData(): array
    {
        $projectForm = new ProjectForm();

        return array_merge([
            'uuid'                => $this->project->getUuid(),
            'form'                => config('form-data.forms.' . $this->project->getForm()),
            'name'                => $this->project->getName(),
            'taxRegime'           => $projectForm->getElementValue('tax_regime', $this->project->getTaxRegime()),
            'currency'            => $projectForm->getElementValue('currency', $this->project->getCurrency()),
            'schedule'            => $projectForm->getElementValue('schedule', $this->project->getSchedule()),
            'isPossibleToCorrect' => $this->project->isPossibleToCorrect(),
            'isCompleted'         => $this->project->isCompleted(),
            'createdAt'           => $this->project->getCreatedAt()->format(DATE_ATOM),
        ],
        array_filter([
            'downloadLink' => DI::getRepository()->getProject()->getDownloadLink($this->project),
        ]));
    }
}
