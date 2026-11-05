<?php declare(strict_types=1);

namespace App\Http\Controllers\Frontend\Cabinet\Projects\Form\Fields;

use App\DI;
use App\Models\Project;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class VariableCostsOther
{
    /**
     * Имя поля для отображения в форме
     */
    const FIELD_NAME = 'variableCostsOther';

    /**
     * Тип поля
     */
    const FIELD_TYPE = 'variableCostsOther';

    /**
     * Правила валидации полей
     */
    const FIELD_RULES = [
        VariableCosts::FIELD_NAME . '.' . self::FIELD_NAME . '.inputs'          => 'required_if:variableCosts.value,false',
        VariableCosts::FIELD_NAME . '.' . self::FIELD_NAME . '.inputs.*.invest' => 'required_if:variableCosts.value,false|boolean',
        VariableCosts::FIELD_NAME . '.' . self::FIELD_NAME . '.inputs.*.title'  => 'required_if:variableCosts.value,false|max:255',
        VariableCosts::FIELD_NAME . '.' . self::FIELD_NAME . '.inputs.*.value'  => 'required_if:variableCosts.value,false|integer|max:1000000000',
        VariableCosts::FIELD_NAME . '.' . self::FIELD_NAME . '.inputs.*.period' => 'required_if:variableCosts.value,false|integer|min:0|max:12',
    ];

    /**
     * Список полей с вопросами
     */
    const FIELD_INPUTS = [];

    /**
     * Поля по умолчанию
     */
    const DEFAULT_INPUT = [
        'type'        => 'text',
        'invest'      => false,
        'title'       => null,
        'placeholder' => null,
        'value'       => '',
        'period'      => 1,
        'column'      => 'col-12 col-md-4'
    ];

    /**
     * Период списания затрат
     * в качестве ключа - кол-во месяцев
     */
    const DEFAULT_PERIODS = [
        0  => 'Единоразово',
        1  => 'ежемесячно',
        2  => 'каждые 2 месяца',
        3  => 'ежеквартально',
        4  => '3 раза в год',
        6  => '2 раза в год',
        12 => 'раз в год',
    ];

    /**
     * Получает заполненные поля
     *
     * @param  Project       $project
     * @return array|array[]
     */
    public function getFilledFields(Project $project): array
    {
        $fields = $project->variableCostsOther;

        if (!is_null($fields) && $fields->isNotEmpty()) {
            $records = [];

            foreach ($fields as $field) {
                $records[] = [
                    'type'        => 'text',
                    'title'       => $field->title,
                    'invest'      => $field->invest ?? false,
                    'placeholder' => null,
                    'value'       => $field->price,
                    'period'      => (int) $field->period,
                    'column'      => 'col-12 col-md-4'
                ];
            }

            return $records;
        }

        return self::FIELD_INPUTS;
    }

    public static function saveForm(Request $request, Project $project)
    {
        // добавляю такую проверку, потому что required_if не совсем корректно отрабатывает
        $field = $request->input(VariableCosts::FIELD_TYPE);
        if (!empty($field) && $field['value'] === true) {
            $project->variableCostsOther()->delete();

            return false;
        }

        if (!is_null($request->input(VariableCosts::FIELD_TYPE . '.' .self::FIELD_TYPE))) {
            $inputs = $request->validate(self::FIELD_RULES, __('project/validation'));

            $project->variableCostsOther()->delete();

            $inputs = $inputs[VariableCosts::FIELD_TYPE];

            if (array_key_exists(self::FIELD_TYPE, $inputs)) {
                try {
                    $inputs     = $inputs[self::FIELD_TYPE];
                    $withInvest = DI::getRepository()->getProject()->withInvestPeriod($project->getUuid());

                    $inputSaved = collect($inputs['inputs'])->whereNotNull('value')->map(function ($field) use (
                        $withInvest
                    ) {
                        return new \App\Models\Project\VariableCostsOther([
                            'title'  => $field['title'],
                            'price'  => $field['value'],
                            'invest' => $withInvest && (bool)$field['invest'],
                            'period' => (int)$field['period'],
                        ]);
                    });

                    return $project->variableCostsOther()->saveMany($inputSaved);
                } catch (\Exception $exception) {
                    Log::error($exception->getMessage(), $exception->getTrace());
                }
            }
        }

        return false;
    }
}
