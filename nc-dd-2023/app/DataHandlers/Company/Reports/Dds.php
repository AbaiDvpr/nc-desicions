<?php declare(strict_types=1);

namespace App\DataHandlers\Company\Reports;

use App\DataHandlers\Company\Reports\Dds\Fact;
use App\DataHandlers\Company\Reports\Dds\Plan;

/**
 * DH для работы с отчетами ДДС
 */
class Dds
{
    /**
     * Возвращает обработчик данных для факта
     *
     * @return \App\DataHandlers\Company\Reports\Dds\Fact
     */
    public function getFact(): Fact
    {
        return new Fact();
    }

    /**
     * Возвращает обработчик данных для плана
     *
     * @return \App\DataHandlers\Company\Reports\Dds\Plan
     */
    public function getPlan(): Plan
    {
        return new Plan();
    }
}
