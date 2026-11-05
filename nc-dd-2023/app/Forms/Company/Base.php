<?php declare(strict_types=1);

namespace App\Forms\Company;

/**
 * Базовы класс для форм со справочниками
 */
abstract class Base implements HandbookInterface
{
    /**
     * Наличными в кассу
     */
    const CASH_CHECKOUT = 1;

    /**
     * Банковский перевод
     */
    const BANK_TRANSFER = 2;

    /**
     * ТМЗ / Услуга
     */
    const TMZ_SERVICE = 3;

    /**
     * Операционная деятельность
     */
    const OPERATING_ACTIVITY = 1;

    /**
     * Финансовая деятельность
     */
    const FINANCIAL_ACTIVITY = 2;

    /**
     * Инвестиционная деятельность
     */
    const INVESTMENT_ACTIVITY = 3;

    /**
     * Долгосрочные Активы
     */
    const ACTIVE_LONG_TERM_ASSETS = 1;

    /**
     * Краткосрочные Активы
     */
    const ACTIVE_SHORT_TERM_ASSETS = 2;

    /**
     * Капитал
     */
    const ACTIVE_CAPITAL = 3;

    /**
     * Долгосрочные Обязательства
     */
    const ACTIVE_LONG_TERM_DUTIES = 4;

    /**
     * Краткосрочные Обязательства
     */
    const ACTIVE_SHORT_TERM_LIABILITIES = 5;

    /**
     * Значения для поля с выбором поступления средств
     *
     * @return array[]
     */
    public function getAccountCashList(): array
    {
        return config('form-data.company.accountCashList');
    }

    /**
     * Возвращает список видов деятельности
     *
     * @return array[]
     */
    public function getKindActivity(): array
    {
        return config('form-data.company.kindActivityList');
    }

    /**
     * Возвращает список "Тип активов или пассивов"
     *
     * @return array
     */
    public function getActiveTypeList(): array
    {
        return config('form-data.company.activeTypeList');
    }
}
