<?php declare(strict_types=1);

namespace App\Components\Report\Sheets;

use App\Components\Report\Sheets\Interfaces\SheetInterface;
use App\Models\Project\AttractingInvestment;
use App\Models\Project\WorkingCapital;
use Carbon\Carbon;
use PhpOffice\PhpSpreadsheet\Calculation\Financial\CashFlow\Constant\Periodic\InterestAndPrincipal;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Calculation\Financial\CashFlow\Constant\Periodic\Payments;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Color;

/**
 * Класс для работы с Графиком пополнения оборотного капитала
 */
class WorkingCapitalSheet extends AbstractReportSheet implements SheetInterface
{

    /**
     * Строка с которой необходимо начать запись в файл
     */
    const START_ROW = 18;

    /**
     * Номер текущего листа
     */
    const CURRENT_SHEET = 12;

    /**
     * Текущий активный лист
     *
     * @var \PhpOffice\PhpSpreadsheet\Worksheet\Worksheet
     */
    protected $sheet;

    /**
     * @inheritdoc
     *
     * @return \App\Components\Report\Sheets\WorkingCapitalSheet
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     */
    public function generate(): self
    {
        $this->setActiveSheet(self::CURRENT_SHEET);

        $this->sheet    = $this->getActiveSheet();
        $workingCapital = $this->project->workingCapital;

        if ($this->project->isWorkingCapital() && !is_null($workingCapital)) {
            $this->sheet->setCellValue('D4', $this->report->totalWorkingCapital);
            $this->sheet->setCellValue('D6', $workingCapital->getAnnualRate() . '%');
            $this->sheet->setCellValue('D8', $workingCapital->getReplenishPeriod());

            $this->sheet->getStyle('D4:D15')->applyFromArray([
                'numberFormat' => [
                    'formatCode' => '#,##0'
                ],
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_RIGHT,
                ],
            ]);

            $monthlyPayments = $this->getAnnuity($workingCapital);
            $totalPayments   = $monthlyPayments * $workingCapital->getReplenishPeriod();

            $this->sheet->setCellValue('D10', $monthlyPayments);
            $this->sheet->setCellValue('D12', $totalPayments);
            $this->sheet->setCellValue('D14', $totalPayments - $this->report->totalWorkingCapital);

            $item                 = 1;
            $tableTotalPayments[] = $totalPayments;
            $planingFrom          = Carbon::parse($this->project->getPlanning()['from'] ?? Carbon::now());

            for ($row = self::START_ROW; $row < ($workingCapital->getReplenishPeriod() + self::START_ROW); $row++) {
                $loanRepayment = $this->report->totalWorkingCapital / $workingCapital->calculateGracePeriod();

                if ($workingCapital->getGracePeriod() && $workingCapital->getGracePeriod() >= $item) {
                    $loanRepayment = 0;
                }

                $this->sheet->setCellValue('B' . $row, $item);
                $this->sheet->setCellValue('C' . $row, $loanRepayment);

                $interestPayment      = $this->getInterestAndPrincipal($workingCapital, $item);
                $totalPayout          = $loanRepayment + $interestPayment;
                $remainingToPay       = $tableTotalPayments[$item - 1] - $totalPayout;
                $date                 = $planingFrom->addMonths($item === 1 ? 0 : 1)->format('d.m.Y');
                $tableTotalPayments[] = $remainingToPay;

                $this->sheet->setCellValue('D' . $row, $interestPayment);
                $this->sheet->setCellValue('E' . $row, $totalPayout);
                $this->sheet->setCellValue('F' . $row, $remainingToPay);
                $this->sheet->setCellValue('G' . $row, $date);

                $this->report->paymentWorkingCapital[$date] = [
                    'B' => $item,
                    'C' => $loanRepayment,
                    'D' => $interestPayment,
                    'E' => $totalPayout,
                    'F' => $remainingToPay,
                    'G' => $date,
                ];

                ++$item;
            }

            $this->sheet->getStyle('B' . self::START_ROW . ':G' . ($item + 16))->applyFromArray([
                'numberFormat' => [
                    'formatCode' => '#,##0'
                ],
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_RIGHT,
                ],
            ]);
        } else {
            $this->spreadsheet->removeSheetByIndex(self::CURRENT_SHEET);
        }


        return $this;
    }

    /**
     * @param  \App\Models\Project\WorkingCapital $workingCapital
     * @return float|int
     */
    private function getAnnuity(WorkingCapital $workingCapital)
    {
        return -Payments::annuity(
            ($workingCapital->getAnnualRate() / 100) / 12,
            $workingCapital->getReplenishPeriod() ?? 36,
            $this->report->totalWorkingCapital
        );
    }

    /**
     * @param  \App\Models\Project\WorkingCapital $workingCapital
     * @param  int                  $item
     * @return float|int
     */
    private function getInterestAndPrincipal(WorkingCapital $workingCapital, int $item = 1)
    {
        return -(new InterestAndPrincipal(
            ($workingCapital->getAnnualRate() / 100) / 12,
            $item,
            $workingCapital->getReplenishPeriod(),
            $this->report->totalWorkingCapital
        ))->interest();
    }
}
