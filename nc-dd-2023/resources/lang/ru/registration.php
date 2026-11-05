<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Authentication Language Lines
    |--------------------------------------------------------------------------
    |
    | The following language lines are used during authentication for various
    | messages that we need to display to the user. You are free to modify
    | these language lines according to your application's requirements.
    |
    */

    'registration' => [
        'title' => 'Регистрация',

        'success' => '<b>Регистрация прошла успешно.</b> <br/>
                    Для завершения, мы отправили вам на почту письмо, следуйте инструкциям в письме.'
    ],

    'verification' => [
        'title' => 'Подтверждение',

        'error_title' => 'Ошибка',
        'error_description' => 'Произошла ошибка во время подтверждения. Обратитесь в службу поддержки.',

        'success' => '<p class="mb-0"><b>E-mail успешно подтвержден .</b></p>
                      <p class="mb-0"> Теперь вы можете войти в личный кабинет.</p>',
    ],

    'restore' => [
        'title' => 'Восстановление пароля',

        'success' => '<p class="mb-0">Ссылка для восстановления пароля отправлена на ваш e-mail.</p>',

        'password_updated' => 'Пароль успешно обновлен. Теперь вы можете авторизоваться.',
        'link_is_not_valid' => 'Ошибка, ссылка не действительна',
    ]
];
