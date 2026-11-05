<?php declare(strict_types = 1);

namespace App\Components\Report\Sheets;

use App\Components\Report\Sheets\Interfaces\SheetInterface;
use PhpOffice\PhpSpreadsheet\Exception;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Класс для заполнения листа "Баланс"
 *
 * @package App\Components\Report\Sheets
 */
class BalanceSheet extends AbstractReportSheet implements SheetInterface
{
    /**
     * Номер текущего листа
     */
    const CURRENT_SHEET = 9;

    /**
     * Текущий активный лист
     *
     * @var Worksheet
     */
    protected $sheet;

    /**
     * @inheritdoc
     *
     * @return \App\Components\Report\Sheets\BalanceSheet
     * @throws Exception
     */
    public function generate(): self
    {
        $this->setActiveSheet(self::CURRENT_SHEET);
        $this->sheet = $this->getActiveSheet();
        $count       = 3;

        $this->setDatesToSheet('B1', 2, 1);

        $assetsTable = $this->getAssetsTable();
        $this->sheet->fromArray($assetsTable, null, 'A3', true);

        $count = $count + count($assetsTable);

        $liabilitiesTable = $this->getLiabilitiesTable();
        $this->sheet->fromArray($liabilitiesTable, null, 'A' . $count, true);

        $count = $count + count($liabilitiesTable);

        $balanceCheck = $this->getBalanceCheck($assetsTable['totalAssets'], $liabilitiesTable['totalLiabilities']);

        $this->sheet->fromArray($balanceCheck, null, 'A' . $count + 1, true);

        $count    = $count + count($balanceCheck) - 1;
        $lastCell = $this->sheet->getCellByColumnAndRow(count($balanceCheck), $count)->getCoordinate();

        $this->sheet->getStyle('B3:' . $lastCell)->applyFromArray([
            'numberFormat' => [
                'formatCode' => '#,##0',
            ],
            'alignment'    => [
                'horizontal' => Alignment::HORIZONTAL_RIGHT,
            ],
        ]);

        return $this;
    }

    /**
     * Получаем данные по активам
     *
     * @return array
     */
    protected function getAssetsTable(): array
    {
        $growthLongTermAssets   = $this->removeNamedColumns([$this->report->investmentActivities['totalCashFlow'] ?? []])[0] ?? [];
        $amortizations          = $this->report->amortization;
        $longTermAssetsCost     = [];
        $prevLongTermAssetsCost = ($growthLongTermAssets[0] ?? 0) * -1;
        $index                  = 0;


        foreach ($this->diffDates as $diffDate) {
            $amortizationsItem    = $amortizations[$index] ?? 0;
            $sum                  = $prevLongTermAssetsCost = $prevLongTermAssetsCost - $amortizationsItem;
            $longTermAssetsCost[] = $sum;

            if ($diffDate['isLast']) {
                $longTermAssetsCost[] = $sum;
                $index++;
            }

            $index++;
        }

        $cashAtEndPeriod = $this->report->cashAtEndPeriod;
        $cash            = [];
        $index           = 0;

        foreach ($this->diffDates as $diffDate) {
            $cash[] = $cashAtEndPeriod[$index];

            if ($diffDate['isLast']) {
                $cash[] = $cashAtEndPeriod[$index];
                $index++;
            }

            $index++;
        }

        $totalAssets = [];
        for ($i = 0; $i < count($longTermAssetsCost); $i++) {
            $longTermAssetsCostItem = $longTermAssetsCost[$i] ?? 0;
            $cashItem               = $cash[$i] ?? 0;
            $totalAssets[]          = $longTermAssetsCostItem + $cashItem;
        }

        return [
            'longTermAssetsCost' => $this->addNameToRow($longTermAssetsCost,
                'Долгосрочные активы, первоначальная стоимость'),
            'cash'               => $this->addNameToRow($cash, 'Денежные средства'),
            'totalAssets'        => $this->addNameToRow($totalAssets, 'Итого активы'),
        ];
    }

    /**
     * Получаем данные по пассивам
     *
     * @return array
     */
    protected function getLiabilitiesTable(): array
    {
        $foundersCapital   = $this->removeNamedColumns([$this->report->financialActivities['FOUNDER'] ?? []])[0] ?? [];
        $authorizedCapital = [$foundersCapital[0] ?? 0];

        for ($i = 1; $i < count($foundersCapital); $i++) {
            $authorizedCapital[] = $authorizedCapital[$i - 1];
        }

        $netProfit            = $this->removeNamedColumns([$this->report->netProfit['PROFIT'] ?? []])[0] ?? [];
        $retainedEarnings     = [];
        $prevRetainedEarnings = 0;
        $index                = 0;

        foreach ($this->diffDates as $diffDate) {
            $sum = $prevRetainedEarnings = $netProfit[$index] + $prevRetainedEarnings;

            $retainedEarnings[] = $sum;

            if ($diffDate['isLast'] ?? false) {
                $retainedEarnings[] = $sum;
                $index++;
            }

            $index++;
        }

        $borrowedCapital        = $this->removeNamedColumns([$this->report->financialActivities['BORROWED']])[0] ?? [];
        $financialOutflowsTotal = $this->removeNamedColumns([$this->report->financialOutflows['RETIREMENT']])[0] ?? [];
        $creditsAndLoans        = [];
        $prevCreditsAndLoans    = 0;
        $index                  = 0;

        foreach ($this->diffDates as $diffDate) {
            $borrowedCapitalItem        = $borrowedCapital[$index] ?? 0;
            $financialOutflowsTotalItem = $financialOutflowsTotal[$index] ?? 0;
            $sum                        = $prevCreditsAndLoans = $prevCreditsAndLoans + $borrowedCapitalItem - $financialOutflowsTotalItem;
            $creditsAndLoans[]          = $sum;

            if ($diffDate['isLast'] ?? false) {
                $creditsAndLoans[] = $sum;
                $index++;
            }
            $index++;
        }

        $totalLiabilities = [];

        for ($i = 0; $i < count($creditsAndLoans); $i++) {
            $authorizedCapitalItem = $authorizedCapital[$i] ?? 0;
            $retainedEarningsItem  = $retainedEarnings[$i] ?? 0;
            $creditsAndLoansItem   = $creditsAndLoans[$i] ?? 0;

            $totalLiabilities[] = $authorizedCapitalItem + $retainedEarningsItem + $creditsAndLoansItem;
        }

        return [
            'authorizedCapital' => $this->addNameToRow($authorizedCapital, 'Уставный капитал'),
            'retainedEarnings'  => $this->addNameToRow($retainedEarnings, 'Нераспределенная прибыль'),
            'creditsAndLoans'   => $this->addNameToRow($creditsAndLoans, 'Кредиты и займы'),
            'totalLiabilities'  => $this->addNameToRow($totalLiabilities, 'Итого пассивы'),
        ];
    }

    /**
     * Считаем проверку баланса по активам и пассивам
     *
     * @param  array $totalAssets
     * @param  array $totalLiabilities
     * @return array
     */
    protected function getBalanceCheck(array $totalAssets, array $totalLiabilities): array
    {
        $totalAssets      = $this->removeNamedColumns([$totalAssets])[0];
        $totalLiabilities = $this->removeNamedColumns([$totalLiabilities])[0];

        $balanceCheck = [];
        for ($i = 0; $i < max(count($totalAssets), count($totalLiabilities)); $i++) {
            $totalAssetsItem      = $totalAssets[$i] ?? 0;
            $totalLiabilitiesItem = $totalLiabilities[$i] ?? 0;

            $balanceCheck[] = $totalAssetsItem - $totalLiabilitiesItem;
        }

        return $this->addNameToRow($balanceCheck, 'проверка баланса');
    }
}
