<?php declare(strict_types=1);

namespace App\Http\Controllers\Frontend\Cabinet\Projects\Form\Fields;

use App\DI;
use App\Models\Project;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class MarketingExpenses
{
    /**
     * Имя поля для отображения в форме
     */
    const FIELD_NAME = 'marketingExpenses';

    /**
     * Тип поля
     */
    const FIELD_TYPE = 'marketingExpenses';

    /**
     * Максимальная длина int поля
     */
    const MAX_INTEGER_COLUMN = 2147483647;

    /**
     * Правила валидации полей
     */
    const FIELD_RULES = [
        //self::FIELD_NAME . '.inputs'         => 'required|array|requiredAll:'.self::FIELD_NAME,
        self::FIELD_NAME . '.inputs.*.name'  => 'sometimes|nullable|max:255',
        self::FIELD_NAME . '.inputs.*.value' => 'sometimes|nullable|integer|max:' . self::MAX_INTEGER_COLUMN,
    ];

    /**
     * Список полей с вопросами
     */
    const FIELD_INPUTS = [
        0 => [
            'type'        => 'text',
            'name'        => 'Контекстная реклама',
            'placeholder' => null,
            'value'       => null,
            'invest'      => false,
            'period'      => 1,
            'column'      => 'col-12 col-md-4',
        ],
        1 => [
            'type'        => 'text',
            'name'        => 'Таргетированная реклама',
            'placeholder' => null,
            'value'       => null,
            'invest'      => false,
            'period'      => 1,
            'column'      => 'col-12 col-md-4',
        ]
    ];

    /**
     * Поля по умолчанию
     */
    const DEFAULT_INPUT = [
        'type'        => 'text',
        'name'        => null,
        'placeholder' => null,
        'value'       => '',
        'invest'      => false,
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
        $fields = $project->marketingExpenses;

        if (!is_null($fields) && $fields->isNotEmpty()) {
            $records = [];

            foreach ($fields as $field) {
                $records[] = [
                    'type'        => 'text',
                    'name'        => $field->name,
                    'placeholder' => null,
                    'value'       => $field->price,
                    'invest'      => $field->invest ?? false,
                    'period'      => (int) $field->period,
                    'column'      => 'col-12 col-md-4'
                ];
            }

            return $records;
        }

        return self::FIELD_INPUTS;
    }

    /**
     * Сохранение формы с маркетинговыми расходами
     *
     * @param  \Illuminate\Http\Request $request
     * @param  \App\Models\Project      $project
     * @return false|iterable
     */
    public static function saveForm(Request $request, Project $project)
    {
        $inputs = $request->input(self::FIELD_NAME);

        if (!is_null($request->input(self::FIELD_TYPE))) {
            try {
                $withInvest = DI::getRepository()->getProject()->withInvestPeriod($project->getUuid());

                $inputs = collect($inputs['inputs'])->whereNotNull('value')
                    ->map(function ($field) use ($withInvest) {
                        return new \App\Models\Project\MarketingExpenses([
                            'name'   => $field['name'],
                            'price'  => $field['value'],
                            'invest' => $withInvest && (bool)$field['invest'],
                            'period' => (int)$field['period'],
                        ]);
                    });

                $project->marketingExpenses()->delete();

                return $project->marketingExpenses()->saveMany($inputs);
            } catch (\Exception $exception) {
                Log::error($exception->getMessage(), $exception->getTrace());
            }
        }

        return false;
    }
}
