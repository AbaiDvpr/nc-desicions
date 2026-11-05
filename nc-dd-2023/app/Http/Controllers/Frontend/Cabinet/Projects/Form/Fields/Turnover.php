<?php declare(strict_types=1);

namespace App\Http\Controllers\Frontend\Cabinet\Projects\Form\Fields;

use App\Models\Project;
use Illuminate\Http\Request;

class Turnover
{
    /**
     * Имя поля для отображения в форме
     */
    const FIELD_NAME = 'turnover';

    /**
     * Тип поля
     */
    const FIELD_TYPE = 'turnover';

    /**
     * Правила валидации полей
     */
    const FIELD_RULES = [
        self::FIELD_NAME . '.value'            => 'required|in:0,1',
        self::FIELD_NAME . '.inputs.1'         => 'exclude_if:' . self::FIELD_NAME . '.value,0|hasRequiredElements:1',
        self::FIELD_NAME . '.inputs.1.*.value' => 'exclude_if:' . self::FIELD_NAME . '.value,0|integer|min:1|max:120',
    ];

    /**
     * Список полей с вопросами
     */
    const FIELD_INPUTS = [
        1 => [
            'receivables'     => [
                'type'        => 'text',
                'title'       => 'Период погашения дебиторской задолженности, дней',
                'hint'        => '<b>Период оборачиваемости дебиторской задолженности</b> - через сколько дней в '.
                                 'среднем клиенты оплачивают выставленные им счета.',
                'placeholder' => null,
                'value'       => null,
                'rules'       => '',
                'column'      => 'col-12 col-md-4',
            ],
            'reserves'        => [
                'type'        => 'text',
                'title'       => 'Период оборачиваемости запасов, дней',
                'hint'        => '<b>Период оборачиваемости запасов</b> - количество времени в днях, необходимое '.
                                 'для того, чтобы распродать запасы.',
                'placeholder' => null,
                'value'       => null,
                'rules'       => '',
                'column'      => 'col-12 col-md-4',
            ],
            'accountsPayable' => [
                'type'        => 'text',
                'title'       => 'Период погашения кредиторской задолженности, дней',
                'hint'        => '<b>Период погашения кредиторской задолженности</b> - количество времени в днях, '.
                                 'требуемое для расчётов с поставщиками',
                'placeholder' => null,
                'value'       => null,
                'rules'       => '',
                'column'      => 'col-12 col-md-4',
            ],
        ],
    ];

    /**
     * Возвращает массив полей с заполненными данными
     *
     * @param  \App\Models\Project $project
     * @return array
     */
    public function getFilledFields(Project $project): array
    {
        if (isset(self::FIELD_INPUTS[1])) {
            $turnover = $project->getTurnover();

            if (!is_null($turnover)) {
                return [
                    1 => collect(self::FIELD_INPUTS[1])->map(function ($field, $fieldName) use($turnover) {
                        $field['value'] = $turnover[$fieldName] ?? null;

                        return $field;
                    })->toArray()
                ];
            }
        }

        return self::FIELD_INPUTS;
    }

    /**
     * Сохранение данных по оборачиваемости
     *
     * @param  \Illuminate\Http\Request $request
     * @param  \App\Models\Project      $project
     * @return bool
     */
    public static function saveForm(Request $request, Project $project): bool
    {
        if ($request->has('turnover')) {

            $fields = $request->validate(self::FIELD_RULES, __('project/validation'));

            $savedFields = null;

            if (array_key_exists('inputs', $fields['turnover'])) {
                $savedFields = collect($fields['turnover']['inputs'][1])->map(function ($value) {
                    return $value['value'];
                })->whereNotNull()->toArray();
            }

            $project->turnover = (!empty($savedFields)) ? $savedFields : null;

            return $project->save();
        }

        return false;
    }
}
