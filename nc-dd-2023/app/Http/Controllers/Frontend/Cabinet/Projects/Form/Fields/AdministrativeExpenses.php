<?php declare(strict_types=1);

namespace App\Http\Controllers\Frontend\Cabinet\Projects\Form\Fields;

use App\DI;
use App\Models\Project;
use App\Models\Project\AdministrativeExpenses as AdministrativeExpensesModel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class AdministrativeExpenses
{
    /**
     * Имя поля для отображения в форме
     */
    const FIELD_NAME = 'administrativeExpenses';

    /**
     * Тип поля
     */
    const FIELD_TYPE = 'administrativeExpenses';

    /**
     * Максимальная длина int поля
     */
    const MAX_INTEGER_COLUMN = 2147483647;

    /**
     * Правила валидации полей
     */
    const FIELD_RULES = [
        self::FIELD_NAME . '.inputs'         => 'required|array|requiredAll:' . self::FIELD_NAME,
        self::FIELD_NAME . '.inputs.*.name'  => 'sometimes|nullable|max:255',
        self::FIELD_NAME . '.inputs.*.value' => 'sometimes|nullable|integer|max:' . self::MAX_INTEGER_COLUMN,
    ];

    /**
     * Список полей с вопросами
     */
    const FIELD_INPUTS = [
        0 => [
            'type'        => 'text',
            'name'        => 'Аренда офиса',
            'placeholder' => null,
            'value'       => null,
            'invest'      => false,
            'typeExpense' => AdministrativeExpensesModel::TYPE_RENT,
            'period'      => 1,
            'column'      => 'col-12 col-md-4',
        ],
        1 => [
            'type'        => 'text',
            'name'        => 'Аренда склада',
            'placeholder' => null,
            'value'       => null,
            'invest'      => false,
            'typeExpense' => AdministrativeExpensesModel::TYPE_RENT,
            'period'      => 1,
            'column'      => 'col-12 col-md-4',
        ],
        2 => [
            'type'        => 'text',
            'name'        => 'Аренда магазина',
            'placeholder' => null,
            'value'       => null,
            'invest'      => false,
            'typeExpense' => AdministrativeExpensesModel::TYPE_RENT,
            'period'      => 1,
            'column'      => 'col-12 col-md-4',
        ],
        3 => [
            'type'        => 'text',
            'name'        => 'Интернет и связь',
            'placeholder' => null,
            'value'       => null,
            'invest'      => false,
            'typeExpense' => AdministrativeExpensesModel::TYPE_OTHER,
            'period'      => 1,
            'column'      => 'col-12 col-md-4',
        ],
        4 => [
            'type'        => 'text',
            'name'        => 'Банковское обслуживание',
            'placeholder' => null,
            'value'       => null,
            'invest'      => false,
            'typeExpense' => AdministrativeExpensesModel::TYPE_OTHER,
            'period'      => 1,
            'column'      => 'col-12 col-md-4',
        ],
        5 => [
            'type'        => 'text',
            'name'        => 'Услуги третьих лиц (аутсорсинг)',
            'placeholder' => null,
            'value'       => null,
            'invest'      => false,
            'typeExpense' => AdministrativeExpensesModel::TYPE_OTHER,
            'period'      => 1,
            'column'      => 'col-12 col-md-4',
        ],
        6 => [
            'type'        => 'text',
            'name'        => 'Коммунальные расходы',
            'placeholder' => null,
            'value'       => null,
            'invest'      => false,
            'typeExpense' => AdministrativeExpensesModel::TYPE_OTHER,
            'period'      => 1,
            'column'      => 'col-12 col-md-4',
        ],
        7 => [
            'type'        => 'text',
            'name'        => 'Земельный налог',
            'placeholder' => null,
            'value'       => null,
            'invest'      => false,
            'typeExpense' => AdministrativeExpensesModel::TYPE_OTHER,
            'period'      => 1,
            'column'      => 'col-12 col-md-4',
        ],
        8 => [
            'type'        => 'text',
            'name'        => 'Командировочные расходы',
            'placeholder' => null,
            'value'       => null,
            'invest'      => false,
            'typeExpense' => AdministrativeExpensesModel::TYPE_OTHER,
            'period'      => 1,
            'column'      => 'col-12 col-md-4',
        ],
        9 => [
            'type'        => 'text',
            'name'        => 'Транспортные расходы',
            'placeholder' => null,
            'value'       => null,
            'invest'      => false,
            'typeExpense' => AdministrativeExpensesModel::TYPE_OTHER,
            'period'      => 1,
            'column'      => 'col-12 col-md-4',
        ],
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
        'typeExpense' => AdministrativeExpensesModel::TYPE_OTHER,
        'period'      => 1,
        'column'      => 'col-12 col-md-4',
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
     * Типы расходов
     */
    const DEFAULT_TYPES = [
        AdministrativeExpensesModel::TYPE_OTHER => 'Прочее',
        AdministrativeExpensesModel::TYPE_RENT  => 'Аренда',
    ];

    /**
     * Получает заполненные поля
     *
     * @param  Project       $project
     * @return array|array[]
     */
    public function getFilledFields(Project $project): array
    {
        $expenses = $project->administrativeExpenses;

        if (!is_null($expenses) && $expenses->isNotEmpty()) {
             return $expenses->map(function ($field) {
                return [
                    'type'        => 'text',
                    'name'        => $field->name,
                    'placeholder' => null,
                    'value'       => $field->price,
                    'invest'      => $field->invest ?? false,
                    'typeExpense' => $field->type ?? 0,
                    'period'      => (int) $field->period,
                    'column'      => 'col-12 col-md-4',
                ];
            })->toArray();
        }

        return self::FIELD_INPUTS;
    }

    /**
     * Сохранение формы с административными расходами
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

                $inputs = collect($inputs['inputs'])->whereNotNull('value')->map(function ($field) use ($withInvest) {
                    return new AdministrativeExpensesModel([
                        'name'   => $field['name'],
                        'price'  => $field['value'],
                        'invest' => $withInvest && (bool) $field['invest'],
                        'type'   => (int) $field['typeExpense'],
                        'period' => (int) $field['period'],
                    ]);
                });

                $project->administrativeExpenses()->delete();

                return $project->administrativeExpenses()->saveMany($inputs);
            } catch (\Exception $exception) {
                Log::error($exception->getMessage(), $exception->getTrace());
            }
        }

        return false;
    }
}
