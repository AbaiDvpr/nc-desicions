<?php declare(strict_types=1);

namespace App\Http\Controllers\Frontend\Cabinet\Projects\Form\Fields;

use App\Models\Project;
use Carbon\Carbon;
use Illuminate\Http\Request;

class PlannedCapacity
{
    /**
     * Имя поля для отображения в форме
     */
    const FIELD_NAME = 'plannedCapacity';

    /**
     * Тип поля
     */
    const FIELD_TYPE = 'plannedCapacity';

    /**
     * Правила валидации полей
     */
    const FIELD_RULES = [
        'plannedCapacity.inputs.*.value' => 'sometimes|nullable|integer|max:1000',
    ];

    /**
     * Выводит поля для указания плановой мощности по годам
     *
     * @param  \App\Models\Project $project
     * @return array
     */
    public static function getInputs(Project $project): array
    {
        $planning        = $project->getPlanning();
        $plannedCapacity = $project->getPlannedCapacity();
        $fields          = [];

        for ($year = Carbon::create($planning['from'])->year; $year <= Carbon::create($planning['to'])->year; $year++) {
            $fields[$year] = [
                'type'           => 'text',
                'title'          => $year,
                'placeholder'    => null,
                'value'          => $plannedCapacity[$year] ?? '',
                'column'         => 'col-6 col-md-4 col-xl-2',
                'isValuePercent' => true,
            ];
        }

        return $fields;
    }

    /**
     * Сохраняет значения "Плановой мощности"
     *
     * @param  Request $request
     * @param  Project $project
     * @return bool
     */
    public static function saveForm(Request $request, Project $project)
    {
        if ($request->has('plannedCapacity')) {

            $fields = $request->validate(self::FIELD_RULES, __('project/validation'));

            $fields = collect($fields['plannedCapacity']['inputs'])->map(function ($value) {
                return $value['value'];
            })->whereNotNull()->toArray();

            $project->plannedCapacity = (count($fields) > 0) ? $fields : null;

            return $project->save();
        }

        return false;
    }
}
