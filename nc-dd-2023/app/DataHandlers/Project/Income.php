<?php declare(strict_types=1);

namespace App\DataHandlers\Project;

use App\DI;

/**
 * Обработчик данных связанных с доходами
 */
class Income
{
    /**
     * Возвращает массив элементов ФОТ
     *
     * @example [id => name]
     *
     * @param  string $uuid
     * @return array
     */
    public function getNames(string $uuid): array
    {
        $volumeSales = DI::getRepository()->getProject()->getVolumeSales($uuid)->getAll();

        if (is_null($volumeSales)) {
            return [];
        }

        $names = $volumeSales->pluck('name', 'id')->toArray();

        return collect($names)->map(function ($name) {
            return "     " . $name;
        })->toArray();
    }
}
