<?php declare(strict_types=1);

namespace App\Http\Controllers\Frontend\Cabinet\Projects\Form\Fields;

use App\Models\Project;
use App\Models\Project\ReadyPremisesList as ReadyPremisesListEntity;
use Illuminate\Http\Request;

/**
 * Данные по амортизации на СМР
 */
class OverhaulList
{
    /**
     * Имя поля для отображения в форме
     */
    const FIELD_NAME = 'overhaulList';

    /**
     * Тип поля
     */
    const FIELD_TYPE = 'overhaulList';

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
            'value'       => null,
            'column'      => 'col-12 col-md-4',
        ],
        'price'        => [
            'type'        => 'text',
            'title'       => 'Стоимость',
            'placeholder' => null,
            'value'       => null,
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
     * Возвращает общую сумму по привлекаемой сумме на приобретение недвижимости
     *
     * @param  \App\Models\Project $project
     * @return int
     */
    public function getTotalSum(Project $project): int
    {
        $totalSum = 0;

        // Проверяем есть ли соответсвующее значение в таблице
        // "Будете ли вы участвовать собственными вложениями?"
        if ($project->isOwnInvestment()) {
            $totalSum = $project->ownInvestment->overhaul ?? 0;
        }

        // Проверяем есть ли соответсвующее значение в таблице
        // "Распределите привлекаемую сумму инвестиций по видам затрат"
        $totalSum += $project->investmentAmount->overhaul ?? 0;

        return $totalSum;
    }

    /**
     * Сохраняет элемент в "Перечень приобретаемой недвижимости"
     *
     * @param Request $request
     * @param Project $project
     * @return mixed
     */
    public static function saveOverhaulItem(Request $request, Project $project)
    {
        $fields = $request->validate(self::FIELD_RULES, __('project/validation'));
        $id     = (int) $fields['defaultInput']['id']['value'] ?? 0;

        unset($fields['defaultInput']['id']);

        $fields = collect($fields['defaultInput'])->map(function ($value, $fieldName) {
            return $value['value'] ?? null;
        })->put('type', 3)->toArray();

        if ($id === 0) {
            $project->overhaulList()->save(new ReadyPremisesListEntity($fields));

            return $project->overhaulList;
        }

        ReadyPremisesListEntity::findOrFail($id)->fill($fields)->save();

        return $project->overhaulList;
    }

    /**
     * Удаляет элемент из "Перечень приобретаемой недвижимости"
     *
     * @param  Request         $request
     * @param  Project         $project
     * @return bool|mixed|null
     * @throws \Exception
     */
    public static function deleteOverhaulItem(Request $request, Project $project)
    {
        $readyPremisesItem = $project->overhaulList()->findOrFail((int) $request->id);

        return $readyPremisesItem->delete();
    }

    /**
     * Проверяет поле "Капитальный ремонт" на активность
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
        if ($project->isOwnInvestment()) {
            $totalSum = $project->ownInvestment->overhaul ?? 0;
        }

        // Проверяем есть ли соответсвующее значение в таблице
        // "Распределите привлекаемую сумму инвестиций по видам затрат"
        $totalSum += $project->investmentAmount->overhaul ?? 0;

        if ($totalSum === 0) {
            $project->overhaulList()->delete();

            return false;
        }

        return true;
    }
}
