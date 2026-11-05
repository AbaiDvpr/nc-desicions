<?php declare(strict_types=1);

namespace App\DataHandlers\Company\Reports;

use App\DataHandlers\Company\Reports\Balance\Fact;
use App\DataHandlers\Company\Reports\Balance\Plan;

/**
 * DH для работы с отчетами Баланса
 */
class Balance
{
    /**
     * Возвращает обработчик данных для факта
     *
     * @return \App\DataHandlers\Company\Reports\Balance\Fact
     */
    public function getFact(): Fact
    {
        return new Fact();
    }

    /**
     * Возвращает обработчик данных для плана
     *
     * @return \App\DataHandlers\Company\Reports\Balance\Plan
     */
    public function getPlan(): Plan
    {
        return new Plan();
    }
}
