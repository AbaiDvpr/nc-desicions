<?php declare(strict_types=1);

namespace App\Http\Controllers\Frontend\Cabinet\Projects\Form\Fields;

use App\Models\Project;
use App\Models\Project\SalaryFund as SalaryFundEntity;
use Illuminate\Http\Request;

class SalaryFund
{
    /**
     * Имя поля для отображения в форме
     */
    const FIELD_NAME = 'salaryFund';

    /**
     * Тип поля
     */
    const FIELD_TYPE = 'salaryFund';

    /**
     * Максимальная длина int поля
     */
    const MAX_INTEGER_COLUMN = 2147483647;

    /**
     * Правила валидации полей
     */
    const FIELD_RULES = [
        'defaultInput.id.value'             => 'sometimes|nullable|integer',
        'defaultInput.invest.value'         => 'nullable|boolean',
        'defaultInput.name.value'           => 'required|max:255',
        'defaultInput.count.value'          => 'required|integer|max:' . self::MAX_INTEGER_COLUMN,
        'defaultInput.salaryPerMonth.value' => 'required|integer|max:' . self::MAX_INTEGER_COLUMN,
        'defaultInput.percent.value'        => 'sometimes|nullable|integer|max:100',
        'defaultInput.industrial.value'     => 'required|integer|in:0,1',
        'defaultInput.services.value'       => 'sometimes|nullable'
    ];

    /**
     * Список полей с вопросами
     */
    const FIELD_INPUTS = [];

    /**
     * Поля по умолчанию
     */
    const DEFAULT_INPUT = [
        'id'             => [
            'value' => null,
        ],
        'invest'         => [
            'type'        => 'checkbox',
            'title'       => 'На инвест период',
            'placeholder' => null,
            'value'       => false,
            'column'      => 'col-12',
        ],
        'name'           => [
            'type'        => 'text',
            'title'       => 'Наименование должности',
            'placeholder' => null,
            'value'       => null,
            'column'      => 'col-12 col-md-4',
        ],
        'count'          => [
            'type'        => 'text',
            'title'       => 'Количество',
            'placeholder' => null,
            'value'       => null,
            'column'      => 'col-12 col-md-4',
        ],
        'salaryPerMonth' => [
            'type'        => 'text',
            'title'       => 'Оклад в месяц',
            'placeholder' => null,
            'value'       => '',
            'column'      => 'col-12 col-md-4',
        ],
        'percent'        => [
            'type'        => 'text',
            'title'       => 'Процент от дохода',
            'placeholder' => null,
            'value'       => '',
            'column'      => 'col-12 col-md-4',
        ],
        'industrial'     => [
            'type'        => 'select',
            'title'       => 'ПП',
            'placeholder' => null,
            'value'       => 0,
            'values'      => [
                0 => 'Нет',
                1 => 'Да',
            ],
            'column'      => 'col-12 col-md-4',
        ],
        'services'       => [
            'type'        => 'select',
            'title'       => 'Услуги',
            'placeholder' => null,
            'value'       => [],
            'values'      => [],
            'column'      => 'col-12 col-md-4',
        ],
    ];

    /**
     * Сохраняет элемент в "Фонд заработной платы"
     *
     * @param Request $request
     * @param Project $project
     * @return mixed
     */
    public static function saveSalaryFundItem(Request $request, Project $project)
    {
        $fields = $request->validate(self::FIELD_RULES, __('project/validation'));
        $id     = (int) $fields['defaultInput']['id']['value'] ?? 0;

        unset($fields['defaultInput']['id']);

        $fields = collect($fields['defaultInput'])->map(function ($value, $fieldName) {
            if ($fieldName === 'services') {
                $servicesValue = array_map(function($val) { return $val['id']; }, $value['value']);

                return count($servicesValue) > 0 ? $servicesValue : null;
            }

            if ($fieldName === 'invest') {
                $value['value'] = (bool) $value['value'];
            }

            return $value['value'];
        })->toArray();

        if ($id === 0) {
            $project->salaryFund()->save(new SalaryFundEntity($fields));

            return $project->salaryFund;
        }

        SalaryFundEntity::findOrFail($id)->fill($fields)->save();

        return $project->salaryFund;
    }

    /**
     * Удаляет элемент из "Фонд заработной платы"
     *
     * @param  Request $request
     * @param  Project $project
     * @return bool|mixed|null
     * @throws \Exception
     */
    public static function deleteSalaryFundItem(Request $request, Project $project)
    {
        $salaryFundItem = $project->salaryFund()->findOrFail((int) $request->id);

        return $salaryFundItem->delete();
    }
}
