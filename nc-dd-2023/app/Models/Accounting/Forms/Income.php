<?php declare(strict_types=1);
namespace App\Models\Accounting\Forms;

/**
 * Форма запроса на добавление доходной части
 */
class Income extends Base
{
    /**
     * Тип операции доходов
     *
     * @var string
     */
    protected string $operation = self::OPERATION_INCOME;
}
