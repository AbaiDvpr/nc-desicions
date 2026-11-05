<?php declare(strict_types=1);

use App\Models\Feedback;

/**
 * Конфиг для формы Обратная связь
 */
return [
    // Статусы
    'statuses' => [
        Feedback::STATUS_NEW          => 'Новая',
        Feedback::STATUS_IN_PROCESS   => 'В работе',
        Feedback::STATUS_CONSULTATION => 'Консультация',
        Feedback::STATUS_DENIED       => 'Отказано',
        Feedback::STATUS_TARGET       => 'Целевой',
    ],
];
