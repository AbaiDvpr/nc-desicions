<?php declare(strict_types=1);

namespace App\Http\Controllers\Frontend\Cabinet\Projects\Form\Fields;

use App\Models\Project;
use App\Models\Project\InvestmentAmount as InvestmentAmountEntity;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class InvestmentAmount
{

    /**
     * Имя поля для отображения в форме
     */
    const FIELD_NAME = 'investmentAmount';

    /**
     * Тип поля
     */
    const FIELD_TYPE = 'investmentAmount';

    /**
     * Правила валидации полей
     */
    const FIELD_RULES = [
        //self::FIELD_NAME . '.inputs'         => 'hasRequiredElements:2',
        self::FIELD_NAME . '.inputs.*.value' => 'sometimes|nullable|numeric',
    ];

    /**
     * Список полей с вопросами (поля должны совпадать с OwnInvestment::FIELD_INPUTS[1])
     */
    const FIELD_INPUTS = [
        'smr'            => [
            'type'        => 'numeric',
            'title'       => 'Перечень видов работ СМР, капитального ремонта',
            'placeholder' => 'укажите сумму',
            'value'       => null,
            'rules'       => '',
            'column'      => 'col-12 col-md-6 col-xl-4',
            'disabled'    => false
        ],
        'overhaul'       => [
            'type'        => 'numeric',
            'title'       => 'Перечень материалов при СМР, капитальном ремонте',
            'placeholder' => 'укажите сумму',
            'value'       => null,
            'rules'       => '',
            'column'      => 'col-12 col-md-6 col-xl-4',
            'disabled'    => false
        ],
        'readyPremises'  => [
            'type'        => 'numeric',
            'title'       => 'Помещения, здания, сооружения',
            'placeholder' => 'укажите сумму',
            'value'       => null,
            'rules'       => '',
            'column'      => 'col-12 col-md-6 col-xl-4',
            'disabled'    => false
        ],
        'equipment'      => [
            'type'        => 'numeric',
            'title'       => 'Приобретение оборудования, техники, мебели',
            'placeholder' => 'укажите сумму',
            'value'       => null,
            'rules'       => '',
            'column'      => 'col-12 col-md-6 col-xl-4',
            'disabled'    => false
        ],
        'raw'            => [
            'type'        => 'numeric',
            'title'       => 'Закуп сырья, товаров, расходных материалов',
            'placeholder' => 'укажите сумму',
            'value'       => null,
            'rules'       => '',
            'column'      => 'col-12 col-md-6 col-xl-4',
            'disabled'    => false
        ],
        'advertising'    => [
            'type'        => 'numeric',
            'title'       => 'Расходы на рекламу',
            'placeholder' => 'укажите сумму',
            'value'       => null,
            'rules'       => '',
            'column'      => 'col-12 col-md-6 col-xl-4',
            'disabled'    => false
        ],
        'wages'          => [
            'type'        => 'numeric',
            'title'       => 'Расходы по выплате заработной платы',
            'placeholder' => 'укажите сумму',
            'value'       => null,
            'rules'       => '',
            'column'      => 'col-12 col-md-6 col-xl-4',
            'disabled'    => false
        ],
        'rentalExpenses' => [
            'type'        => 'numeric',
            'title'       => 'Расходы по аренде',
            'placeholder' => 'укажите сумму',
            'value'       => null,
            'rules'       => '',
            'column'      => 'col-12 col-md-6 col-xl-4',
            'disabled'    => false
        ],
        'otherExpenses'  => [
            'type'        => 'numeric',
            'title'       => 'Прочие расходы',
            'placeholder' => 'укажите сумму',
            'value'       => null,
            'rules'       => '',
            'column'      => 'col-12 col-md-6 col-xl-4',
            'disabled'    => false
        ],
    ];

    /**
     * Выполняет заполнение полей
     *
     * @param  \App\Models\Project $project
     * @return array[]
     */
    public function getFilledFields(Project $project): array
    {
        $investmentAmount = $project->investmentAmount;

        if (!is_null($investmentAmount)) {
            return collect(self::FIELD_INPUTS)->map(function ($field, $fieldName) use ($investmentAmount, $project) {
                $field['value'] = $investmentAmount->{$fieldName} ?? null;
                return $field;
            })->toArray();
        }

        return self::FIELD_INPUTS;
    }

    /**
     * Сохранение данных с полей "Распределите привлекаемую сумму инвестиций по видам затрат"
     *
     * @param  \Illuminate\Http\Request $request
     * @param  \App\Models\Project      $project
     * @return false|\Illuminate\Database\Eloquent\Model
     */
    public static function saveForm(Request $request, Project $project)
    {
        if ($request->has('investmentAmount')) {
            try {
                // перед тем как сохранять значения, удаляем старые
                // заодно, таким образом сохраняем версии изменения
                $project->investmentAmount()->delete();

                $inputs = collect($request->input('investmentAmount.inputs'));

                $answers = $inputs->whereNotNull('value')->map(function ($value) {
                    return $value['value'] ?? null;
                })->all();

                if (count($answers) > 0) {
                    return $project->investmentAmount()->save(new InvestmentAmountEntity($answers));
                }
            } catch (\Exception $exception) {
                Log::error($exception->getMessage(), $exception->getTrace());
            }
        }

        return true;
    }
}
