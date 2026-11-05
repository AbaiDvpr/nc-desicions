<?php declare(strict_types=1);
namespace App\Models\Accounting\Forms;

/**
 * Форма запроса на добавление расходной части
 */
class Outcome extends Base
{
    /**
     * Тип операции расходов
     *
     * @var string
     */
    protected string $operation = self::OPERATION_OUTCOME;
}
