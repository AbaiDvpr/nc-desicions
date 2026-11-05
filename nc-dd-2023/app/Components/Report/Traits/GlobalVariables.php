<?php declare(strict_types=1);

namespace App\Components\Report\Traits;

trait GlobalVariables
{

    /**
     * Ставка дисконтирования (по умолчанию - 14%)
     *
     * @var float
     */
    public $discountPercent = 0.14;

    /**
     * Массив с итогами плана финансирования
     *
     * @var array
     */
    public $financingTotal = [];

    /**
     * Общая сумма на пополнение оборотного капитала
     *
     * @var int
     */
    public $totalWorkingCapital = 0;

    /**
     * Общая сумма плана финансирования
     *
     * @var int
     */
    public $totalInvestmentSum = 0;

    /**
     * Массив с итогами "Фонд заработной платы - АУП"
     *
     * @var array
     */
    public $salaryFundTotal = [];

    /**
     * Массив с итогами по налогам "Фонд заработной платы - АУП"
     *
     * @var array
     */
    public $salaryFundTaxes = [];

    /**
     * Массив с итогами "Фонд заработной платы - ВП"
     *
     * @var array
     */
    public $vpSalaryFundTotal = [];

    /**
     * Массив с итогами по налогам "Фонд заработной платы - ВП"
     *
     * @var array
     */
    public $vpSalaryFundTaxes = [];

    /**
     * Данные для таблицы "Объем продаж"
     *
     * @var array
     */
    public $volumeSales = [];

    /**
     * Данные по "Себестоимости"
     *
     * @var array
     */
    public $costPriceTotal = [];

    /**
     * Данные по Выручке/Доход без итоговой суммы
     *
     * @var array
     */
    public $detailIncome = [];

    /**
     * Итого по доходам
     *
     * @var array
     */
    public $totalIncome = [];

    /**
     * ОПИУ 1-й блок (Выручка, Доп-й доход, Себестоимость, Маржа и Маржинальный доход)
     *
     * @var array
     */
    public $proceedsTable = [];

    /**
     * Себестоимость для таблицы ОПИУ
     *
     * @var array
     */
    public $opiuCostPrice = [];

    /**
     * Переменные расходы
     *
     * @var array
     */
    public $variableCosts = [];

    /**
     * Данные по ФОТ и расходам для таблицы ОПУ
     *
     * @var array
     */
    public $opiuFotData = [];

    /**
     * ОПИУ Сумма "Выручка" и "Дополнительный доход"
     *
     * @var array
     */
    public $opiuIncome = [];

    /**
     * Притоки по операционной деятельности - ИТОГО (ДДС)
     *
     * @var array
     */
    public $opiuIncomeTotal = [];

    /**
     * Расходы с НДС
     *
     * @var array
     */
    public $expensesWithNds = [];

    /**
     * Расходы без НДС
     *
     * @var array
     */
    public $expensesWithoutNds = [];

    /**
     * Значение EBITDA и EBITDA/выручку
     * EBITDA/выручку - (%)
     *
     * @var array
     */
    public $ebitda = [
        'EBITDA'   => null,
        'PROCEEDS' => null,
    ];

    /**
     * Лист ОПИУ - амортизации
     *
     * @var array
     */
    public $amortization = [];

    /**
     * ДДС - собранные данные для "Оттоки по операционной деятельности"
     *
     * @var array
     */
    public $operatingActivities = [];

    /**
     * ДДС - собранные строку "ИТОГО" для "Оттоки по операционной деятельности"
     *
     * @var array
     */
    public $operatingActivitiesTotal = [];

    /**
     * График платежей на основные средства
     *
     * @var array
     */
    public $paymentSchedule = [];

    /**
     * График платежей на пополнение оборотных средств
     *
     * @var array
     */
    public $paymentWorkingCapital = [];

    /**
     * Данные по налогу на прибыль
     *
     * @var array
     */
    public $incomeTax = [];

    /**
     * ОПИУ "Чистая прибыль" и "Чистая прибыль/выручка"
     *
     * @var array
     */
    public $netProfit = [
        'PROFIT'  => [], // Чистая прибыль
        'PERCENT' => [], // Чистая прибыль / выручка
    ];

    /**
     * [ДДС] Оттоки от инвестиционной деятельности
     *
     * @var array
     */
    public $investmentActivities = [];

    /**
     * Общие данные по инвестиционным полям
     * (СМР Кап ремонт и тд)
     *
     * @var array
     */
    public $investmentItems = [];

    /**
     * Данные по выплате дивидендов
     *
     * @var array
     */
    public $dividends = [];

    /**
     * Значения оборачиваемости
     *
     * @var array|null
     */
    public $turnover = null;

    /**
     * Денежные средства на конец периода
     *
     * @var array
     */
    public array $cashAtEndPeriod = [];

    /**
     * Притоки по финансовой деятельности
     *
     * @var array
     */
    public array $financialActivities = [
        'FOUNDER'  => [], //Привлечение капитала учредителей
        'BORROWED' => [], //Привлечение заемного капитала
        'TOTAL'    => [], //Итого поступления денежных средств от финансовой деятельности
    ];

    /**
     * Данные "Оттоки по финансовой деятельности"
     *
     * @var array
     */
    public array $financialOutflows = [
        'CREDIT'     => [], // Погашение займов и кредитов
        'RETIREMENT' => [], // Итого выбытие по финансовой деятельности
        'FLOW'       => [], // Итого денежный поток по финансовой деятельности
    ];

    /**
     * ОПИУ Отчет о прибыли "Чистая прибыль"
     *
     * @var array
     */
    public array $incomeStatementProfit = [];
}
