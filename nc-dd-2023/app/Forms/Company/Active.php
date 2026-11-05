<?php declare(strict_types=1);
namespace App\Forms\Company;

/**
 * Параметры для формы заполнения расходов
 */
class Active extends Base
{
    /**
     * Расходы
     */
    const NAME = 'active';

    /**
     * Производственные расходы
     */
    const PRODUCTION_COSTS = 1;

    /**
     * Административные расходы
     */
    const ADMINISTRATIVE_EXPENSES = 2;

    /**
     * Коммерческие расходы
     */
    const SELLING_EXPENSES = 3;

    /**
     * Прямые расходы
     */
    const DIRECT_COSTS = 4;

    /**
     * Налоги и сборы
     */
    const TAXES_FEES = 5;

    /**
     * Прочие расходы
     */
    const OTHER_OUTCOME = 6;

    /**
     * @inheritDoc
     *
     * @return array
     */
    public static function getHandbooks(): array
    {
        $self = new self();

        return [
            'accountCashList' => $self->getAccountCashList(),
            'activeList'      => $self->getKindActivity(),
            'activeTypeList'  => $self->getActiveTypeList(),
        ];
    }

    /**
     * Возвращает список полей для выбора активов или пассивов
     *
     * @return array
     */
    public function getActiveList(): array
    {
        return config('form-data.company.activeList');
    }
}
