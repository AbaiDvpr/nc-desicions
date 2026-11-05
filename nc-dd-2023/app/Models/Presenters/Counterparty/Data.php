<?php declare(strict_types = 1);

namespace App\Models\Presenters\Counterparty;

use App\DI;
use App\Models\Counterparty;
use App\Models\Handbook\Bank;

/**
 * Презентер для обработки данных контрагента
 */
class Data
{
    /**
     * Базовая модель контрагента
     *
     * @var \App\Models\Counterparty
     */
    private Counterparty $counterparty;

    /**
     * Конструктор
     *
     * @param \App\Models\Counterparty $counterparty
     */
    public function __construct(Counterparty $counterparty)
    {
        $this->counterparty = $counterparty;
    }

    /**
     * Возвращает наименование компании/физ лица контрагента
     *
     * @return string
     */
    public function getName(): string
    {
        return $this->counterparty->getName();
    }

    /**
     * Возвращает юридический адрес контрагента
     *
     * @return string
     */
    public function getLegalAddress(): string
    {
        return $this->counterparty->getLegalAddress();
    }

    /**
     * Возвращает фактический адрес контрагента
     *
     * @return string|null
     */
    public function getActualAddress(): ?string
    {
        return $this->counterparty->getActualAddress();
    }

    /**
     * Возвращает email контрагента
     *
     * @return string
     */
    public function getEmail(): string
    {
        return $this->counterparty->getEmail();
    }

    /**
     * Возвращает номер контрагента
     *
     * @return string|null
     */
    public function getPhone(): ?string
    {
        return $this->counterparty->getPhone();
    }

    /**
     * Возвращает директора контрагента
     *
     * @return string
     */
    public function getDirector(): string
    {
        return $this->counterparty->getDirector();
    }

    /**
     * Возвращает БИН из банковских реквизитов
     *
     * @return string
     */
    public function getBin(): string
    {
        return $this->counterparty->getBin();
    }

    /**
     * Возвращает ИИК из банковских реквизитов
     *
     * @return string
     */
    public function getIik(): string
    {
        return $this->counterparty->getIik();
    }

    /**
     * Возвращает связь с банком
     *
     * @return \App\Models\Handbook\Bank|null
     */
    public function getBank(): ?Bank
    {
        return DI::getRepository()->getHandbook()->getBank()->getOne(
            $this->counterparty->getBankId()
        );
    }

    /**
     * Возвращает публичные данные компании
     *
     * @return array
     */
    public function getPublicData(): array
    {
        $bank = $this->getBank();

        return [
            'uuid'          => $this->counterparty->getId(),
            'name'          => $this->getName(),
            'legalAddress'  => $this->getLegalAddress(),
            'actualAddress' => $this->getActualAddress(),
            'email'         => $this->getEmail(),
            'phone'         => formatPhone($this->getPhone()),
            'director'      => $this->getDirector(),
            'bin'           => $this->getBin(),
            'iik'           => $this->getIik(),
            'bankId'        => $bank?->getId(),
            'bankName'      => $bank?->getName(),
            'bik'           => $bank?->getRequisites()->getBik(),
        ];
    }
}
