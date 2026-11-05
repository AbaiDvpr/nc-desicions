<?php declare(strict_types=1);

namespace App\Components\Report\Sheets\PaymentSchedule;

/**
 * Базовы интерфейс для календаря платежей
 */
interface BaseInterface
{
    /**
     * Возвращает календарь платежей
     *
     * @return array
     */
    public function getCalendar(): array;

    /**
     * Возвращает календарь платежей по датам
     *
     * @return array
     */
    public function getCalendarByDate(): array;

    /**
     * Возвращает сумму финансирования
     *
     * @return float
     */
    public function getFinancingGrant(): float;

    /**
     * Возвращает общую сумму выплат
     *
     * @return float
     */
    public function getTotalPayments(): float;

    /**
     * Возвращает сумму переплаты
     *
     * @return float
     */
    public function getOverpayment(): float;
}
