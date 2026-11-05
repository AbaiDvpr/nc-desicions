<?php

const MAX_INTEGER_COLUMN = 2147483647;

$fieldIsRequired   = 'Поле не заполнено';
$maxIntegerMessage = 'Поле должно быть целым числом и не более ' . MAX_INTEGER_COLUMN;

return [
    'uuid.required'                 => 'Не указан uuid финансовой модели',
    'name.value.required'           => 'Не заполнено',
    'form.value.required'           => 'Не заполнено',
    'form.value.in'                 => 'Ничего не выбрано',
    'planning.values.from.required' => 'Дата не выбрана',
    'planning.values.to.required'   => 'Дата не выбрана',

    'tax_regime.value.required' => 'Ничего не выбрали',
    'tax_regime.value.in'       => 'Выберите один из вариантов',

    'currency.value.required' => 'Валюта не выбрана',
    'currency.value.in'       => 'Необходимо указать, в какой валюте производить расчет',

    'time_param.value.required' => 'Ничего не выбрали',
    'time_param.value.in'       => 'Выберите один из вариантов',

    'schedule.value.required' => 'Ничего не выбрали',
    'schedule.value.in'       => 'Выберите один из вариантов',

    'own_investments.inputs.1.has_required_elements' => 'Необходимо заполнить как минимум 1 из пунктов',
    'investmentAmount.inputs.has_required_elements'  => 'Необходимо заполнить как минимум 1 из пунктов',

    'own_investments.inputs.*.smr.value.required'            => $fieldIsRequired,
    'own_investments.inputs.*.smr.value.integer'             => $maxIntegerMessage,
    'own_investments.inputs.*.overhaul.value.required'       => $fieldIsRequired,
    'own_investments.inputs.*.overhaul.value.integer'        => $maxIntegerMessage,
    'own_investments.inputs.*.readyPremises.value.required'  => $fieldIsRequired,
    'own_investments.inputs.*.readyPremises.value.integer'   => $maxIntegerMessage,
    'own_investments.inputs.*.equipment.value.required'      => $fieldIsRequired,
    'own_investments.inputs.*.equipment.value.integer'       => $maxIntegerMessage,
    'own_investments.inputs.*.raw.value.required'            => $fieldIsRequired,
    'own_investments.inputs.*.raw.value.integer'             => $maxIntegerMessage,
    'own_investments.inputs.*.advertising.value.required'    => $fieldIsRequired,
    'own_investments.inputs.*.advertising.value.integer'     => $maxIntegerMessage,
    'own_investments.inputs.*.wages.value.required'          => $fieldIsRequired,
    'own_investments.inputs.*.wages.value.integer'           => $maxIntegerMessage,
    'own_investments.inputs.*.rentalExpenses.value.required' => $fieldIsRequired,
    'own_investments.inputs.*.rentalExpenses.value.integer'  => $maxIntegerMessage,
    'own_investments.inputs.*.otherExpenses.value.required'  => $fieldIsRequired,
    'own_investments.inputs.*.otherExpenses.value.integer'   => $maxIntegerMessage,

    'investmentAmount.inputs.smr.value.required'            => $fieldIsRequired,
    'investmentAmount.inputs.smr.value.integer'             => $maxIntegerMessage,
    'investmentAmount.inputs.overhaul.value.required'       => $fieldIsRequired,
    'investmentAmount.inputs.overhaul.value.integer'        => $maxIntegerMessage,
    'investmentAmount.inputs.readyPremises.value.required'  => $fieldIsRequired,
    'investmentAmount.inputs.readyPremises.value.integer'   => $maxIntegerMessage,
    'investmentAmount.inputs.equipment.value.required'      => $fieldIsRequired,
    'investmentAmount.inputs.equipment.value.integer'       => $maxIntegerMessage,

    'defaultInput.name.value.required'           => $fieldIsRequired,
    'defaultInput.name.value.max'                => 'Максимум :max символов',
    'defaultInput.count.value.required'          => $fieldIsRequired,
    'defaultInput.count.value.integer'           => $maxIntegerMessage,
    'defaultInput.count.value.max'               => 'Число не должно превышать значение выше :max',
    'defaultInput.priceOneUnit.value.required'   => $fieldIsRequired,
    'defaultInput.priceOneUnit.value.integer'    => $maxIntegerMessage,
    'defaultInput.priceOneUnit.value.max'        => 'Число не должно превышать значение выше :max',
    'defaultInput.price.value.required'          => $fieldIsRequired,
    'defaultInput.price.value.integer'           => $maxIntegerMessage,
    'defaultInput.price.value.max'               => 'Число не должно превышать значение выше :max',
    'defaultInput.period.value.min'              => 'Число не менее :min месяцев',
    'defaultInput.period.value.max'              => 'Число не более :max месяцев',
    'defaultInput.year.value.required'           => $fieldIsRequired,
    'defaultInput.year.value.integer'            => $maxIntegerMessage,
    'defaultInput.year.value.max'                => 'Число не должно превышать значение выше :max',
    'defaultInput.lifeTime.value.required'       => $fieldIsRequired,
    'defaultInput.lifeTime.value.integer'        => $maxIntegerMessage,
    'defaultInput.lifeTime.value.max'            => 'Число не должно превышать значение выше :max',
    'defaultInput.salaryPerMonth.value.required' => $fieldIsRequired,
    'defaultInput.salaryPerMonth.value.integer'  => $maxIntegerMessage,
    'defaultInput.salaryPerMonth.value.max'      => 'Число не должно превышать значение выше :max',
    'defaultInput.units.value.required'          => $fieldIsRequired,
    'defaultInput.units.value.integer'           => $maxIntegerMessage,
    'defaultInput.units.value.max'               => 'Число не должно превышать значение выше :max',
    'defaultInput.seasonal.value.array'          => 'Ожидается массив данных',
    'defaultInput.seasonal.value.min'            => 'Не менее :min месяца',
    'defaultInput.seasonal.value.max'            => 'Не более :max месяцев',

    'defaultInput.costType.value.required' => $fieldIsRequired,
    'defaultInput.costType.value.max'      => 'Максимум :max символов',
    'defaultInput.vs_id.value.required'    => $fieldIsRequired,
    'defaultInput.vs_id.value.integer'     => $maxIntegerMessage,
    'defaultInput.vs_id.value.max'         => 'Число не должно превышать значение выше :max',

    'attractingInvestment.value.required'                                => 'Поле не правильно заполнено',
    'attractingInvestment.value.in'                                      => 'Поле выбрано не верно',
    'attractingInvestment.inputs.1.main.investorTerm.value.required'     => $fieldIsRequired,
    'attractingInvestment.inputs.1.main.investorTerm.value.integer'      => $maxIntegerMessage,
    'attractingInvestment.inputs.1.main.investorTerm.value.max'          => 'Не более :max лет',
    'attractingInvestment.inputs.1.main.investorDividend.value.required' => $fieldIsRequired,
    'attractingInvestment.inputs.1.main.investorDividend.value.numeric'  => 'Поле должно быть числом',
    'attractingInvestment.inputs.1.main.investorDividend.value.max'      => 'Не более :max %',

    'attractingInvestment.inputs.2.main.loanTerm.value.required'    => $fieldIsRequired,
    'attractingInvestment.inputs.2.main.loanTerm.value.integer'     => $maxIntegerMessage,
    'attractingInvestment.inputs.2.main.gracePeriod.value.required' => $fieldIsRequired,
    'attractingInvestment.inputs.2.main.gracePeriod.value.integer'  => $maxIntegerMessage,
    'attractingInvestment.inputs.2.main.gracePeriod.value.max'      => 'Не более :max месяцев',
    'attractingInvestment.inputs.2.main.annualRate.value.required'  => $fieldIsRequired,
    'attractingInvestment.inputs.2.main.annualRate.value.numeric'   => 'Поле должно быть числом',

    'attractingInvestment.inputs.2.workingCapital.annualRate.value.required'      => $fieldIsRequired,
    'attractingInvestment.inputs.2.workingCapital.gracePeriod.value.required'     => $fieldIsRequired,
    'attractingInvestment.inputs.2.workingCapital.replenishPeriod.value.required' => $fieldIsRequired,
    'attractingInvestment.inputs.2.workingCapital.annualRate.value.integer'       => $maxIntegerMessage,
    'attractingInvestment.inputs.2.workingCapital.gracePeriod.value.integer'      => $maxIntegerMessage,
    'attractingInvestment.inputs.2.workingCapital.replenishPeriod.value.integer'  => $maxIntegerMessage,

    'attractingInvestment.inputs.2.workingCapital.annualRate.value.min'      => "Число не менее :min %",
    'attractingInvestment.inputs.2.workingCapital.gracePeriod.value.min'     => "Число не менее :min месяцев",
    'attractingInvestment.inputs.2.workingCapital.replenishPeriod.value.min' => "Число не менее :min месяцев",

    'attractingInvestment.inputs.2.workingCapital.annualRate.value.max'           => "Не более :max %",
    'attractingInvestment.inputs.2.workingCapital.gracePeriod.value.max'          => "Не более :max месяцев",
    'attractingInvestment.inputs.2.workingCapital.replenishPeriod.value.max'      => "Не более :max месяцев",

    'salesStartDate.value.required' => 'Дата не выбрана',
    'plannedVolume.value.required'  => 'Дата не выбрана',

    'otherIncome.value.integer' => $maxIntegerMessage,

    'plannedCapacity.inputs.*.value.integer' => $maxIntegerMessage,
    'plannedCapacity.inputs.*.value.max'     => 'Число не должно быть более :max',

    'administrativeExpenses.inputs.required'        => 'Не заполнено ни одного пункта',
    'administrativeExpenses.inputs.array'           => 'Ожидается массив данных',
    'administrativeExpenses.inputs.required_all'    => 'Не все поля заполнены',
    'administrativeExpenses.inputs.*.name.max'      => 'Максимум :max символов',
    'administrativeExpenses.inputs.*.value.integer' => $maxIntegerMessage,
    'administrativeExpenses.inputs.*.value.max'     => 'Число не должно превышать значение выше :max',

    'marketingExpenses.inputs.required'        => 'Не заполнено ни одного пункта',
    'marketingExpenses.inputs.array'           => 'Ожидается массив данных',
    'marketingExpenses.inputs.required_all'    => 'Не все поля заполнены',
    'marketingExpenses.inputs.*.name.max'      => 'Максимум :max символов',
    'marketingExpenses.inputs.*.value.integer' => $maxIntegerMessage,
    'marketingExpenses.inputs.*.value.max'     => 'Число не должно превышать значение выше :max',

    'variableCosts.variableCostsOther.inputs'                     => 'Таблица не заполнена',
    'variableCosts.variableCostsOther.inputs.*.title.required_if' => 'Наименование не указано',
    'variableCosts.variableCostsOther.inputs.*.title.max'         => 'Не более :max символов',
    'variableCosts.variableCostsOther.inputs.*.value.required_if' => 'Затраты не указаны',
    'variableCosts.variableCostsOther.inputs.*.value.integer'     => 'Значение должно быть числовым',
    'variableCosts.variableCostsOther.inputs.*.value.max'         => 'Не должно быть выше :max',
    'variableCosts.variableCostsOther.inputs.*.period.max'        => 'Не более :max',

    'utilityCosts.value.integer' => $maxIntegerMessage,

    'volumeSales.value.required' => 'Таблица не заполнена',
    'volumeSales.value.min'      => 'Таблица не заполнена',

    'equipmentList.value.required' => 'Таблица не заполнена',
    'equipmentList.value.min'      => 'Таблица не заполнена',

    'salaryFund.value.required' => 'Таблица не заполнена',
    'salaryFund.value.min'      => 'Таблица не заполнена',

    'salesStartDate.value.min_planning'            => 'Дата не должна быть ниже начальной даты планирования',
    'plannedVolume.value.max_planning'             => 'Дата не должна превышать конечную дату планирования',
    'readyPremisesList.value.price_ready_premises' => 'Сумма приобретаемой недвижимости не совпадает с суммой, указанной ранее в "Помещения, здания, сооружения"',
    'equipmentList.value.price_equipment'          => 'Сумма приобретаемого оборудования не совпадает с суммой, указанной ранее в "Приобретение оборудования, техники, мебели"',
    'smrList.value.price_smr'                      => 'Сумма перечня СМР не совпадает с заявленной на предыдущем шаге',
    'overhaulList.value.price_overhaul'            => 'Сумма перечня материалов по капитальному ремонту не совпадает с заявленной на предыдущем шаге',

    'price_smr'            => 'smr',
    'price_overhaul'       => 'overhaul',
    'price_equipment'      => 'equipment',
    'price_ready_premises' => 'readyPremises',
];
