<?php declare(strict_types=1);

namespace App\Components\Report\Sheets\PaymentSchedule;

use App\Models\Project;
use App\Models\Project\AttractingInvestment;
use Carbon\Carbon;
use PhpOffice\PhpSpreadsheet\Calculation\Financial\CashFlow\Constant\Periodic\InterestAndPrincipal;
use PhpOffice\PhpSpreadsheet\Calculation\Financial\CashFlow\Constant\Periodic\Payments;

/**
 * Аннуитетный платеж
 */
class Annuity
{
    /**
     * @var \App\Models\Project
     */
    protected Project $project;

    /**
     * Сумма финансирования
     *
     * @var float
     */
    protected float $financingGrant = 0;

    /**
     * Переплата
     *
     * @var float
     */
    protected float $overpayment = 0;

    /**
     * Календарь платежей
     *
     * @var array
     */
    protected array $calendar = [];

    /**
     * Календарь платежей по датам
     *
     * @var array
     */
    protected array $calendarByDate = [];

    /**
     * Конструктор
     *
     * @param \App\Models\Project $project
     * @param float               $financingGrant
     */
    public function __construct(Project $project, float $financingGrant)
    {
        $this->project        = $project;
        $this->financingGrant = $financingGrant;

        $this->generate();
    }

    /**
     * Формирование календаря платежей
     *
     * @return \App\Components\Report\Sheets\PaymentSchedule\Annuity
     */
    protected function generate(): self
    {
        $calendar           = [];
        $calendarByDate     = [];
        $planingFrom        = Carbon::parse($this->project->getPlanning()['from'] ?? Carbon::now());
        $leftFinancingGrant = $this->getFinancingGrant();
        $investment         = $this->project->attractingInvestment;
        $overpayment        = 0;

        for ($item = 1; $item <= $investment->getLoanTerm(); $item++) {
            $isGracePeriod      = $investment->gracePeriod && $investment->gracePeriod >= $item;
            $payments           = $this->getInterestAndPrincipal(
                $investment,
                $investment->gracePeriod ? max($item -  $investment->gracePeriod, 1) : $item
            );
            $loanRepayment      = $isGracePeriod ? 0 : $payments['principal'];
            $interestPayment    = $payments['interest'];
            $date               = $planingFrom->addMonths($item === 1 ? 0 : 1)->format('d.m.Y');
            $totalPayout        = abs($loanRepayment + $interestPayment);
            $leftToPay          = $isGracePeriod ? $leftFinancingGrant : $leftFinancingGrant - $loanRepayment;
            $leftFinancingGrant -= $loanRepayment;
            $overpayment        += $interestPayment;

            $calendar[] = [
                $item,
                $loanRepayment,
                $interestPayment,
                $totalPayout,
                $leftToPay,
                $date,
            ];

            $calendarByDate[$date] = [
                'B' => $item,
                'C' => $loanRepayment,
                'D' => $interestPayment,
                'E' => $totalPayout,
                'F' => $leftToPay,
                'G' => $date,
            ];
        }

        $this->calendar       = $calendar;
        $this->calendarByDate = $calendarByDate;
        $this->overpayment    = $overpayment;

        return $this;
    }

    /**
     * Возвращает календарь платежей
     *
     * @return array
     */
    public function getCalendar(): array
    {
        return $this->calendar;
    }

    /**
     * Возвращает календарь платежей по датам
     *
     * @return array
     */
    public function getCalendarByDate(): array
    {
        return $this->calendarByDate;
    }

    /**
     * Возвращает основные и процентные выплаты на ежемесячной основе
     *
     * @param  AttractingInvestment $investment
     * @param  int                  $item
     * @return array
     */
    private function getInterestAndPrincipal(AttractingInvestment $investment, int $item = 1): array
    {
        $payments = (new InterestAndPrincipal(
            ($investment->annualRate / 100) / 12,
            $item,
            $investment->getGracePeriod(),
            $this->getFinancingGrant()
        ));

        return [
            'interest'  => -$payments->interest(),
            'principal' => -$payments->principal(),
        ];
    }

    /**
     * Возвращает сумму финансирования
     *
     * @return float
     */
    public function getFinancingGrant(): float
    {
        return $this->financingGrant;
    }

    /**
     * Возвращает общую сумму выплат
     *
     * @return float
     */
    public function getTotalPayments(): float
    {
        return $this->getFinancingGrant() + $this->getOverpayment();
    }

    /**
     * Возвращает сумму переплаты
     *
     * @return float
     */
    public function getOverpayment(): float
    {
        return $this->overpayment;
    }
}
