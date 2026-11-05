<?php declare(strict_types=1);

namespace App\Models\Handbook\Bank;

/**
 * Базовый интерфейс для вложенных полей
 */
interface BaseInterface
{
    /**
     * Возвращает значения свойства в виде массива
     *
     * @return array
     */
    public function toArray(): array;
}
