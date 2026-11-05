<?php declare(strict_types=1);

namespace App\Forms\Company;

/**
 * Интерфейс для форм со справочниками
 */
interface HandbookInterface
{
    /**
     * Возвращает список справочников
     *
     * @return array
     */
    public static function getHandbooks(): array;
}
