<?php declare(strict_types=1);

namespace App\Http\Controllers\Frontend\Cabinet\Projects\Form\Fields;

use App\Models\Project;
use App\Models\Project\OwnInvestment as OwnInvestmentEntity;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class OwnInvestment
{
    /**
     * Имя поля для отображения в форме
     */
    const FIELD_NAME = 'own_investments';

    /**
     * Тип поля
     */
    const FIELD_TYPE = 'OwnInvestment';

    /**
     * Правила валидации полей
     */
    const FIELD_RULES = [
        self::FIELD_NAME . '.value'            => 'required|in:0,1',
        self::FIELD_NAME . '.inputs.1.*.value' => 'exclude_if:' . self::FIELD_NAME . '.value,0|sometimes|nullable|numeric',
    ];

    /**
     * Список полей с вопросами
     */
    const FIELD_INPUTS = [
        1 => [
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
            $ownInvestmentField = $project->ownInvestment;

            if (!is_null($ownInvestmentField)) {
                return [
                    1 => collect(self::FIELD_INPUTS[1])->map(function ($field, $fieldName) use($ownInvestmentField) {
                        $field['value'] = $ownInvestmentField->{$fieldName} ?? null;

                        return $field;
                    })->toArray()
                ];
            }
        }

        return self::FIELD_INPUTS;
    }

    /**
     * Сохранение данных с полей "Будете ли вы участвовать собственными вложениями?"
     *
     * @param  \Illuminate\Http\Request $request
     * @param  \App\Models\Project      $project
     * @return false|\Illuminate\Database\Eloquent\Model
     */
    public static function saveForm(Request $request, Project $project)
    {
        if (!is_null($request->input('own_investments'))) {
            try {
                // перед тем как сохранять значения, удаляем старые
                // заодно, таким образом сохраняем версии изменения
                $project->ownInvestment()->delete();

                $ownInvestment = (int) $request->input('own_investments.value');

                if ($ownInvestment === 1) {
                    $inputs = $request->input('own_investments.inputs')[$ownInvestment];

                    $answers = collect($inputs)->map(function ($value, $key) {
                        return $value['value'] ?? null;
                    })->whereNotNull()->toArray();

                    return $project->ownInvestment()->save(new OwnInvestmentEntity($answers));
                }
            } catch (\Exception $exception) {
                Log::error($exception->getMessage(), $exception->getTrace());
            }
        }

        return false;
    }
}
