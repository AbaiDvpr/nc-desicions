<?php declare(strict_types=1);

namespace App\Components\Report\Helpers;

use App\Models\Project;

class BaseHelper
{

    /**
     * Проект
     *
     * @var \App\Models\Project
     */
    protected $project;

    /**
     *
     * @param \App\Models\Project $project
     */
    public function __construct(Project $project)
    {
        $this->project = $project;
    }
}
