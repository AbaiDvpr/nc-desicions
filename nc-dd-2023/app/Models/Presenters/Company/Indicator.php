<?php declare(strict_types=1);

namespace App\Models\Presenters\Company;

use App\DI;
use App\Models\Company\Indicator as IndicatorModel;

/**
 * Презентер для обработки данных показателей компаний
 */
class Indicator
{
    /**
     * @var \App\Models\Company\Indicator
     */
    private IndicatorModel $indicator;

    /**
     * Конструктор
     *
     * @param \App\Models\Company\Indicator $indicator
     */
    public function __construct(IndicatorModel $indicator)
    {
        $this->indicator = $indicator;
    }

    /**
     * Возвращает публичные данные показателей
     *
     * @return array
     */
    public function getPublicData(): array
    {
        return [
            'id'     => $this->indicator->getId(),
            'fields' => [
                'proportionVariableCosts' => $this->indicator->getProportionVariableCosts(),
                'longTermAssets'          => $this->indicator->getLongTermAssets(),
                'workingCapital'          => $this->indicator->getWorkingCapital(),
                'needCash'                => $this->indicator->getNeedCash(),
                'interestRateOnLoans'     => $this->indicator->getInterestRateOnLoans(),
                'incomeTaxRate'           => $this->indicator->getIncomeTaxRate(),
                'dividendPayoutRatio'     => $this->indicator->getDividendPayoutRatio(),
                'inflationIndex'          => $this->indicator->getInflationIndex(),
                'revenueGrowthRate'       => $this->indicator->getRevenueGrowthRate(),
                'loanVolume'              => $this->indicator->getLoanVolume(),
                'acquisitionAssets'       => $this->indicator->getAcquisitionAssets(),
            ],
        ];
    }
}
