<?php declare(strict_types=1);

namespace App\Models\Company\Forms;

use Illuminate\Http\Request;

class Indicator
{
    /**
     * uuid компании
     *
     * @var string
     */
    protected string $companyUuid;

    /**
     * Доля переменных затрат
     *
     * @var float
     */
    protected float $proportionVariableCosts;

    /**
     * Потребность в долгосрочных активах на тенге выручки
     *
     * @var float
     */
    protected float $longTermAssets;

    /**
     * Потребность в оборотном капитале на тенге выручки
     *
     * @var float
     */
    protected float $workingCapital;

    /**
     * Потребность в денежных средства на тенге выручки
     *
     * @var float
     */
    protected float $needCash;

    /**
     * Процентная ставка по кредитам (в % от объема кредита на конец периода)
     *
     * @var float
     */
    protected float $interestRateOnLoans;

    /**
     * Ставка налога на прибыль
     *
     * @var float
     */
    protected float $incomeTaxRate;

    /**
     * Коэффициент дивидендных выплат
     *
     * @var float
     */
    protected float $dividendPayoutRatio;

    /**
     * Индекс инфляции (темп прироста постоянных расходов)
     *
     * @var float
     */
    protected float $inflationIndex;

    /**
     * Темп прироста выручки (REV/REV (-1) - 1)
     *
     * @var float
     */
    protected float $revenueGrowthRate;

    /**
     * Объем привлекаемого/погашаемого кредита в n периоде
     *
     * @var float
     */
    protected float $loanVolume;

    /**
     * Приобретение активов
     *
     * @var float
     */
    protected float $acquisitionAssets;

    /**
     * Заполняет свойства данными из запроса
     *
     * @param  \Illuminate\Http\Request            $request
     * @return \App\Models\Company\Forms\Indicator
     */
    public static function createFromRequest(Request $request): Indicator
    {
        $form = new self();

        $form->companyUuid             = (string) $request->get('companyUuid');
        $form->proportionVariableCosts = (float) $request->get('proportionVariableCosts');
        $form->longTermAssets          = (float) $request->get('longTermAssets');
        $form->workingCapital          = (float) $request->get('workingCapital');
        $form->needCash                = (float) $request->get('needCash');
        $form->interestRateOnLoans     = (float) $request->get('interestRateOnLoans');
        $form->incomeTaxRate           = (float) $request->get('incomeTaxRate');
        $form->dividendPayoutRatio     = (float) $request->get('dividendPayoutRatio');
        $form->inflationIndex          = (float) $request->get('inflationIndex');
        $form->revenueGrowthRate       = (float) $request->get('revenueGrowthRate');
        $form->loanVolume              = (float) $request->get('loanVolume');
        $form->acquisitionAssets       = (float) $request->get('acquisitionAssets');

        return $form;
    }

    /**
     * Возвращает uuid компании
     *
     * @return string
     */
    public function getCompanyUuid(): string
    {
        return $this->companyUuid;
    }

    /**
     * Возвращает долю переменных затрат
     *
     * @return float
     */
    public function getProportionVariableCosts(): float
    {
        return $this->proportionVariableCosts;
    }

    /**
     * Возвращает потребность в долгосрочных активах на тенге выручки
     *
     * @return float
     */
    public function getLongTermAssets(): float
    {
        return $this->longTermAssets;
    }

    /**
     * Возвращает потребность в оборотном капитале на тенге выручки
     *
     * @return float
     */
    public function getWorkingCapital(): float
    {
        return $this->workingCapital;
    }

    /**
     * Возвращает потребность в денежных средства на тенге выручки
     *
     * @return float
     */
    public function getNeedCash(): float
    {
        return $this->needCash;
    }

    /**
     * Возвращает процентную ставку по кредитам (в % от объема кредита на конец периода)
     *
     * @return float
     */
    public function getInterestRateOnLoans(): float
    {
        return $this->interestRateOnLoans;
    }

    /**
     * Возвращает ставку налога на прибыль
     *
     * @return float
     */
    public function getIncomeTaxRate(): float
    {
        return $this->incomeTaxRate;
    }

    /**
     * Возвращает коэффициент дивидендных выплат
     *
     * @return float
     */
    public function getDividendPayoutRatio(): float
    {
        return $this->dividendPayoutRatio;
    }

    /**
     * Возвращает индекс инфляции (темп прироста постоянных расходов)
     *
     * @return float
     */
    public function getInflationIndex(): float
    {
        return $this->inflationIndex;
    }

    /**
     * Возвращает темп прироста выручки (REV/REV (-1) - 1)
     *
     * @return float
     */
    public function getRevenueGrowthRate(): float
    {
        return $this->revenueGrowthRate;
    }

    /**
     * Возвращает объем привлекаемого/погашаемого кредита в n периоде
     *
     * @return float
     */
    public function getLoanVolume(): float
    {
        return $this->loanVolume;
    }

    /**
     * Возвращает приобретение активов
     *
     * @return float
     */
    public function getAcquisitionAssets(): float
    {
        return $this->acquisitionAssets;
    }

    /**
     * Возвращает заполненную форму в виде массива
     *
     * @return array
     */
    public function toArray(): array
    {
        return [
            'proportionVariableCosts' => $this->getProportionVariableCosts(),
            'longTermAssets'          => $this->getLongTermAssets(),
            'workingCapital'          => $this->getWorkingCapital(),
            'needCash'                => $this->getNeedCash(),
            'interestRateOnLoans'     => $this->getInterestRateOnLoans(),
            'incomeTaxRate'           => $this->getIncomeTaxRate(),
            'dividendPayoutRatio'     => $this->getDividendPayoutRatio(),
            'inflationIndex'          => $this->getInflationIndex(),
            'revenueGrowthRate'       => $this->getRevenueGrowthRate(),
            'loanVolume'              => $this->getLoanVolume(),
            'acquisitionAssets'       => $this->getAcquisitionAssets(),
        ];
    }
}
