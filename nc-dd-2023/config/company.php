<?php declare(strict_types = 1);

/**
 * Конфиги для компании в личном кабинете
 */

use App\Models\Company;

return [
    /**
     * Тип заполнения данных в ОПУ, ДДС и Баланс
     */
    'fillType' => [
        // Ручной ввод
        'input'  => 0,

        // Импорт файла
        'import' => 1,
    ],

    /**
     * Номера и наименование листов в импорт файле
     */
    'importSheet' => [
        'index' => [
            Company::TYPE_OPU     => 0,
            Company::TYPE_DDS     => 1,
            Company::TYPE_BALANCE => 2,
        ],
        'name'  => [
            Company::TYPE_OPU     => 'ОПУ',
            Company::TYPE_DDS     => 'ДДС',
            Company::TYPE_BALANCE => 'Баланс',
        ],
    ],
];
