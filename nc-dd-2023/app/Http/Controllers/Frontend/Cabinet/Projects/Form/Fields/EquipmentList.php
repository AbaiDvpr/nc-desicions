<?php declare(strict_types=1);

namespace App\Http\Controllers\Frontend\Cabinet\Projects\Form\Fields;

use App\Models\Project;
use App\Models\Project\EquipmentList as EquipmentListEntity;
use Illuminate\Http\Request;

/**
 * Таблица "Перечень приобретаемого оборудования"
 *
 * @package EquipmentList
 */
class EquipmentList
{
    /**
     * Имя поля для отображения в форме
     */
    const FIELD_NAME = 'equipmentList';

    /**
     * Тип поля
     */
    const FIELD_TYPE = 'equipmentList';

    /**
     * Максимальная длина int поля
     */
    const MAX_INTEGER_COLUMN = 2147483647;

    /**
     * Правила валидации полей
     */
    const FIELD_RULES = [
        'defaultInput.id.value'           => 'sometimes|nullable|integer',
        'defaultInput.name.value'         => 'required|max:255',
        'defaultInput.count.value'        => 'required|integer|max:' . self::MAX_INTEGER_COLUMN,
        'defaultInput.priceOneUnit.value' => 'required|numeric',
        'defaultInput.price.value'        => 'required|numeric',
        'defaultInput.year.value'         => 'required|integer|max:' . self::MAX_INTEGER_COLUMN,
        'defaultInput.lifeTime.value'     => 'required|integer|max:' . self::MAX_INTEGER_COLUMN,
    ];

    /**
     * Список полей с вопросами
     */
    const FIELD_INPUTS = [];

    /**
     * Поля по умолчанию
     */
    const DEFAULT_INPUT = [
        'id'           => [
            'value' => null,
        ],
        'name'         => [
            'type'        => 'text',
            'title'       => 'Наименование',
            'placeholder' => 'укажите название оборудования',
            'value'       => null,
            'column'      => 'col-12 col-md-4',
        ],
        'count'        => [
            'type'        => 'text',
            'title'       => 'Кол-во',
            'placeholder' => null,
            'value'       => null,
            'column'      => 'col-12 col-md-4',
        ],
        'priceOneUnit' => [
            'type'        => 'text',
            'title'       => 'Цена за ед',
            'placeholder' => null,
            'value'       => '',
            'column'      => 'col-12 col-md-4',
        ],
        'price'        => [
            'type'        => 'text',
            'title'       => 'Стоимость',
            'placeholder' => null,
            'value'       => '',
            'column'      => 'col-12 col-md-4',
        ],
        'year'         => [
            'type'        => 'text',
            'title'       => 'Год приобретения',
            'placeholder' => null,
            'value'       => null,
            'column'      => 'col-12 col-md-4',
        ],
        'lifeTime'     => [
            'type'        => 'text',
            'title'       => 'Срок службы в годах',
            'placeholder' => null,
            'value'       => null,
            'column'      => 'col-12 col-md-4',
        ],
    ];

    /**
     * Возвращает общую сумму по привлекаемой сумме на приобретение оборудования
     *
     * @param  \App\Models\Project $project
     * @return int
     */
    public function getTotalSum(Project $project): int
    {
        $totalSum = 0;

        // Проверяем есть ли соответсвующее значение в таблице
        // "Будете ли вы участвовать собственными вложениями?"
        if ($project->own_investments) {
            $totalSum = $project->ownInvestment->equipment ?? 0;
        }

        // Проверяем есть ли соответсвующее значение в таблице
        // "Распределите привлекаемую сумму инвестиций по видам затрат"
        $totalSum += $project->investmentAmount->equipment ?? 0;

        return $totalSum;
    }

    /**
     * Сохраняет элемент в "Перечень приобретаемого оборудования"
     *
     * @param Request $request
     * @param Project $project
     * @return mixed
     */
    public static function saveEquipmentItem(Request $request, Project $project)
    {
        $fields = $request->validate(self::FIELD_RULES, trans('project/validation'));
        $id     = (int) $fields['defaultInput']['id']['value'] ?? 0;

        unset($fields['defaultInput']['id']);
        unset($fields['defaultInput']['total']);

        $fields = collect($fields['defaultInput'])->map(function ($value) {
            return $value['value'];
        })->toArray();

        if ($id === 0) {
            $project->equipmentList()->save(new EquipmentListEntity($fields));

            return $project->equipmentList;
        }

        EquipmentListEntity::findOrFail($id)->fill($fields)->save();

        return $project->equipmentList;
    }

    /**
     * Удаляет элемент из "Перечень приобретаемого оборудования"
     *
     * @param  Request $request
     * @param  Project $project
     * @return bool|mixed|null
     * @throws \Exception
     */
    public static function deleteEquipmentItem(Request $request, Project $project)
    {
        $equipmentItem = $project->equipmentList()->findOrFail((int) $request->id);

        return $equipmentItem->delete();
    }

    /**
     * Проверяет поле "Перечень приобретаемого оборудования" на активность
     *
     * @param  Project         $project
     * @return bool|mixed|null
     * @throws \Exception
     */
    public static function checkFieldActive(Project $project)
    {
        $totalSum = 0;

        // Проверяем есть ли соответсвующее значение в таблице
        // "Будете ли вы участвовать собственными вложениями?"
        if ($project->own_investments) {
            $totalSum = $project->ownInvestment->equipment ?? 0;
        }

        // Проверяем есть ли соответсвующее значение в таблице
        // "Распределите привлекаемую сумму инвестиций по видам затрат"
        $totalSum += $project->investmentAmount->equipment ?? 0;

        if ($totalSum === 0) {
            $project->equipmentList()->delete();

            return false;
        }

        return true;
    }
}
