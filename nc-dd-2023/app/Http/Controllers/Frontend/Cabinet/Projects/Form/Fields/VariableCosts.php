<?php declare(strict_types=1);

namespace App\Http\Controllers\Frontend\Cabinet\Projects\Form\Fields;

use App\Models\Project;
use App\Models\Project\VariableCosts as VariableCostsEntity;
use Illuminate\Http\Request;

class VariableCosts
{
    /**
     * Имя поля для отображения в форме
     */
    const FIELD_NAME = 'variableCosts';

    /**
     * Тип поля
     */
    const FIELD_TYPE = 'variableCosts';

    /**
     * Максимальная длина int поля
     */
    const MAX_INTEGER_COLUMN = 2147483647;

    /**
     * Правила валидации полей
     */
    const FIELD_RULES = [
        'defaultInput.id.value'       => 'sometimes|nullable|integer',
        'defaultInput.vs_id.value'    => 'required_if:variableCosts.value,true|integer|max:' . self::MAX_INTEGER_COLUMN,
        'defaultInput.costType.value' => 'required_if:variableCosts.value,true|max:255',
        'defaultInput.price.value'    => 'required_if:variableCosts.value,true|integer|max:' . self::MAX_INTEGER_COLUMN,
    ];

    /**
     * Список полей с вопросами
     */
    const FIELD_INPUTS = [];

    /**
     * Поля по умолчанию
     */
    const DEFAULT_INPUT = [
        'id'       => [
            'value' => null,
        ],
        'vs_id'    => [
            'type'        => 'select',
            'title'       => 'Наименование продукции',
            'placeholder' => null,
            'value'       => null,
            'values'      => [],
            'column'      => 'col-12 col-md-4',
        ],
        'costType' => [
            'type'        => 'text',
            'title'       => 'Вид затрат',
            'placeholder' => null,
            'value'       => null,
            'column'      => 'col-12 col-md-4',
        ],
        'price'    => [
            'type'        => 'text',
            'title'       => 'Стоимость затрат',
            'placeholder' => null,
            'value'       => '',
            'column'      => 'col-12 col-md-4',
        ],
    ];


    /**
     * @param Project $project
     * @return array
     */
    public static function getFilledFields(Project $project)
    {
        $volumeSales = $project->volumeSales;
        $variableCosts = $project->variableCosts;

        if ($volumeSales->isNotEmpty()) {
            $records = [];

            foreach ($volumeSales as $item) {
                $records[$item->id] = [
                    'title' => $item->name,
                    'price' => $item->price,
                    'items' => []
                ];
            }

            if ($variableCosts->isNotEmpty()) {
                foreach ($variableCosts as $item) {
                    $records[$item->vs_id]['items'][] = [
                        'id' => $item->id,
                        'vs_id' => $item->vs_id,
                        'costType' => $item->costType,
                        'price' => $item->price,
                    ];
                }
            }

            return $records;
        }

        return self::FIELD_INPUTS;
    }

    /**
     * Сохраняет форму переменных расходов
     *
     * @param  \Illuminate\Http\Request $request
     * @param  \App\Models\Project      $project
     * @return bool
     */
    public static function saveForm(Request $request, Project $project): bool
    {
        if ($request->has('variableCosts')) {
            $fields = $request->validate([
                'variableCosts.value' => 'required|boolean'
            ], __('project/validation'));

            $isUnitCost = (bool) $fields['variableCosts']['value'];

            $project->setCostType($isUnitCost)->save();

            return $isUnitCost;
        }

        return false;
    }

    /**
     * Сохраняет элемент в "Переменные расходы (себестоимость)"
     *
     * @param Request $request
     * @param Project $project
     * @return mixed
     */
    public static function saveVariableCostsItem(Request $request, Project $project)
    {
        $fields = $request->validate(self::FIELD_RULES, __('project/validation'));
        $id     = (int) $fields['defaultInput']['id']['value'] ?? 0;

        unset($fields['defaultInput']['id']);

        $fields = collect($fields['defaultInput'])->map(function ($value, $fieldName) {
            return $value['value'];
        })->toArray();

        if ($id === 0) {
            $project->variableCosts()->save(new VariableCostsEntity($fields));

            return self::getFilledFields($project);
        }

        VariableCostsEntity::findOrFail($id)->fill($fields)->save();

        return self::getFilledFields($project);
    }

    /**
     * Удаляет элемент из "Переменные расходы (себестоимость)"
     *
     * @param  Request $request
     * @param  Project $project
     * @return bool|mixed|null
     * @throws \Exception
     */
    public static function deleteVariableCostsItem(Request $request, Project $project)
    {
        $variableCostsItem = $project->variableCosts()->findOrFail((int) $request->id);

        return $variableCostsItem->delete();
    }
}
