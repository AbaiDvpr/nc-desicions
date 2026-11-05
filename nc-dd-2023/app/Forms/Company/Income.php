<?php declare(strict_types=1);
namespace App\Forms\Company;

/**
 * Параметры для формы заполнения доходов
 */
class Income extends Base
{
    /**
     * Доходы
     */
    const NAME = 'income';

    /**
     * Основные доходы (Реализация товаров и услуг)
     */
    const BASIC_INCOME = 1;

    /**
     * Прочие доходы (Прочие поступления)
     */
    const OTHER_INCOME = 2;

    /**
     * Прочая выручка
     */
    const OTHER_REVENUE = 3;

    /**
     * Авансы, полученные от покупателей, заказчиков
     */
    const ADVANCES = 4;

    /**
     * Поступления по договорам страхования
     */
    const CONTRACTS = 5;

    /**
     * Полученные вознаграждения
     */
    const REMUNERATION_RECEIVED = 6;

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
                'value' => self::BASIC_INCOME,
                'title' => 'Основные доходы',
            ],
            [
                'value' => self::OTHER_REVENUE,
                'title' => 'Прочая выручка',
            ],
            [
                'value' => self::ADVANCES,
                'title' => 'Авансы, полученные от покупателей, заказчиков',
            ],
            [
                'value' => self::CONTRACTS,
                'title' => 'Поступления по договорам страхования',
            ],
            [
                'value' => self::REMUNERATION_RECEIVED,
                'title' => 'Полученные вознаграждения',
            ],
            [
                'value' => self::OTHER_INCOME,
                'title' => 'Прочие доходы',
            ],
        ];
    }
}
