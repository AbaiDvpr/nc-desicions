<?php declare(strict_types=1);

namespace App\Components\Report\Sheets;

use App\Components\Report\Sheets\Interfaces\SheetInterface;
use App\DI;
use App\Models\Project\WorkingCapital;
use PhpOffice\PhpSpreadsheet\Style\Color;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;

/**
 * Класс для заполнения листа "Структура финансирования"
 *
 * @package App\Components\Report\Sheets
 */
class FinancingSheet extends AbstractReportSheet implements SheetInterface
{
    /**
     * Номер текущего листа
     */
    const CURRENT_SHEET = 1;

    /**
     * Строка с которой необходимо начать запись в файл
     */
    const START_ROW = 3;

    /**
     * Текущий активный лист
     *
     * @var \PhpOffice\PhpSpreadsheet\Worksheet\Worksheet
     */
    protected $sheet;

    /**
     * @inheritdoc
     *
     * @return \App\Components\Report\Sheets\FinancingSheet
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     */
    public function generate(): self
    {
        $financing = collect($this->getFinancingTable());

        if ($financing->count() > 0) {
            $this->setActiveSheet(self::CURRENT_SHEET);

            $this->sheet = $this->getActiveSheet();
            $data        = $financing->map(function ($datum, $field) {
                return collect($datum)->prepend(config('projects.financing.' . $field));
            })->values()->toArray();
            $data        = collect($data)->push(
                collect(DI::getDH()->getProject()->getSumColumns($data))->put(0, 'Итого')
            )->toArray();

            // Для грантового проекта меняем название столбца
            if ($this->project->isGrantFinancing()) {
                $this->sheet->setCellValue('B2', 'Грантовые средства');
            }

            // Устанавливаем общую сумму привлекаемых инвестиций
            $this->report->totalWorkingCapital = $this->getWorkingCapital();
            $this->report->investmentItems     = $financing->only(WorkingCapital::INVESTMENT_FIELDS)
                ->map(function ($item) {
                    return last($item);
                });

            // Сохраняем дальше по листам итоги плана финансирования
            $this->report->financingTotal = [
                'grant' => last($data)[1] ?? 0,
                'own'   => last($data)[2] ?? 0,
                'total' => last($data)[3] ?? 0,
            ];

            $this->sheet->fromArray($data, null, 'A' . self::START_ROW, true);

            $lastRow  = self::START_ROW + count($data) - 1;
            $lastCell = $this->sheet->getCellByColumnAndRow(count(last($data)), $lastRow)->getCoordinate();

            $this->setBorders('A1:' . $lastCell, Color::COLOR_BLACK);
            $this->sheet->getStyle('B' . self::START_ROW . ':' . $lastCell)->applyFromArray([
                'numberFormat' => [
                    'formatCode' => self::FORMAT_NUMBER
                ]
            ]);
            $this->setResultStyle('A' . $lastRow . ':' . $lastCell);

            // Устанавливаем процентное соотношение
            $this->sheet->fromArray(
                $this->getRowPercentage(last($data)),
                null,
                'A' . $lastRow + 1,
                true
            );
        }

        $this->setDiscountPercent();

        return $this;
    }

    /**
     * Формирует данные для таблицы "Структура финансирования"
     *
     * @return array
     */
    private function getFinancingTable(): array
    {
        $records       = collect([]);
        $investments   = $this->getInvestmentAmount();
        $ownInvestment = $this->getOwnInvestment();

        if (count($investments) > 0 || count($ownInvestment) > 0) {
            if (count($investments) > 0) {
                $records = collect($investments);
            } elseif (count($ownInvestment) > 0) {
                $records = collect($ownInvestment);
            }
        }

        if ($records->count() > 0) {
            return $records->map(function ($record, $field) use ($investments, $ownInvestment) {
                if (isset($investments[$field]) || isset($ownInvestment[$field])) {
                    $invest = $investments[$field] ?? 0;
                    $own    = $ownInvestment[$field] ?? 0;

                    return [
                        $invest ?? 0,
                        $own,
                        $invest + $own
                    ];
                }

                return null;
            })->whereNotNull()->toArray();
        }

        return [];
    }

    /**
     * Получить значения из собственных вложений
     *
     * @return array
     */
    private function getOwnInvestment(): array
    {
        if ($this->project->isOwnInvestment()) {
            $ownInvestment = $this->project->ownInvestment;

            if ($ownInvestment !== null) {
                return collect($ownInvestment)->except(['id'])->toArray();
            }
        }

        return [];
    }

    /**
     * Получить значения из привлекаемых инвестиций
     *
     * @return array
     */
    private function getInvestmentAmount(): array
    {
        $investmentAmount = $this->project->investmentAmount;

        if ($investmentAmount !== null) {
            return collect($investmentAmount)->except(['id'])->toArray();
        }

        return [];
    }

    /**
     * Высчитывает и устанавливает ставку дисконтирования
     */
    protected function setDiscountPercent()
    {
        $form                = $this->project->getForm();
        $taxRegime           = $this->project->getTaxRegime();
        $discountTax         = config('projects.discountTax');
        $percentagePerRegime = $discountTax[$form][$taxRegime];

        // $discountPercent = 0.135 * ($this->report->financingTotal['own'] ?? 1) /
        //     ($this->report->financingTotal['total'] ?? 1) + 0.19 * (1 - $percentagePerRegime) *
        //     ($this->report->financingTotal['grant'] ?? 1) / ($this->report->financingTotal['total'] ?? 1);

        // = (13,5%'Структура финансирования'!C9/'Структура финансирования'!D9)+
        // (19%'Структура финансирования'!B9/'Структура финансирования'!D9)*
        // (1-ставка налога на прибыль)
        // Решено было так сделать 31.05.2022 (Лейла сказала так сделать)
        $discountPercent = (0.135 * ($this->report->financingTotal['own'] ?? 1) /
            ($this->report->financingTotal['total'] ?? 1)) + (0.19 *
            ($this->report->financingTotal['grant'] ?? 1) / ($this->report->financingTotal['total'] ?? 1)) *
            (1 - $percentagePerRegime);

        if ($discountPercent < 0.135) {
            $discountPercent = 0.135;
        }

        $this->report->discountPercent = $discountPercent;

        $this->updateDiscountFactorAfterCalculating();
    }

    /**
     * Получает общую сумму привлекаемых инвестиций
     *
     * @return int
     */
    protected function getWorkingCapital(): int
    {
        $investmentAmount = $this->getInvestmentAmount();

        return (int) collect($investmentAmount)->only(WorkingCapital::WORKING_CAPITAL_FIELDS)->sum();
    }

    /**
     * Получает общую сумму привлекаемых инвестиций
     *
     * @return int
     */
    protected function getInvestmentTotalSum(): int
    {
        $investmentAmount = $this->getInvestmentAmount();
        $ownInvestment = $this->getOwnInvestment();

        return (int) collect($investmentAmount)->only(WorkingCapital::INVESTMENT_FIELDS)->sum() +
               (int) collect($ownInvestment)->only(WorkingCapital::INVESTMENT_FIELDS)->sum();
    }

    /**
     * Формирует строку с процентным соотношением
     *
     * @param  array $data
     * @return array
     */
    private function getRowPercentage(array $data): array
    {
        // Значение последнего столбца - это общая сумма
        $totalData = collect($this->removeNamedColumns([$data]))->first();
        $totalSum  = array_pop($totalData);
        $result    = ['100%'];

        foreach ($totalData as $item) {
            array_unshift($result, $this->getSalePercent($totalSum, $item));
        }

        return array_merge([null], $result);
    }

    /**
     * Возвращает процент от числа
     *
     * @param  $totalSum
     * @param  $percentSum
     * @return string
     */
    private function getSalePercent($totalSum, $percentSum): string
    {
        return ((string) round((($totalSum - $percentSum) * 100) / $totalSum)) . '%';
    }
}
