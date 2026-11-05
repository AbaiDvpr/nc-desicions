<?php declare(strict_types=1);

return [

    'name.required'                  => 'ФИО не указано',
    'name.min'                       => 'ФИО не должно быть менее :min символов',
    'email.required'                 => 'E-mail не заполнен',
    'email.email'                    => 'E-mail заполнен не верно',
    'email.unique'                   => 'E-mail уже зарегистрирован',
    'mobile.required'                => 'Номер телефона не указан',
    'mobile.regex'                   => 'Номер телефона указан не верно',
    'birthday.required'              => 'Дата рождения обязательна к заполнению',
    'birthday.before'                => 'Вам еще не исполнилось 18 лет',
    'password.required'              => 'Пароль не указан',
    'password.min'                   => 'Пароль должен быть не менее :min символов',
    'password.confirmed'             => 'Пароли не совпадают',
    'password_confirmation.required' => 'Подтвердите пароль',

];
