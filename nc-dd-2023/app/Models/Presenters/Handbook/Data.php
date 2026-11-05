<?php declare(strict_types=1);

namespace App\Models\Presenters\Handbook;

use App\DI;
use App\Models\Company;
use App\Models\Handbook;

/**
 * Презентер для представления записи из справочника
 */
class Data
{
    /**
     * Базовая модель справочника
     *
     * @var \App\Models\Handbook
     */
    private Handbook $handbook;

    /**
     * Конструктор
     *
     * @param \App\Models\Handbook $handbook
     */
    public function __construct(Handbook $handbook)
    {
        $this->handbook = $handbook;
    }

    /**
     * Возвращает UUID компании
     *
     * @return \App\Models\Company|null
     */
    public function getCompany(): ?Company
    {
        $company = DI::getRepository()->getCompany()->getByUuid($this->handbook->getCompanyUuid());

        if (is_null($company)) {
            return null;
        }

        return $company;
    }

    /**
     * Возвращает тип
     *
     * @return int
     */
    public function getType(): int
    {
        return $this->handbook->getType();
    }

    /**
     * Возвращает публичные данные справочника
     *
     * @return array
     */
    public function getPublicData(): array
    {
        return [
            'uuid'       => $this->handbook->getId(),
            'value'      => $this->handbook->getId(),
            'company'    => $this->getCompany()?->getCompanyData()->getPublicData(),
            'title'      => $this->handbook->getTitle(),
            'type'       => $this->getType(),
            'created_at' => $this->handbook->getCreatedAt()->format(DATE_ATOM),
        ];
    }
}
