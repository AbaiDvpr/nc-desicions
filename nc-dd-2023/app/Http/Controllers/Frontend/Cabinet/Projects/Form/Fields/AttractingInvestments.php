<?php declare(strict_types=1);

namespace App\Http\Controllers\Frontend\Cabinet\Projects\Form\Fields;

use App\Models\Project;
use App\Models\Project\AttractingInvestment as AttractingInvestmentEntity;
use App\Models\Project\WorkingCapital;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class AttractingInvestments
{
    /**
     * Имя поля для отображения в форме
     */
    const FIELD_NAME = 'attractingInvestment';

    /**
     * Тип поля
     */
    const FIELD_TYPE = 'attractingInvestment';

    /**
     * Долевое финансирование
     */
    const EQUITY_FINANCING = 1;

    /**
     * Кредитное финансирование
     */
    const CREDIT_FINANCING = 2;

    /**
     * Грантовое финансирование
     */
    const GRANT_FINANCING = 3;

    /**
     * Правила валидации полей
     */
    const FIELD_RULES = [
        self::FIELD_NAME . '.value'                                => 'required|in:1,2,3',
        self::FIELD_NAME . '.inputs.1.main.investorTerm.value'     => 'exclude_if:' . self::FIELD_NAME . '.value,2|required|integer|max:100',
        self::FIELD_NAME . '.inputs.1.main.investorDividend.value' => 'exclude_if:' . self::FIELD_NAME . '.value,2|required|numeric|max:100',

        self::FIELD_NAME . '.inputs.2.main.loanTerm.value'    => 'exclude_if:' . self::FIELD_NAME . '.value,1|sometimes|nullable|required|integer',
        self::FIELD_NAME . '.inputs.2.main.gracePeriod.value' => 'exclude_if:' . self::FIELD_NAME . '.value,1|sometimes|nullable|required|integer|max:36',
        self::FIELD_NAME . '.inputs.2.main.annualRate.value'  => 'exclude_if:' . self::FIELD_NAME . '.value,1|sometimes|nullable|required|numeric',
        self::FIELD_NAME . '.inputs.2.main.annuity.value'     => 'exclude_if:' . self::FIELD_NAME . '.value,1|sometimes|boolean',

        self::FIELD_NAME . '.inputs.2.workingCapital.replenishPeriod.value' => 'exclude_if:' . self::FIELD_NAME . '.value,1|sometimes|nullable|integer|min:24|max:36',
        self::FIELD_NAME . '.inputs.2.workingCapital.gracePeriod.value'     => 'exclude_if:' . self::FIELD_NAME . '.value,1|sometimes|nullable|integer|min:0|max:36',
        self::FIELD_NAME . '.inputs.2.workingCapital.annualRate.value'      => 'exclude_if:' . self::FIELD_NAME . '.value,1|sometimes|nullable|integer|min:0|max:100',
    ];

    /**
     * Список полей с вопросами
     */
    const FIELD_INPUTS = [
        self::EQUITY_FINANCING => [
            'main' => [
                'investorTerm'     => [
                    'type'         => 'numeric',
                    'title'        => 'Введите срок участия инвестора в проекте',
                    'placeholder'  => 'в годах',
                    'value'        => null,
                    'rules'        => '',
                    'column'       => 'col-12 col-md-6',
                ],
                'investorDividend' => [
                    'type'           => 'text',
                    'title'          => 'Укажите % дивидендов инвестора',
                    'placeholder'    => 'укажите в процентах',
                    'value'          => '',
                    'rules'          => '',
                    'column'         => 'col-12 col-md-6',
                    'isValuePercent' => true,
                ],
            ]
        ],

        self::CREDIT_FINANCING => [
            // На пополнение основных средств
            'main' => [
                'loanTerm'    => [
                    'type'         => 'text',
                    'title'        => 'Укажите срок займа, на инвестиционные цели',
                    'placeholder'  => 'в месяцах',
                    'hint'         => 'К инвестиционным целям относятся приобретение помещения, оборудования, ' .
                                      'ремонт помещения, строительство',
                    'value'        => null,
                    'rules'        => '',
                    'column'       => 'col-12',
                    'isValueMonth' => true,
                    'maxValue'     => 250,
                ],
                'gracePeriod' => [
                    'type'         => 'text',
                    'title'        => 'Выберите льготный период по выплате основного долга',
                    'placeholder'  => 'в месяцах (не более 36 месяцев)',
                    'hint'         => '<b>Льготный период</b> - «каникулы» по выплате основного долга',
                    'value'        => null,
                    'rules'        => '',
                    'column'       => 'col-12 col-md-6',
                    'isValueMonth' => true,
                ],
                'annualRate'  => [
                    'type'           => 'text',
                    'title'          => 'Годовая ставка вознаграждения по кредиту',
                    'placeholder'    => 'укажите в процентах',
                    'value'          => '',
                    'rules'          => '',
                    'column'         => 'col-12 col-md-6',
                    'isValuePercent' => true,
                ],
                'annuity'     => [
                    'type'        => 'checkbox',
                    'title'       => 'Аннуитетный расчет',
                    'placeholder' => null,
                    'value'       => false,
                    'rules'       => '',
                    'column'      => 'col-12',
                ],
            ],

            // На пополнение оборотных средств
            'workingCapital' => [
                'replenishPeriod' => [
                    'type'         => 'text',
                    'title'        => 'Укажите срок займа на пополнение оборотных средств',
                    'placeholder'  => 'в месяцах',
                    'hint'         => 'Срок займа на пополнение оборотных средств составляет от <b>24</b> до <b>36</b> месяцев',
                    'value'        => null,
                    'rules'        => '',
                    'column'       => 'col-12',
                    'isValueMonth' => true,
                ],
                'gracePeriod'     => [
                    'type'         => 'text',
                    'title'        => 'Укажите льготный период (каникулы)',
                    'placeholder'  => 'в месяцах (не более 36 месяцев)',
                    'hint'         => '<b>Льготный период</b> - «каникулы» по выплате основного долга',
                    'value'        => null,
                    'rules'        => '',
                    'column'       => 'col-12 col-md-6',
                    'isValueMonth' => true,
                ],
                'annualRate'      => [
                    'type'           => 'text',
                    'title'          => 'Годовая ставка вознаграждения по кредиту',
                    'placeholder'    => 'укажите в процентах',
                    'hint'           => null,
                    'value'          => '',
                    'rules'          => '',
                    'column'         => 'col-12 col-md-6',
                    'isValuePercent' => true,
                ],
            ]
        ],

        self::GRANT_FINANCING  => [
            'main' => []
        ]
    ];

    /**
     * Возвращает массив полей с заполненными данными
     *
     * @param  Project $project
     * @return array
     */
    public function getFilledFields(Project $project): array
    {
        $fields     = self::FIELD_INPUTS;
        $investment = $project->attractingInvestment;

        if (!is_null($investment)) {
            foreach ($fields as $key => $subFields) {
                foreach ($subFields as $subFieldName => $subField) {
                    $function = 'set' . ucfirst($subFieldName);

                    if (is_callable([$this, $function])) {
                        $fields[$key][$subFieldName] = $this->$function($project, $subField);

                        if (count($fields[$key][$subFieldName]) === 0) {
                            unset($fields[$key][$subFieldName]);
                        }
                    }
                }
            }
        }

        return [
            'value'  => $investment->investment ?? 1,
            'inputs' => $fields
        ];
    }

    /**
     * Сохранение данных с полей "На каких условиях вы планируете привлечь инвестиции"
     *
     * @param  Request $request
     * @param  Project $project
     * @return false|\Illuminate\Database\Eloquent\Model|int
     */
    public static function saveForm(Request $request, Project $project)
    {
        if (self::isFormGrantFinancing($request)) {
            // удаляем старое значение
            $project->attractingInvestment()->delete();

            self::saveMainFields($project, [], self::GRANT_FINANCING);

            // удаляем старое значения "Пополнение оборотного капитала"
            $project->workingCapital()->delete();

            return true;
        } elseif ($project->isFixedAssets() || $project->isWorkingCapital()) {
            if (!is_null($request->input(self::FIELD_TYPE))) {
                $fields = $request->validate(self::FIELD_RULES, __('project/validation'));

                if (array_key_exists(self::FIELD_TYPE, $fields)) {
                    try {
                        $investment = (int)$fields[self::FIELD_TYPE]['value'];
                        $mapFields  = $fields[self::FIELD_TYPE]['inputs'][$investment];

                        if (isset($mapFields['main'])) {
                            self::saveMainFields($project, $mapFields['main'], $investment);
                        }

                        self::saveWorkingCapital($project, $mapFields);

                        return true;
                    } catch (\Exception $exception) {
                        Log::error($exception->getMessage(), $exception->getTrace());
                    }
                }
            }
        }

        return false;
    }

    /**
     * Подставляет нужные данные в поля "На пополнение основных средств"
     *
     * @param  \App\Models\Project $project
     * @param  array               $subField
     * @return array
     */
    protected function setMain(Project $project, array $subField): array
    {
        if (count($subField) === 0) {
            return [];
        }

        if ($project->isFixedAssets()) {
            $investment = $project->attractingInvestment;

            foreach ($subField as $inputFieldName => $inputField) {
                $inputField['value']     = $investment->{$inputFieldName};
                $fields[$inputFieldName] = $inputField;
            }
        }

        return $fields ?? [];
    }

    /**
     * Подставляет нужные данные в поля "На пополнение оборотных средств"
     *
     * @param  \App\Models\Project $project
     * @param  array               $subField
     * @return array
     */
    protected function setWorkingCapital(Project $project, array $subField): array
    {
        if (count($subField) === 0) {
            return [];
        }

        if ($project->isWorkingCapital()) {
            $workingCapital = $project->workingCapital;

            foreach ($subField as $inputFieldName => $inputField) {
                $inputField['value']     = $workingCapital->{$inputFieldName} ?? null;
                $fields[$inputFieldName] = $inputField;
            }
        }

        return $fields ?? [];
    }

    /**
     * Сохраняем данные по таблице "На пополнение основных средств"
     *
     * @param  \App\Models\Project $project
     * @param  array               $mainFields
     * @param  int                 $investment
     * @return false|\Illuminate\Database\Eloquent\Model|int
     */
    protected static function saveMainFields(Project $project, array $mainFields, int $investment)
    {
        $savedInputs = collect($mainFields)->map(function ($value) {
            return $value['value'];
        })->toArray();

        $inputs = collect(self::FIELD_INPUTS[$investment]['main'] ?? [])->map(function ($field, $key) use ($savedInputs) {
            return $savedInputs[$key] ?? $field['value'];
        })->merge(['investment' => $investment])->toArray();

        if (is_null($project->attractingInvestment)) {
            return $project->attractingInvestment()->save(new AttractingInvestmentEntity($inputs));
        }

        if ($investment !== self::CREDIT_FINANCING) {
            $inputs['annuity'] = false;
        }

        return $project->attractingInvestment()->update($inputs);
    }

    /**
     * Сохраняем данные по таблице "На пополнение оборотного капитала"
     *
     * @param  \App\Models\Project $project
     * @param  array               $mapFields
     * @return false|\Illuminate\Database\Eloquent\Model
     */
    protected static function saveWorkingCapital(Project $project, array $mapFields)
    {
        // Удаляем предыдущее значение
        $project->workingCapital()->delete();

        if (!$project->isWorkingCapital() || !array_key_exists('workingCapital', $mapFields)) {
            return false;
        }

        $savedInputs = collect($mapFields['workingCapital'])->map(function ($value) {
            return $value['value'];
        })->toArray();

        return $project->workingCapital()->save(new WorkingCapital($savedInputs));
    }

    /**
     * Является ли заполняемая форма на "Грантовое финансирование"
     *
     * @param  \Illuminate\Http\Request $request
     * @return bool
     */
    protected static function isFormGrantFinancing(Request $request): bool
    {
        $attractingInvestment = $request->get('attractingInvestment', []);

        if (array_key_exists('value', $attractingInvestment)) {
            return (int) $attractingInvestment['value'] === self::GRANT_FINANCING;
        }

        return false;
    }
}
