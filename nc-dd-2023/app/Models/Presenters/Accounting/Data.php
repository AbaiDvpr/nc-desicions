<?php declare(strict_types = 1);
namespace App\Models\Presenters\Accounting;

use App\Forms\Company\Active;
use App\Forms\Company\Base;
use App\Forms\Company\Income;
use App\Forms\Company\Outcome;
use App\Models\Accounting;
use App\Models\Company;

/**
 * Презентер для обработки данных статьи доходов/расходов
 */
class Data
{
    /**
     * @var \App\Models\Accounting
     */
    private Accounting $accounting;

    /**
     * Конструктор
     *
     * @param \App\Models\Accounting $accounting
     */
    public function __construct(Accounting $accounting)
    {
        $this->accounting = $accounting;
    }

    /**
     * Является ли запись доходом
     *
     * @return bool
     */
    public function isIncome(): bool
    {
        return $this->accounting->getOperation() === Income::NAME;
    }

    /**
     * Является ли запись расходом
     *
     * @return bool
     */
    public function isOutcome(): bool
    {
        return $this->accounting->getOperation() === Outcome::NAME;
    }

    /**
     * Является ли запись активами или пассивами
     *
     * @return bool
     */
    public function isActive(): bool
    {
        return $this->accounting->getOperation() === Active::NAME;
    }

    /**
     * Является ли запись доходом от операционной деятельности
     *
     * @return bool
     */
    public function isIncomeOperating(): bool
    {
        if (!$this->isIncome()) {
            return false;
        }

        return $this->accounting->getKindActivity() === Base::OPERATING_ACTIVITY;
    }

    /**
     * Является ли запись доходом от финансовой деятельности
     *
     * @return bool
     */
    public function isIncomeFinancial(): bool
    {
        if (!$this->isIncome()) {
            return false;
        }

        return $this->accounting->getKindActivity() === Base::FINANCIAL_ACTIVITY;
    }

    /**
     * Является ли запись доходом от инвестиционной деятельности
     *
     * @return bool
     */
    public function isIncomeInvestment(): bool
    {
        if (!$this->isIncome()) {
            return false;
        }

        return $this->accounting->getKindActivity() === Base::INVESTMENT_ACTIVITY;
    }

    /**
     * Является ли запись расходом от операционной деятельности
     *
     * @return bool
     */
    public function isOutcomeOperating(): bool
    {
        if (!$this->isOutcome()) {
            return false;
        }

        return $this->accounting->getKindActivity() === Base::OPERATING_ACTIVITY;
    }

    /**
     * Является ли запись расходом от финансовой деятельности
     *
     * @return bool
     */
    public function isOutcomeFinancial(): bool
    {
        if (!$this->isOutcome()) {
            return false;
        }

        return $this->accounting->getKindActivity() === Base::FINANCIAL_ACTIVITY;
    }

    /**
     * Является ли запись расходом от инвестиционной деятельности
     *
     * @return bool
     */
    public function isOutcomeInvestment(): bool
    {
        if (!$this->isOutcome()) {
            return false;
        }

        return $this->accounting->getKindActivity() === Base::INVESTMENT_ACTIVITY;
    }

    /**
     * Является ли статья частью "Основные доходы"
     *
     * @return bool
     */
    public function isIncomeBasic(): bool
    {
        if (!$this->isIncome()) {
            return false;
        }

        return $this->accounting->getType() === Income::BASIC_INCOME;
    }

    /**
     * Является ли статья доходами от "Прочая выручка"
     *
     * @return bool
     */
    public function isIncomeOtherRevenue(): bool
    {
        if (!$this->isIncome()) {
            return false;
        }

        return $this->accounting->getType() === Income::OTHER_REVENUE;
    }

    /**
     * Является ли статья доходами от "Авансы, полученные от покупателей, заказчиков"
     *
     * @return bool
     */
    public function isIncomeAdvances(): bool
    {
        if (!$this->isIncome()) {
            return false;
        }

        return $this->accounting->getType() === Income::ADVANCES;
    }

    /**
     * Является ли статья доходами от "Поступления по договорам страхования"
     *
     * @return bool
     */
    public function isIncomeContracts(): bool
    {
        if (!$this->isIncome()) {
            return false;
        }

        return $this->accounting->getType() === Income::CONTRACTS;
    }

    /**
     * Является ли статья доходами от "Полученные вознаграждения"
     *
     * @return bool
     */
    public function isIncomeRemunerationReceived(): bool
    {
        if (!$this->isIncome()) {
            return false;
        }

        return $this->accounting->getType() === Income::REMUNERATION_RECEIVED;
    }

    /**
     * Является ли статья частью прочие доходы
     *
     * @return bool
     */
    public function isIncomeOther(): bool
    {
        if (!$this->isIncome()) {
            return false;
        }

        return $this->accounting->getType() === Income::OTHER_INCOME;
    }

    /**
     * Является ли статья частью производственных расходов
     *
     * @return bool
     */
    public function isOutcomeProductionCost(): bool
    {
        if (!$this->isOutcome()) {
            return false;
        }

        return $this->accounting->getType() === Outcome::PRODUCTION_COSTS;
    }

    /**
     * Является ли статья частью коммерческих расходов
     *
     * @return bool
     */
    public function isOutcomeImplementationCost(): bool
    {
        if (!$this->isOutcome()) {
            return false;
        }

        return $this->accounting->getType() === Outcome::SELLING_EXPENSES;
    }

    /**
     * Является ли статья частью административных расходов
     *
     * @return bool
     */
    public function isOutcomeAdministrativeExpenses(): bool
    {
        if (!$this->isOutcome()) {
            return false;
        }

        return $this->accounting->getType() === Outcome::ADMINISTRATIVE_EXPENSES;
    }

    /**
     * Является ли статья частью "прочие расходы"
     *
     * @return bool
     */
    public function isOutcomeOther(): bool
    {
        if (!$this->isOutcome()) {
            return false;
        }

        return $this->accounting->getType() === Outcome::OTHER_OUTCOME;
    }

    /**
     * Является ли статья частью "налоги и сборы"
     *
     * @return bool
     */
    public function isOutcomeTaxesFees(): bool
    {
        if (!$this->isOutcome()) {
            return false;
        }

        return $this->accounting->getType() === Outcome::TAXES_FEES;
    }

    /**
     * Возвращает имя типа доходов/расходов
     *
     * @return string|null
     */
    public function getTypeName(): ?string
    {
        switch ($this->accounting->getOperation()) {
            case Income::NAME:
                $type = collect((new Income())->getTypes())
                    ->firstWhere('value', $this->accounting->getType());

                break;

            case Outcome::NAME:
                $type = collect((new Outcome())->getTypes())
                    ->firstWhere('value', $this->accounting->getType());

                break;
        }

        return $type['title'] ?? null;
    }

    /**
     * Возвращает наименование вида деятельности
     *
     * @return string|null
     */
    public function getKindActivityName(): ?string
    {
        switch ($this->accounting->getOperation()) {
            case Income::NAME:
                $type = collect((new Income())->getKindActivity())
                    ->firstWhere('value', $this->accounting->getKindActivity());

                break;

            case Outcome::NAME:
                $type = collect((new Outcome())->getKindActivity())
                    ->firstWhere('value', $this->accounting->getKindActivity());

                break;
        }

        return $type['title'] ?? null;
    }

    /**
     * Возвращает имя актива / пассива
     *
     * @return string|null
     */
    public function getActiveName(): ?string
    {
        if ($this->isActive()) {
            $type = collect((new Active())->getActiveList())
                ->firstWhere('value', $this->accounting->getActive());
        }

        return $type['title'] ?? null;
    }

    /**
     * Возвращает имя типа выбранного актива / пассива
     *
     * @return string|null
     */
    public function getActiveTypeName(): ?string
    {
        if ($this->isActive()) {
            $activeTypeList = (new Active())->getActiveTypeList();

            $type = collect($activeTypeList[$this->accounting->getActive()])->firstWhere('value', $this->accounting->getActiveType());
        }

        return $type['title'] ?? null;
    }

    /**
     * Возвращает публичные данные компании
     *
     * @return array
     */
    public function getPublicData(): array
    {
        return [
            'id'           => $this->accounting->getId(),
            'operation'    => $this->accounting->getOperation(),
            'accountCash'  => $this->accounting->getAccountCash(),
            'article'      => $this->accounting?->getArticle()?->getTitle(),
            'active'       => $this->getActiveName(),
            'activeType'   => $this->getActiveTypeName(),
            'direction'    => $this->accounting->getDirection(),
            'kindActivity' => $this->getKindActivityName(),
            'type'         => $this->getTypeName(),
            'price'        => $this->accounting->getPrice(),
            'receiptDate'  => $this->accounting->getReceiptDate()->format('Y-m-d H:i'),
            'createdAt'    => $this->accounting->getCreatedAt()->format('Y-m-d H:i'),
        ];
    }
}
