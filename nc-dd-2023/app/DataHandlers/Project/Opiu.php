<?php declare(strict_types=1);
namespace App\DataHandlers\Project;

/**
 * DataHandler для расчётов ДДС
 */
class Opiu
{

    /**
     * Вычитает элементы одного массива из элементов другого
     *
     * @param  array $proceedsTable
     * @param  array $expenses
     * @return array
     */
    public function getDifference(array $proceedsTable, array $expenses): array
    {
        // если нет ни выручки ни расходов
        if (empty($proceedsTable) && empty($expenses)) {
            return [];
        }

        $profit   = [];
        $countMax = !empty($proceedsTable) ? count($proceedsTable) : count($expenses);

        for ($i = 0; $i < $countMax; $i++) {
            $proceeds   = $proceedsTable[$i] ?? 0;
            $profit[$i] = $proceeds - ($expenses[$i] ?? 0);
        }

        return $profit;
    }

    /**
     * Возвращает сумму Общих и Административных расходов
     *
     * @param array $fot
     * @param array $expensesWithoutNds
     * @return array
     */
    public function getGeneralAndAdministrativeExpenses(array $fot, array $expensesWithoutNds): array
    {
        $generalAndAdminExpenses   = [];
        $fotWithExpensesWithoutNds = array_merge($fot, $expensesWithoutNds);

        foreach ($fotWithExpensesWithoutNds as $fotWithExpenseWithoutNd) {
            unset($fotWithExpenseWithoutNd[0]);
            foreach ($fotWithExpenseWithoutNd as $key => $item) {
                $key = $key - 1;
                if (!isset($generalAndAdminExpenses[$key])) {
                    $generalAndAdminExpenses[$key] = 0;
                }
                $generalAndAdminExpenses[$key] += $item;
            }
        }

        return $generalAndAdminExpenses;
    }

}
