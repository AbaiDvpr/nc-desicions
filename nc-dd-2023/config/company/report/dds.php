<?php declare(strict_types=1);

return [
    'netProfit'                                => [
        'name'         => 'Чистая прибыль',
        'data'         => [],
        'isSelected'   => false,
        'isShowChart'  => false,
        'chartOptions' => [
            'type'    => 'column',
            'group'   => 'netProfit',
            'ncColor' => '#54AD97',
        ],
    ],
    'increaseInNetWorkingCapital'              => [
        'name'         => 'Прирост чистого оборотного капитала',
        'data'         => [],
        'isSelected'   => false,
        'isShowChart'  => false,
        'chartOptions' => [
            'type'    => 'column',
            'group'   => 'increaseInNetWorkingCapital',
            'ncColor' => '#54AD97',
        ],
    ],
    'cashFlowsFromOperatingActivities'         => [
        'name'         => 'Денежные потоки от операционной деятельности',
        'data'         => [],
        'isSelected'   => true,
        'isShowChart'  => true,
        'chartOptions' => [
            'type'    => 'column',
            'group'   => 'cashFlowsFromOperatingActivities',
            'ncColor' => '#54AD97',
        ],
    ],
    'investments'                              => [
        'name'         => 'Инвестиции',
        'data'         => [],
        'isSelected'   => false,
        'isShowChart'  => false,
        'chartOptions' => [
            'type'    => 'column',
            'group'   => 'investments',
            'ncColor' => '#141629',
        ],
    ],
    'cashFlowsFromInvestingActivities'         => [
        'name'         => 'Денежные потоки от инвестиционной деятельности',
        'data'         => [],
        'isSelected'   => true,
        'isShowChart'  => true,
        'chartOptions' => [
            'type'    => 'column',
            'group'   => 'cashFlowsFromInvestingActivities',
            'ncColor' => '#3293A3',
        ],
    ],
    'receiptOrRepaymentOfFinancialLiabilities' => [
        'name'         => 'Поступление/погашение финансовых обязательств',
        'data'         => [],
        'isSelected'   => false,
        'isShowChart'  => false,
        'chartOptions' => [
            'type'    => 'column',
            'group'   => 'receiptOrRepaymentOfFinancialLiabilities',
            'ncColor' => '#54AD97',
        ],
    ],
    'issueOfShares'                            => [
        'name'         => 'Эмиссия акций',
        'data'         => [],
        'isSelected'   => false,
        'isShowChart'  => false,
        'chartOptions' => [
            'type'    => 'column',
            'group'   => 'issueOfShares',
            'ncColor' => '#54AD97',
        ],
    ],
    'cashFlowsFromFinancingActivities'         => [
        'name'         => 'Денежные потоки от финансовой деятельности',
        'data'         => [],
        'isSelected'   => true,
        'isShowChart'  => true,
        'chartOptions' => [
            'type'    => 'column',
            'group'   => 'cashFlowsFromFinancingActivities',
            'ncColor' => '#695BD6',
        ],
    ],
    'totalChangeInCash'                        => [
        'name'         => 'Итого изменение денежных средств',
        'data'         => [],
        'isSelected'   => true,
        'isShowChart'  => true,
        'chartOptions' => [
            'type'    => 'column',
            'group'   => 'totalChangeInCash',
            'ncColor' => '#FBC104',
        ],
    ],
    'impactOfExchangeRatesAgainstTenge'        => [
        'name'         => 'Влияние обменных курсов валют к тенге',
        'data'         => [],
        'isSelected'   => true,
        'isShowChart'  => true,
        'chartOptions' => [
            'type'    => 'column',
            'group'   => 'impactOfExchangeRatesAgainstTenge',
            'ncColor' => '#324da3',
        ],
    ],
    'cashAtBeginningPeriod'                    => [
        'name'         => 'Денежные средства на начало периода',
        'data'         => [],
        'isSelected'   => false,
        'isShowChart'  => false,
        'chartOptions' => [
            'type'    => 'column',
            'group'   => 'cashAtBeginningPeriod',
            'ncColor' => '#54AD97',
        ],
    ],
    'cashAtEndPeriod'                          => [
        'name'         => 'Денежные средства на конец периода',
        'data'         => [],
        'isSelected'   => false,
        'isShowChart'  => false,
        'chartOptions' => [
            'type'    => 'column',
            'group'   => 'cashAtEndPeriod',
            'ncColor' => '#324da3',
        ],
    ],
];
