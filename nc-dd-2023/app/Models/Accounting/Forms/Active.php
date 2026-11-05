<?php declare(strict_types=1);
namespace App\Models\Accounting\Forms;

/**
 * Форма запроса на добавление активов/пассивов
 */
class Active extends Base
{
    /**
     * Тип операции активов/пассивов
     *
     * @var string
     */
    protected string $operation = self::OPERATION_ACTIVE;
}
