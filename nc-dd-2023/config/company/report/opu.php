<?php declare(strict_types=1);

return [
    'income'                       => [
        'name'         => 'Выручка от реализации продукции',
        'data'         => [],
        'isSelected'   => true,
        'isShowChart'  => true,
        'chartOptions' => [
            'type'    => 'column',
            'group'   => 'income',
            'ncColor' => '#00B1F2',
        ],
    ],
    'costPrice'                    => [
        'name'         => 'Себестоимость',
        'data'         => [],
        'isSelected'   => false,
        'isShowChart'  => true,
        'chartOptions' => [
            'type'    => 'column',
            'group'   => 'costPrice',
            'ncColor' => '#695BD6',
        ],
    ],
    'generalAndAdminExpenses'      => [
        'name'         => 'ОиАР',
        'data'         => [],
        'isSelected'   => false,
        'isShowChart'  => false,
        'chartOptions' => [
            'type'    => 'column',
            'group'   => 'generalAndAdminExpenses',
            'ncColor' => '#54AD97',
        ],
    ],
    'profitBeforeTaxesAndPercents' => [
        'name'         => 'Прибыль до налогов и процентов',
        'data'         => [],
        'isSelected'   => true,
        'isShowChart'  => false,
        'chartOptions' => [
            'type'    => 'column',
            'group'   => 'profitBeforeTaxesAndPercents',
            'ncColor' => '#141629',
        ],
    ],
    'financialIncomeOrExpenses'    => [
        'name'         => 'Финансовые доходы/расходы',
        'data'         => [],
        'isSelected'   => false,
        'isShowChart'  => false,
        'chartOptions' => [
            'type'    => 'column',
            'group'   => 'financialIncomeOrExpenses',
            'ncColor' => '#54AD97',
        ],
    ],
    'otherIncomeOrExpenses'        => [
        'name'         => 'Прочие доходы/расходы',
        'data'         => [],
        'isSelected'   => false,
        'isShowChart'  => false,
        'chartOptions' => [
            'type'    => 'column',
            'group'   => 'otherIncomeOrExpenses',
            'ncColor' => '#54AD97',
        ],
    ],
    'exchangeDifference'           => [
        'name'         => 'Курсовая разница',
        'data'         => [],
        'isSelected'   => false,
        'isShowChart'  => false,
        'chartOptions' => [
            'type'    => 'column',
            'group'   => 'exchangeDifference',
            'ncColor' => '#54AD97',
        ],
    ],
    'profitBeforeTaxes'            => [
        'name'         => 'Прибыль до налогов',
        'data'         => [],
        'isShowChart'  => false,
        'isSelected'   => true,
        'chartOptions' => [
            'type'    => 'column',
            'group'   => 'profitBeforeTaxes',
            'ncColor' => '#695BD6',
        ],
    ],
    'incomeTax'                    => [
        'name'         => 'Налог на прибыль',
        'data'         => [],
        'isSelected'   => false,
        'isShowChart'  => false,
        'chartOptions' => [
            'type'    => 'column',
            'group'   => 'incomeTax',
            'ncColor' => '#54AD97',
        ],
    ],
    'netProfit'                    => [
        'name'         => 'Чистая прибыль',
        'data'         => [],
        'isShowChart'  => true,
        'isSelected'   => true,
        'chartOptions' => [
            'type'    => 'area',
            'group'   => 'netProfit',
            'ncColor' => '#00E396',
        ],
    ],
];
