<?php declare(strict_types = 1);

namespace App\Models\Presenters\Tariff;

use App\Models\Tariff;

/**
 * Презентер для обработки данных тарифов
 */
class Data
{
    /**
     * @var \App\Models\Tariff
     */
    private Tariff $tariff;

    /**
     * Конструктор
     *
     * @param \App\Models\Tariff $tariff
     */
    public function __construct(Tariff $tariff)
    {
        $this->tariff = $tariff;
    }

    /**
     * Возвращает публичные данные компании
     *
     * @return array
     */
    public function getPublicData(): array
    {
        return [
            'id'          => $this->tariff->getId(),
            'type'        => $this->tariff->getType(),
            'name'        => $this->tariff->getName(),
            'description' => $this->tariff->getDescription(),
            'price'       => $this->tariff->getPrice(),
            'correction'  => $this->tariff->getCorrection(),
            'maxProjects' => $this->tariff->getMaxProjects(),
        ];
    }
}
