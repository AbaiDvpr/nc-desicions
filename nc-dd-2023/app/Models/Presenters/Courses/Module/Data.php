<?php declare(strict_types=1);

namespace App\Models\Presenters\Courses\Module;

use App\Models\Courses\Module;

/**
 * Презентер для обработки данных модуля курсов
 */
class Data
{
    /**
     * @var \App\Models\Courses\Module
     */
    private Module $module;

    /**
     * Конструктор
     *
     * @param \App\Models\Courses\Module $module
     */
    public function __construct(Module $module)
    {
        $this->module = $module;
    }

    /**
     * Возвращает публичные данные
     *
     * @return array
     */
    public function getPublicData(): array
    {
        return [
            'id'    => $this->module->getId(),
            'title' => $this->module->getTitle(),
        ];
    }
}
