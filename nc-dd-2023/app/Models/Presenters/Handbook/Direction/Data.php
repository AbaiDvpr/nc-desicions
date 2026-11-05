<?php declare(strict_types=1);

namespace App\Models\Presenters\Handbook\Direction;

use App\DI;
use App\Models\Company;
use App\Models\Handbook\Direction;

/**
 * Презентер для представления записи из справочника
 */
class Data
{
    /**
     * Базовая модель банка
     *
     * @var \App\Models\Handbook\Direction
     */
    private Direction $direction;

    /**
     * Конструктор
     *
     * @param \App\Models\Handbook\Direction $direction
     */
    public function __construct(Direction $direction)
    {
        $this->direction = $direction;
    }

    /**
     * Возвращает UUID компании
     *
     * @return \App\Models\Company|null
     */
    public function getCompany(): ?Company
    {
        $company = DI::getRepository()->getCompany()->getByUuid($this->direction->getCompanyUuid());

        if (is_null($company)) {
            return null;
        }

        return $company;
    }

    /**
     * Возвращает имя компании
     *
     * @return string
     */
    public function getTitle(): string
    {
        return $this->direction->getTitle();
    }

    /**
     * Возвращает публичные справочника
     *
     * @return array
     */
    public function getPublicData(): array
    {
        return [
            'uuid'       => $this->direction->getId(),
            'value'      => $this->direction->getId(),
            'company'    => $this->getCompany()?->getCompanyData()->getPublicData(),
            'title'      => $this->getTitle(),
            'created_at' => $this->direction->getCreatedAt()->format(DATE_ATOM),
        ];
    }
}
