<?php declare(strict_types=1);

namespace App\Components\Report\Sheets;

use App\Components\Report\Sheets\Interfaces\SheetInterface;
use App\Components\Report\Sheets\PaymentSchedule\Annuity;
use App\Components\Report\Sheets\PaymentSchedule\Equal;
use App\Models\Project\AttractingInvestment;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Calculation\Financial\CashFlow\Constant\Periodic\Payments;

class PaymentScheduleSheet extends AbstractReportSheet implements SheetInterface
{

    /**
     * Строка с которой необходимо начать запись в файл
     */
    const START_ROW = 18;

    /**
     * Номер текущего листа
     */
    const CURRENT_SHEET = 11;

    /**
     * Текущий активный лист
     *
     * @var \PhpOffice\PhpSpreadsheet\Worksheet\Worksheet
     */
    protected $sheet;

    /**
     * Сумма гранта без учета суммы которая на пополнение оборотного капитала
     * (при условии, что соответствующие поля заполнены)
     *
     * @var int
     */
    private $financingGrant = 0;

    /**
     * @inheritdoc
     *
     * @return \App\Components\Report\Sheets\PaymentScheduleSheet
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     */
    public function generate(): self
    {
        $this->setActiveSheet(self::CURRENT_SHEET);
        $this->sheet          = $this->getActiveSheet();
        $this->financingGrant = ($this->report->financingTotal['grant'] ?? 0) - $this->report->totalWorkingCapital;

        if ($this->project->isCreditFinancing() && $this->financingGrant > 0) {
            $investment      = $this->project->attractingInvestment;
            $paymentSchedule = $investment->isAnnuity()
                ? new Annuity($this->project, $this->financingGrant)
                : new Equal($this->project, $this->financingGrant);

            $this->sheet->setCellValue('D4', $this->financingGrant);
            $this->sheet->setCellValue('D6', $investment->annualRate . '%');
            $this->sheet->setCellValue('D8', $investment->getLoanTerm());

            $this->sheet->getStyle('D4:D15')->applyFromArray([
                'numberFormat' => [
                    'formatCode' => '#,##0'
                ],
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_RIGHT,
                ],
            ]);

            $this->sheet->setCellValue('D10', $this->getAnnuity($investment));
            $this->sheet->setCellValue('D12', $paymentSchedule->getTotalPayments());
            $this->sheet->setCellValue('D14', $paymentSchedule->getOverpayment());

            $this->sheet->fromArray(
                $paymentSchedule->getCalendar(),
                null,
                'B' . self::START_ROW,
                true
            );

            $this->report->paymentSchedule = $paymentSchedule->getCalendarByDate();

            $this->sheet->getStyle('B' . self::START_ROW . ':G' . (self::START_ROW + count($paymentSchedule->getCalendar())))->applyFromArray([
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
     * @param  AttractingInvestment $investment
     * @return float
     */
    private function getAnnuity(AttractingInvestment $investment): float
    {
        return -Payments::annuity(
            ($investment->annualRate / 100) / 12,
            $investment->getLoanTerm(),
            $this->financingGrant
        );
    }
}
