<?php declare(strict_types=1);

use App\Models\Tariff;

/**
 * Конфиг для тарифов
 */
return [
    // Типы тарифов
    'types' => [
        Tariff::TYPE_MONTHLY => 'Ежемесячный',
        Tariff::TYPE_ANNUAL  => 'Годовой',
    ],
];

