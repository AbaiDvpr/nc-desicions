<?php declare(strict_types=1);

namespace App\Repositories;

use App\Repositories\Courses\Lesson;
use App\Repositories\Courses\Module;

/**
 * Репозиторий для работы с курсами по обучению
 */
class Courses extends BaseRepository
{

    /**
     * Репозиторий для работы с модулями курсов
     *
     * @var \App\Repositories\Courses\Module
     */
    protected $module;

    /**
     * Репозиторий для работы с уроками курсов
     *
     * @var \App\Repositories\Courses\Lesson
     */
    protected $lesson;

    /**
     * Репозиторий для работы с модулями курсов
     *
     * @return \App\Repositories\Courses\Module
     */
    public function getModule(): Module
    {
        if (!$this->module) {
            $this->module = new Module();
        }

        return $this->module;
    }

    /**
     * Репозиторий для работы с уроками курсов
     *
     * @return \App\Repositories\Courses\Lesson
     */
    public function getLesson(): Lesson
    {
        if (!$this->lesson) {
            $this->lesson = new Lesson();
        }

        return $this->lesson;
    }
}
