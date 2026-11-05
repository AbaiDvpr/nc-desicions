<?php declare(strict_types=1);
namespace App\Forms\Company;

/**
 * Параметры для формы заполнения расходов
 */
class Outcome extends Base
{
    /**
     * Расходы
     */
    const NAME = 'outcome';

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
            'accountCashList'  => $self->getAccountCashList(),
            'kindActivityList' => $self->getKindActivity(),
            'accountTypeList'  => $self->getActiveTypeList(),
            'typesList'        => $self->getTypes(),
        ];
    }

    /**
     * Возвращает список типов доходов
     *
     * @return array[]
     */
    public function getTypes(): array
    {
        return [
            [
                'value' => self::PRODUCTION_COSTS,
                'title' => 'Текущие расходы на товар/услуги',
                // 'title' => 'Производственные расходы',
            ],
            [
                'value' => self::ADMINISTRATIVE_EXPENSES,
                // 'title' => 'Административные расходы',
                'title' => 'Расходы офиса',
            ],
            [
                'value' => self::SELLING_EXPENSES,
                'title' => 'Коммерческие расходы',
            ],
            // [
            //     'value' => self::DIRECT_COSTS,
            //     'title' => 'Прямые расходы',
            // ],
            [
                'value' => self::TAXES_FEES,
                'title' => 'Налоги и сборы',
            ],
            [
                'value' => self::OTHER_OUTCOME,
                'title' => 'Прочие расходы',
            ],
        ];
    }
}
