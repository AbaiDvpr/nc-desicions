<?php declare(strict_types=1);

$chooseOneOption = 'Выберите один из вариантов';

return [
    'uuid.required'       => 'Не указан uuid компании',
    'name.required'       => 'Наименование компании не указано',
    'name.min'            => 'Поле не может быть менее :min символов',
    'name.max'            => 'Поле не может быть больше :max символов',
    'form.required'       => $chooseOneOption,
    'form.in'             => $chooseOneOption,
    'currency.required'   => $chooseOneOption,
    'currency.in'         => $chooseOneOption,
    'tax_regime.required' => $chooseOneOption,
    'tax_regime.in'       => $chooseOneOption,
    'nds.required'        => $chooseOneOption,
    'nds.in'              => $chooseOneOption,
    'description.min'     => 'Поле не может быть менее :min символов',
    'description.max'     => 'Поле не может быть больше :max символов',
];
