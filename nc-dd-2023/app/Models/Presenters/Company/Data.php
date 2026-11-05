<?php declare(strict_types=1);

namespace App\Models\Presenters\Company;

use App\Models\Company;

/**
 * Презентер для обработки данных компании
 */
class Data
{
    /**
     * @var \App\Models\Company
     */
    private Company $company;

    /**
     * Конструктор
     *
     * @param \App\Models\Company $company
     */
    public function __construct(Company $company)
    {
        $this->company = $company;
    }

    /**
     * Возвращает публичные данные компании
     *
     * @return array
     */
    public function getPublicData(): array
    {
        return [
            'uuid'        => $this->company->getUuid(),
            'name'        => $this->company->getName(),
            'description' => $this->company->getDescription(),
            'currency'    => $this->company->getCurrency(),
            'form'        => $this->getFormName(),
            'taxRegime'   => $this->getTaxRegimeName(),
            'nds'         => $this->getNdsName(),
            'created_at'  => $this->company->getCreatedAt()->format(DATE_ATOM),
        ];
    }

    /**
     * Возвращает название организационно-правовой формы
     *
     * @return string
     */
    public function getFormName(): string
    {
        return (string) config(sprintf('form-data.forms.%d', $this->company->getForm()));
    }

    /**
     * Возвращает название формы налогообложения
     *
     * @return null|string
     */
    public function getTaxRegimeName(): ?string
    {
        if ($this->company->getTaxRegime()) {
            return (string) config(sprintf('form-data.company.taxRegime.%d', $this->company->getTaxRegime()));
        }

        return null;
    }

    /**
     * Возвращает название НДС
     *
     * @return string
     */
    public function getNdsName(): string
    {
        return $this->company->getNds() ? 'Да' : 'Нет';
    }
}
