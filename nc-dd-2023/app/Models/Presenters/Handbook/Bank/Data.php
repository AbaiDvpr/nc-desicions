<?php declare(strict_types=1);

namespace App\Models\Presenters\Handbook\Bank;

use App\Models\Handbook\Bank;

/**
 * Презентер для обработки данных компании
 */
class Data
{
    /**
     * Базовая модель банка
     *
     * @var \App\Models\Handbook\Bank
     */
    private Bank $bank;

    /**
     * Конструктор
     *
     * @param \App\Models\Handbook\Bank $bank
     */
    public function __construct(Bank $bank)
    {
        $this->bank = $bank;
    }

    /**
     * Возвращает имя компании
     *
     * @return string
     */
    public function getName(): string
    {
        return $this->bank->getName();
    }

    /**
     * Возвращает адрес банка
     *
     * @return string|null
     */
    public function getAddress(): ?string
    {
        return $this->bank->getRequisites()->getAddress();
    }

    /**
     * Возвращает список телефонов отформатированный
     *
     * @return array
     */
    public function getPhones(): array
    {
        return collect($this->bank->getRequisites()->getPhones())
            ->map(function ($phone) {
                return formatPhone($phone);
            })->toArray();
    }

    /**
     * Возвращает список телефонов отформатированный
     *
     * @return string
     */
    public function getPhonesToString(): string
    {
        return collect($this->bank->getRequisites()->getPhones())
            ->map(function ($phone) {
                return formatPhone($phone);
            })->implode(', ');
    }

    /**
     * Возвращает IBAN номер из реквизитов
     *
     * @return string|null
     */
    public function getIban(): ?string
    {
        return $this->bank->getRequisites()->getIban();
    }

    /**
     * Возвращает РНН номер из реквизитов
     *
     * @return string|null
     */
    public function getRnn(): ?string
    {
        return $this->bank->getRequisites()->getRnn();
    }

    /**
     * Возвращает БИН номер из реквизитов
     *
     * @return string|null
     */
    public function getBin(): ?string
    {
        return $this->bank->getRequisites()->getBin();
    }

    /**
     * Возвращает номер лицензия из реквизитов
     *
     * @return string|null
     */
    public function getLicense(): ?string
    {
        return $this->bank->getRequisites()->getLicense();
    }

    /**
     * Возвращает SWIFT код или БИК из реквизитов
     *
     * @return string|null
     */
    public function getBik(): ?string
    {
        return $this->bank->getRequisites()->getBik();
    }

    /**
     * Возвращает публичные данные компании
     *
     * @return array
     */
    public function getPublicData(): array
    {
        return [
            'uuid'       => $this->bank->getId(),
            'name'       => $this->bank->getName(),
            'address'    => $this->getAddress(),
            'phones'     => $this->getPhones(),
            'iban'       => $this->getIban(),
            'rnn'        => $this->getRnn(),
            'bin'        => $this->getBin(),
            'license'    => $this->getLicense(),
            'bik'        => $this->getBik(),
            'created_at' => $this->bank->getCreatedAt()->format(DATE_ATOM),
        ];
    }
}
