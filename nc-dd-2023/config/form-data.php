<?php declare(strict_types=1);

/**
 * Поля формы для личного кабинета
 */

use App\Forms\Company\Base as CompanyBaseForm;
use App\Models\Company\Balance as BalanceModel;
use App\Models\CoreModel;
use App\Models\Tariff;
use App\Models\Handbook;
use App\Models\Project;

return [
    // Валюта расчётов
    'currencies' => [
        'kzt' => 'тенге',
        'usd' => 'долл.USD',
        'eur' => 'EUR'
    ],

    // Организационно-правовая форма
    'forms' => [
        1 => 'ИП',
        2 => 'ТОО'
    ],

    // Статусы проекта
    'projectStatuses' => [
        Project::STATUS_NEW       => 'Новый',
        Project::STATUS_EDITED    => 'Редактируется',
        Project::STATUS_COMPLETED => 'Завершенные',
    ],

    // Поля для форм компаний
    'company' => [
        // Виды операций со средствами
        'operationList' => [
            [
                'value' => 'income',
                'title' => 'Доходы',
            ],
            [
                'value' => 'outcome',
                'title' => 'Расходы',
            ],
            [
                'value' => 'active',
                'title' => 'Активы / Пассивы',
            ]
        ],

        // Поступления средств
        'accountCashList' => [
            [
                'value' => CompanyBaseForm::CASH_CHECKOUT,
                'title' => 'Наличными в кассу',
            ],
            [
                'value' => CompanyBaseForm::BANK_TRANSFER,
                'title' => 'Банковский перевод',
            ],
            [
                'value' => CompanyBaseForm::TMZ_SERVICE,
                'title' => 'ТМЗ / Услуга',
            ],
        ],

        // Список видов деятельности
        'kindActivityList' => [
            [
                'value' => CompanyBaseForm::OPERATING_ACTIVITY,
                'title' => 'Операционная деятельность',
            ],
            [
                'value' => CompanyBaseForm::FINANCIAL_ACTIVITY,
                'title' => 'Финансовая деятельность',
            ],
            [
                'value' => CompanyBaseForm::INVESTMENT_ACTIVITY,
                'title' => 'Инвестиционная деятельность',
            ],
        ],

        // Список для формы активов и пассивов
        'activeList' => [
            // Актив
            [
                'value' => CompanyBaseForm::ACTIVE_LONG_TERM_ASSETS,
                'title' => 'Долгосрочные Активы',
            ],
            [
                'value' => CompanyBaseForm::ACTIVE_SHORT_TERM_ASSETS,
                'title' => 'Краткосрочные Активы',
            ],

            // Пассив
            [
                'value' => CompanyBaseForm::ACTIVE_CAPITAL,
                'title' => 'Капитал',
            ],
            [
                'value' => CompanyBaseForm::ACTIVE_LONG_TERM_DUTIES,
                'title' => 'Долгосрочные Обязательства',
            ],
            [
                'value' => CompanyBaseForm::ACTIVE_SHORT_TERM_LIABILITIES,
                'title' => 'Краткосрочные Обязательства',
            ],
        ],

        // Тип активов или пассивов
        'activeTypeList' => [
            // поля для долгосрочных активов
            CompanyBaseForm::ACTIVE_LONG_TERM_ASSETS => [
                [
                    'value' => BalanceModel::NC_110,
                    'title' => 'Долгосрочные финансовые активы, оцениваемые по амортизированной стоимости',
                ],
                [
                    'value' => BalanceModel::NC_111,
                    'title' => 'Долгосрочные финансовые активы, оцениваемые по справедливой стоимости через прочий совокупный доход',
                ],
                [
                    'value' => BalanceModel::NC_112,
                    'title' => 'Долгосрочные финансовые активы, учитываемые по справедливой стоимости через прибыли или убытки',
                ],
                [
                    'value' => BalanceModel::NC_113,
                    'title' => 'Долгосрочные производные финансовые инструменты',
                ],
                [
                    'value' => BalanceModel::NC_114,
                    'title' => 'Инвестиции, учитываемые по первоначальной стоимости ',
                ],
                [
                    'value' => BalanceModel::NC_115,
                    'title' => 'Инвестиции, учитываемые методом долевого участия',
                ],
                [
                    'value' => BalanceModel::NC_116,
                    'title' => 'Прочие долгосрочные финансовые активы',
                ],
                [
                    'value' => BalanceModel::NC_117,
                    'title' => 'Долгосрочная торговая и прочая дебиторская задолженность',
                ],
                [
                    'value' => BalanceModel::NC_118,
                    'title' => 'Долгосрочная дебиторская задолженность по аренде',
                ],
                [
                    'value' => BalanceModel::NC_119,
                    'title' => 'Долгосрочные активы по договорам с покупателями ',
                ],
                [
                    'value' => BalanceModel::NC_120,
                    'title' => 'Инвестиционное имущество',
                ],
                [
                    'value' => BalanceModel::NC_121,
                    'title' => 'Основные средства',
                ],
                [
                    'value' => BalanceModel::NC_122,
                    'title' => 'Актив в форме права пользования',
                ],
                [
                    'value' => BalanceModel::NC_123,
                    'title' => 'Биологические активы',
                ],
                [
                    'value' => BalanceModel::NC_124,
                    'title' => 'Разведочные и оценочные активы',
                ],
                [
                    'value' => BalanceModel::NC_125,
                    'title' => 'Нематериальные активы',
                ],
                [
                    'value' => BalanceModel::NC_126,
                    'title' => 'Отложенные налоговые активы',
                ],
                [
                    'value' => BalanceModel::NC_127,
                    'title' => 'Прочие долгосрочные активы',
                ],
            ],

            // поля для краткосрочных активов
            CompanyBaseForm::ACTIVE_SHORT_TERM_ASSETS => [
                [
                    'value' => BalanceModel::NC_10,
                    'title' => 'Денежные средства и их эквиваленты',
                ],
                [
                    'value' => BalanceModel::NC_11,
                    'title' => 'Краткосрочные финансовые активы, оцениваемые по амортизированной стоимости',
                ],
                [
                    'value' => BalanceModel::NC_12,
                    'title' => 'Краткосрочные финансовые активы, оцениваемые по справедливой стоимости через прочий совокупный доход',
                ],
                [
                    'value' => BalanceModel::NC_13,
                    'title' => 'Краткосрочные финансовые активы, учитываемые по справедливой стоимости через прибыли или убытки',
                ],
                [
                    'value' => BalanceModel::NC_14,
                    'title' => 'Краткосрочные производные финансовые инструменты',
                ],
                [
                    'value' => BalanceModel::NC_15,
                    'title' => 'Прочие краткосрочные финансовые активы',
                ],
                [
                    'value' => BalanceModel::NC_16,
                    'title' => 'Краткосрочная торговая и прочая дебиторская задолженность',
                ],
                [
                    'value' => BalanceModel::NC_17,
                    'title' => 'Краткосрочная дебиторская задолженность по аренде',
                ],
                [
                    'value' => BalanceModel::NC_18,
                    'title' => 'Краткосрочные активы по договорам с покупателями',
                ],
                [
                    'value' => BalanceModel::NC_19,
                    'title' => 'Текущий подоходный налог',
                ],
                [
                    'value' => BalanceModel::NC_20,
                    'title' => 'Запасы',
                ],
                [
                    'value' => BalanceModel::NC_21,
                    'title' => 'Биологические активы',
                ],
                [
                    'value' => BalanceModel::NC_22,
                    'title' => 'Прочие краткосрочные активы',
                ],
            ],

            // поля для капитала
            CompanyBaseForm::ACTIVE_CAPITAL => [
                [
                    'value' => BalanceModel::NC_410,
                    'title' => 'Уставный (акционерный) капитал',
                ],
                [
                    'value' => BalanceModel::NC_411,
                    'title' => 'Эмиссионный доход',
                ],
                [
                    'value' => BalanceModel::NC_412,
                    'title' => 'Выкупленные собственные долевые инструменты',
                ],
                [
                    'value' => BalanceModel::NC_413,
                    'title' => 'Компоненты прочего совокупного дохода',
                ],
                [
                    'value' => BalanceModel::NC_414,
                    'title' => 'Нераспределенная прибыль (непокрытый убыток)',
                ],
                [
                    'value' => BalanceModel::NC_415,
                    'title' => 'Прочий капитал',
                ],
            ],

            // поля для долгосрочных обязательств
            CompanyBaseForm::ACTIVE_LONG_TERM_DUTIES => [
                [
                    'value' => BalanceModel::NC_310,
                    'title' => 'Долгосрочные финансовые обязательства, оцениваемые по амортизированной стоимости',
                ],
                [
                    'value' => BalanceModel::NC_311,
                    'title' => 'Долгосрочные финансовые обязательства, оцениваемые по справедливой стоимости через прибыль или убыток',
                ],
                [
                    'value' => BalanceModel::NC_312,
                    'title' => 'Долгосрочные производные финансовые инструменты',
                ],
                [
                    'value' => BalanceModel::NC_313,
                    'title' => 'Прочие долгосрочные финансовые обязательства',
                ],
                [
                    'value' => BalanceModel::NC_314,
                    'title' => 'Долгосрочная торговая и прочая кредиторская задолженность',
                ],
                [
                    'value' => BalanceModel::NC_315,
                    'title' => 'Долгосрочные оценочные обязательства',
                ],
                [
                    'value' => BalanceModel::NC_316,
                    'title' => 'Отложенные налоговые обязательства',
                ],
                [
                    'value' => BalanceModel::NC_317,
                    'title' => 'Вознаграждения работникам ',
                ],
                [
                    'value' => BalanceModel::NC_318,
                    'title' => 'Долгосрочная задолженность по аренде ',
                ],
                [
                    'value' => BalanceModel::NC_319,
                    'title' => 'Долгосрочные обязательства по договорам с покупателями ',
                ],
                [
                    'value' => BalanceModel::NC_320,
                    'title' => 'Государственные субсидии ',
                ],
                [
                    'value' => BalanceModel::NC_321,
                    'title' => 'Прочие долгосрочные обязательства',
                ],
            ],

            // поля для краткосрочных обязательств
            CompanyBaseForm::ACTIVE_SHORT_TERM_LIABILITIES => [
                [
                    'value' => BalanceModel::NC_210,
                    'title' => 'Краткосрочные финансовые обязательства, оцениваемые по амортизированной стоимости',
                ],
                [
                    'value' => BalanceModel::NC_211,
                    'title' => 'Краткосрочные финансовые обязательства, оцениваемые по справедливой стоимости через прибыль или убыток',
                ],
                [
                    'value' => BalanceModel::NC_212,
                    'title' => 'Краткосрочные производные финансовые инструменты ',
                ],
                [
                    'value' => BalanceModel::NC_213,
                    'title' => 'Прочие краткосрочные финансовые обязательства',
                ],
                [
                    'value' => BalanceModel::NC_214,
                    'title' => 'Краткосрочная торговая и прочая кредиторская задолженность',
                ],
                [
                    'value' => BalanceModel::NC_215,
                    'title' => 'Краткосрочные оценочные обязательства ',
                ],
                [
                    'value' => BalanceModel::NC_216,
                    'title' => 'Текущие налоговые обязательства по подоходному налогу ',
                ],
                [
                    'value' => BalanceModel::NC_217,
                    'title' => 'Вознаграждения работникам',
                ],
                [
                    'value' => BalanceModel::NC_218,
                    'title' => 'Краткосрочная задолженность по аренде',
                ],
                [
                    'value' => BalanceModel::NC_219,
                    'title' => 'Краткосрочные обязательства по договорам покупателями',
                ],
                [
                    'value' => BalanceModel::NC_220,
                    'title' => 'Государственные субсидии',
                ],
                [
                    'value' => BalanceModel::NC_221,
                    'title' => 'Дивиденды к оплате ',
                ],
                [
                    'value' => BalanceModel::NC_222,
                    'title' => 'Прочие краткосрочные обязательства',
                ],
            ]
        ],

        // Форма налогообложения
        'taxRegime' => [
            CoreModel::TAX_REGIME_GENERAL_PROCEDURE => 'Общеустановленный порядок',
            CoreModel::TAX_REGIME_SIMPLIFIED        => 'Упрощенная декларация',
        ],
    ],

    // Типы тарифов
    'tariffTypes' => [
        'monthly' => Tariff::TYPE_MONTHLY,
        'annual'  => Tariff::TYPE_ANNUAL
    ],

    // Типы справочника
    'handbookTypes' => [
        Handbook::TYPE_GROUP => 'Группа',
    ],
];
