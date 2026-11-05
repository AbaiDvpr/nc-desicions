<?php declare(strict_types=1);

namespace App\Http\Controllers\Frontend\Cabinet\Projects\Form\Fields;

use App\DI;
use App\Models\Project;
use Illuminate\Http\Request;

class VolumeSales
{
    /**
     * Имя поля для отображения в форме
     */
    const FIELD_NAME = 'volumeSales';

    /**
     * Тип поля
     */
    const FIELD_TYPE = 'volumeSales';

    /**
     * Максимальная длина int поля
     */
    const MAX_INTEGER_COLUMN = 2147483647;

    /**
     * Правила валидации полей
     */
    const FIELD_RULES = [
        'defaultInput.id.value'         => 'sometimes|nullable|integer',
        'defaultInput.name.value'       => 'required|max:255',
        'defaultInput.count.value'      => 'required|numeric|max:' . self::MAX_INTEGER_COLUMN,
        //'defaultInput.period.value'   => 'integer|min:1|max:12',
        'defaultInput.seasonal.value'   => 'sometimes|nullable|array|min:1|max:12',
        'defaultInput.seasonal.value.*' => 'integer',
        'defaultInput.units.value'      => 'required|integer|max:' . self::MAX_INTEGER_COLUMN,
        'defaultInput.price.value'      => 'required|numeric|max:' . self::MAX_INTEGER_COLUMN,
    ];

    /**
     * Список полей с вопросами
     */
    const FIELD_INPUTS = [];

    /**
     * Поля по умолчанию
     */
    const DEFAULT_INPUT = [
        'id'    => [
            'value'        => null,
            'defaultValue' => null,
            'isActive'     => false,
            'hasToggle'    => false,
        ],
        'name'  => [
            'type'         => 'text',
            'title'        => 'Наименование продукции/услуг объединённых в группы',
            'placeholder'  => null,
            'value'        => null,
            'defaultValue' => null,
            'isActive'     => false,
            'hasToggle'    => false,
            'column'       => 'col-12 col-md-4',
        ],
        'count' => [
            'type'         => 'text',
            'title'        => 'Планируемое количество продаж за период',
            'placeholder'  => null,
            'value'        => '',
            'defaultValue' => '',
            'isActive'     => false,
            'hasToggle'    => false,
            'column'       => 'col-12 col-md-4',
        ],
        'seasonal' => [
            'type'         => 'text',
            'title'        => 'Период продаж',
            'placeholder'  => null,
            'value'        => null,
            'defaultValue' => null,
            'isActive'     => false,
            'hasToggle'    => true,
            'column'       => 'col-12 col-md-4',
        ],
        'units' => [
            'type'         => 'select',
            'title'        => 'Ед. измерения',
            'placeholder'  => null,
            'value'        => 1,
            'defaultValue' => 1,
            'isActive'     => false,
            'hasToggle'    => false,
            'values'       => [
                1  => 'шт',
                2  => 'кг',
                3  => 'тонн',
                4  => 'усл',
                5  => 'м2',
                6  => 'м3',
                7  => 'л',
                8  => 'м.п.',
                9  => 'км',
                10 => 'чел.',
                11 => 'ед.',
            ],
            'column'       => 'col-12 col-md-4',
        ],
        'price' => [
            'type'         => 'text',
            'title'        => 'Цена за ед./ср.чек',
            'placeholder'  => null,
            'value'        => '',
            'defaultValue' => '',
            'isActive'     => false,
            'hasToggle'    => false,
            'column'       => 'col-12 col-md-4',
        ],
    ];


    /**
     * Сохраняет элемент в "Планируемый объем продаж в месяц"
     *
     * @param Request $request
     * @param Project $project
     * @return mixed
     */
    public static function saveVolumeSalesItem(Request $request, Project $project)
    {
        $fields = $request->validate(self::FIELD_RULES, __('project/validation'));
        $id     = (int) $fields['defaultInput']['id']['value'] ?? 0;

        unset($fields['defaultInput']['id']);

        $fields = collect($fields['defaultInput'])->map(function ($value, $fieldName) {
            return $value['value'];
        })->toArray();

        return DI::getRepository()->getProject()->getVolumeSales($project->getUuid())->save($id, $fields);
    }

    /**
     * Удаляет элемент из "Планируемый объем продаж в месяц"
     *
     * @param  Request $request
     * @param  Project $project
     * @return bool
     */
    public static function deleteVolumeSalesItem(Request $request, Project $project): bool
    {
        return DI::getRepository()->getProject()->getVolumeSales($project->getUuid())->delete((int) $request->id);
    }
}
