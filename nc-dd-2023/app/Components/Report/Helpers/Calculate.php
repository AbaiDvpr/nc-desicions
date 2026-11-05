<?php declare(strict_types=1);

namespace App\Components\Report\Helpers;

use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;

class Calculate extends BaseHelper
{
    /**
     * Процент "по умолчанию"
     */
    const DEFAULT_TAX_REGIME = 0.03;

    /**
     * Процент "по умолчанию"
     */
    const DEFAULT_TAX_YEAR = 2023;

    /**
     * Упрощенная декларация
     */
    const SIMPLIFIED_DECLARATION = 4;

    /**
     * Получает процент налога на прибыль
     */
    public function calculateTax(): float
    {
        $taxes = Arr::get(config('projects.incomeTax'), $this->project->getForm());

        return Arr::get($taxes, $this->project->getTaxRegime(), self::DEFAULT_TAX_REGIME);
    }

    /**
     * Высчитывает год с которого нужно будет применять налог на прибыль
     * в зависимости от режима налогообложения
     *
     * @return int
     */
    public function calculateYearForTax(): int
    {
        if ($this->project->getTaxRegime() === self::SIMPLIFIED_DECLARATION) {
            return self::DEFAULT_TAX_YEAR;
        }

        return Carbon::now()->year;
    }
}
