<?php declare(strict_types=1);

namespace App\Http\Controllers\Frontend\Cabinet\Projects\Form\Fields;

use App\Models\Project;
use App\Models\Project\AdministrativeExpenses as AdministrativeExpensesModel;

/**
 * Класс для предоставления информации о суммарных данных по привлекаемым суммам
 */
class InvestPanel
{
    /**
     * Имя поля для отображения в форме
     */
    const FIELD_NAME = 'investPanel';

    /**
     * Тип поля
     */
    const FIELD_TYPE = 'investPanel';

    /**
     * Выполняет заполнение полей
     *
     * @param  \App\Models\Project $project
     * @return array[]
     */
    public function getInfo(Project $project): array
    {
        $raw            = $project->getCostType()
            ? $this->getSumVariableCosts($project)
            : (int) $project->variableCostsOther()->sum('price');
        $wages          = collect($project->salaryFund)->map(function ($position) {
            return $position->salaryPerMonth * $position->count;
        })->sum();
        $rentalExpenses = collect($project->administrativeExpenses)->map(function ($expense) {
            return $expense->type === AdministrativeExpensesModel::TYPE_RENT ? $expense->price : 0;
        })->sum();
        $otherExpenses  = collect($project->administrativeExpenses)->map(function ($expense) {
            return $expense->type === AdministrativeExpensesModel::TYPE_OTHER ? $expense->price : 0;
        })->sum();

        return [
            // общая сумма перечня строительно-монтажных работ
            'smr' => (int) $project->smrList()->sum('price'),

            // общая сумма на капитальный ремонт
            'overhaul' => (int) $project->overhaulList()->sum('price'),

            // общая сумма приобретаемой недвижимости
            'readyPremises' => (int) $project->readyPremisesList()->sum('price'),

            // общая сумма приобретаемого оборудования
            'equipment' => (int) $project->equipmentList()->sum('price'),

            // общая сумма закупа сырья, товаров, расходных материалов
            'raw' => $raw,

            // общая сумма расходы по выплате заработной платы
            'wages' => $wages,

            // общая сумма расходы на рекламу
            'advertising' => (int) $project->marketingExpenses()->sum('price'),

            // общая сумма расходы по аренде
            'rentalExpenses' => $rentalExpenses,

            // общая сумма прочие расходы
            'otherExpenses' => $otherExpenses,
        ];
    }

    /**
     * Возвращает общую сумму себестоимости перемноженную на план продаж
     *
     * @param  \App\Models\Project $project
     * @return float|int
     */
    protected function getSumVariableCosts(Project $project): float|int
    {
        $total = 0;

        if ($project->getCostType()) {
            $variableCosts = $project->variableCosts()->get();
            $volumeSales   = $project->volumeSales->pluck('count', 'id')->all();

            if ($variableCosts->count() > 0) {
                foreach ($variableCosts as $variableCost) {
                    $total += $variableCost->getPrice() * ($volumeSales[$variableCost->getVsId()] ?? 1);
                }
            }
            
            return $total;
        }

        return $total;
    }
}
