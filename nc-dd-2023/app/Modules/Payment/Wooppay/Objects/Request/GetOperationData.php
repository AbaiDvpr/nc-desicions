<?php declare(strict_types=1);

namespace App\Modules\Payment\Wooppay\Objects\Request;

/**
 * Получить запрос данных об операции
 */
class GetOperationData
{
    /**
     * Идентификаторы операций.
     *
     * @var array
     */
    protected array $operationId;

    public function getOperationId(): array
    {
        return $this->operationId;
    }

    public function setOperationId(array $operationId): self
    {
        $this->operationId = $operationId;

        return $this;
    }
}
