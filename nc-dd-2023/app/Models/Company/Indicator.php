<?php declare(strict_types=1);

namespace App\Models\Company;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Presenters\Company\Indicator as IndicatorData;

/**
 * Сущность для хранения показателей компании
 */
class Indicator extends Model
{
    use SoftDeletes;

    /**
     * @inheritdoc
     *
     * @var array
     */
    protected $guarded = [];

    /**
     * @inheritdoc
     *
     * @var string
     */
    protected $table = 'companies-indicators';

    /**
     * @var $presenter
     */
    protected $presenter;

    /**
     * Презентер
     *
     * @return \App\Models\Presenters\Company\Indicator
     */
    public function getPresenter(): IndicatorData
    {
        if (!$this->presenter) {
            $this->presenter = new IndicatorData($this);
        }

        return $this->presenter;
    }

    /**
     * Возвращает ID
     *
     * @return int
     */
    public function getId(): int
    {
        return (int) $this->getAttribute('id');
    }

    /**
     * Устанавливает uuid компании
     *
     * @param  string                       $uuid
     * @return \App\Models\Company\Indicator
     */
    public function setCompanyUuid(string $uuid): static
    {
        $this->setAttribute('companyUuid', $uuid);

        return $this;
    }

    /**
     * Возвращает uuid компании
     *
     * @return string
     */
    public function getCompanyUuid(): string
    {
        return (string) $this->getAttribute('companyUuid');
    }

    /**
     * Устанавливает долю переменных затрат
     *
     * @param  float                        $proportionVariableCosts
     * @return \App\Models\Company\Indicator
     */
    public function setProportionVariableCosts(float $proportionVariableCosts): static
    {
        $this->setAttribute('proportionVariableCosts', $proportionVariableCosts);

        return $this;
    }

    /**
     * Возвращает долю переменных затрат
     *
     * @return float
     */
    public function getProportionVariableCosts(): float
    {
        return (float) $this->getAttribute('proportionVariableCosts');
    }

    /**
     * Устанавливает потребность в долгосрочных активах на тенге выручки
     *
     * @param  float                        $longTermAssets
     * @return \App\Models\Company\Indicator
     */
    public function setLongTermAssets(float $longTermAssets): static
    {
        $this->setAttribute('longTermAssets', $longTermAssets);

        return $this;
    }

    /**
     * Возвращает потребность в долгосрочных активах на тенге выручки
     *
     * @return float
     */
    public function getLongTermAssets(): float
    {
        return (float) $this->getAttribute('longTermAssets');
    }

    /**
     * Устанавливает потребность в оборотном капитале на тенге выручки
     *
     * @param  float                        $workingCapital
     * @return \App\Models\Company\Indicator
     */
    public function setWorkingCapital(float $workingCapital): static
    {
        $this->setAttribute('workingCapital', $workingCapital);

        return $this;
    }

    /**
     * Возвращает потребность в оборотном капитале на тенге выручки
     *
     * @return float
     */
    public function getWorkingCapital(): float
    {
        return (float) $this->getAttribute('workingCapital');
    }

    /**
     * Устанавливает потребность в денежных средства на тенге выручки
     *
     * @param  float                        $needCash
     * @return \App\Models\Company\Indicator
     */
    public function setNeedCash(float $needCash): static
    {
        $this->setAttribute('needCash', $needCash);

        return $this;
    }

    /**
     * Возвращает потребность в денежных средства на тенге выручки
     *
     * @return float
     */
    public function getNeedCash(): float
    {
        return (float) $this->getAttribute('needCash');
    }

    /**
     * Устанавливает процентную ставку по кредитам (в % от объема кредита на конец периода)
     *
     * @param  float                        $interestRateOnLoans
     * @return \App\Models\Company\Indicator
     */
    public function setInterestRateOnLoans(float $interestRateOnLoans): static
    {
        $this->setAttribute('interestRateOnLoans', $interestRateOnLoans);

        return $this;
    }

    /**
     * Возвращает процентную ставку по кредитам (в % от объема кредита на конец периода)
     *
     * @return float
     */
    public function getInterestRateOnLoans(): float
    {
        return (float) $this->getAttribute('interestRateOnLoans');
    }

    /**
     * Устанавливает ставку налога на прибыль
     *
     * @param  float                        $incomeTaxRate
     * @return \App\Models\Company\Indicator
     */
    public function setIncomeTaxRate(float $incomeTaxRate): static
    {
        $this->setAttribute('incomeTaxRate', $incomeTaxRate);

        return $this;
    }

    /**
     * Возвращает ставку налога на прибыль
     *
     * @return float
     */
    public function getIncomeTaxRate(): float
    {
        return (float) $this->getAttribute('incomeTaxRate');
    }

    /**
     * Устанавливает коэффициент дивидендных выплат
     *
     * @param  float                        $dividendPayoutRatio
     * @return \App\Models\Company\Indicator
     */
    public function setDividendPayoutRatio(float $dividendPayoutRatio): static
    {
        $this->setAttribute('dividendPayoutRatio', $dividendPayoutRatio);

        return $this;
    }

    /**
     * Возвращает коэффициент дивидендных выплат
     *
     * @return float
     */
    public function getDividendPayoutRatio(): float
    {
        return (float) $this->getAttribute('dividendPayoutRatio');
    }

    /**
     * Устанавливает индекс инфляции (темп прироста постоянных расходов)
     *
     * @param  float                        $inflationIndex
     * @return \App\Models\Company\Indicator
     */
    public function setInflationIndex(float $inflationIndex): static
    {
        $this->setAttribute('inflationIndex', $inflationIndex);

        return $this;
    }

    /**
     * Возвращает индекс инфляции (темп прироста постоянных расходов)
     *
     * @return float
     */
    public function getInflationIndex(): float
    {
        return (float) $this->getAttribute('inflationIndex');
    }

    /**
     * Устанавливает темп прироста выручки (REV/REV (-1) - 1)
     *
     * @param  float                        $revenueGrowthRate
     * @return \App\Models\Company\Indicator
     */
    public function setRevenueGrowthRate(float $revenueGrowthRate): static
    {
        $this->setAttribute('revenueGrowthRate', $revenueGrowthRate);

        return $this;
    }

    /**
     * Возвращает темп прироста выручки (REV/REV (-1) - 1)
     *
     * @return float
     */
    public function getRevenueGrowthRate(): float
    {
        return (float) $this->getAttribute('revenueGrowthRate');
    }

    /**
     * Устанавливает объем привлекаемого/погашаемого кредита в n периоде
     *
     * @param  float                        $loanVolume
     * @return \App\Models\Company\Indicator
     */
    public function setLoanVolume(float $loanVolume): static
    {
        $this->setAttribute('loanVolume', $loanVolume);

        return $this;
    }

    /**
     * Возвращает объем привлекаемого/погашаемого кредита в n периоде
     *
     * @return float
     */
    public function getLoanVolume(): float
    {
        return (float) $this->getAttribute('loanVolume');
    }

    /**
     * Устанавливает приобретение активов
     *
     * @param  float                        $acquisitionAssets
     * @return \App\Models\Company\Indicator
     */
    public function setAcquisitionAssets(float $acquisitionAssets): static
    {
        $this->setAttribute('acquisitionAssets', $acquisitionAssets);

        return $this;
    }

    /**
     * Возвращает приобретение активов
     *
     * @return float
     */
    public function getAcquisitionAssets(): float
    {
        return (float) $this->getAttribute('acquisitionAssets');
    }
}
