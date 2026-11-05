<?php declare(strict_types=1);

namespace App\Repositories\Project\Objects;

use App\Models\Project as ProjectModel;

class Base
{
    use \App\Traits\Cache\Cacheable;

    /**
     * Проект
     *
     * @var \App\Models\Project|null
     */
    public ?ProjectModel $project;

    /**
     * Конструктор
     *
     * @param \App\Models\Project|null $project
     */
    public function __construct(?ProjectModel $project)
    {
        $this->project = $project;
    }
}
