<?php declare(strict_types=1);

namespace App\DataHandlers\Company\Reports;

use App\DataHandlers\Company\Reports\Opu\Fact;
use App\DataHandlers\Company\Reports\Opu\Plan;

/**
 * DH для работы с отчетами ОПУ
 */
class Opu
{
    /**
     * Возвращает обработчик данных для факта
     *
     * @return \App\DataHandlers\Company\Reports\Opu\Fact
     */
    public function getFact(): Fact
    {
        return new Fact();
    }

    /**
     * Возвращает обработчик данных для плана
     *
     * @return \App\DataHandlers\Company\Reports\Opu\Plan
     */
    public function getPlan(): Plan
    {
        return new Plan();
    }
}
