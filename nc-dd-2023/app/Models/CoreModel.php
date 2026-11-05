<?php declare(strict_types=1);

namespace App\Models;

use App\Traits\Models\Uuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Базовый класс для моделей Компании и Проектов
 */
class CoreModel extends Model
{
    use SoftDeletes, Uuids;

    /**
     * Режимы налогообложения
     */
    const TAX_REGIME_GENERAL_PROCEDURE                        = 1; // Общеустановленный порядок
    const TAX_REGIME_SPECIAL                                  = 2; // Специальный налоговый режим
    const TAX_REGIME_PATENT                                   = 3; // Патент
    const TAX_REGIME_SIMPLIFIED                               = 4; // Упрощенная декларация
    const TAX_REGIME_SPECIAL_FOR_PEASANT_AND_FARM_ENTERPRISES = 5; // Специальный налоговый режим для крестьянских и фермерских хозяйств

    /**
     * Валюта
     */
    const CURRENCY_KZT = 'kzt'; // тенге
    const CURRENCY_USD = 'usd'; // долл.USD
    const CURRENCY_EUR = 'eur'; // EUR

    /**
     * Возвращает режим налогообложения
     *
     * @return int
     */
    public function getTaxRegime(): int
    {
        return (int) $this->getAttribute('tax_regime');
    }
}
