<?php declare(strict_types=1);

namespace App\Models\Handbook\Bank;

use Illuminate\Support\Arr;

class Requisites extends Base
{
    /**
     * Наименование поля со вложенными полями
     */
    public string $fieldName = 'requisites';

    /**
     * Адрес банка
     *
     * @var string|null
     */
    protected ?string $address;

    /**
     * Телефоны банка
     *
     * @var string|null
     */
    protected ?string $phones;

    /**
     * Международный номер банковского счета - IBAN
     *
     * @var string|null
     */
    protected ?string $iban;

    /**
     * Регистрационный номер налогоплательщика - РНН
     *
     * @var string|null
     */
    protected ?string $rnn;

    /**
     * Бизнес-Идентификационный Номер - БИН
     *
     * @var string|null
     */
    protected ?string $bin;

    /**
     * Лицензия
     *
     * @var string|null
     */
    protected ?string $license;

    /**
     * SWIFT код или БИК (Банковский Идентификационный Код)
     *
     * @var string|null
     */
    protected ?string $bik;

    /**
     * Список свойств доступных для сохранения в реквизитах
     *
     * @var array|string[]
     */
    protected array $validProperties = [
        'address',
        'phones',
        'iban',
        'rnn',
        'bin',
        'license',
        'bik',
    ];

    /**
     * Возвращает адрес из реквизитов
     *
     * @return string|null
     */
    public function getAddress(): ?string
    {
        return $this->getProperty('address');
    }

    /**
     * Устанавливает адрес в реквизиты
     *
     * @param  string|null                          $address
     * @return \App\Models\Handbook\Bank\Requisites
     */
    public function setAddress(?string $address): static
    {
        $this->setProperty('address', $address);

        return $this;
    }

    /**
     * Возвращает адрес из реквизитов
     *
     * @return string|array|null
     */
    public function getPhones(): string|array|null
    {
        return $this->getProperty('phones');
    }

    /**
     * Устанавливает адрес в реквизиты
     *
     * @param  string|array|null                    $phones
     * @return \App\Models\Handbook\Bank\Requisites
     */
    public function setPhones(string|array|null $phones): static
    {
        if (!empty($phones) && !is_array($phones)) {
            $phones = Arr::wrap($phones);
        }

        $this->setProperty('phones', $phones);

        return $this;
    }

    /**
     * Возвращает IBAN номер из реквизитов
     *
     * @return string|null
     */
    public function getIban(): ?string
    {
        return $this->getProperty('iban');
    }

    /**
     * Устанавливает IBAN номер в реквизиты
     *
     * @param  string|null                          $iban
     * @return \App\Models\Handbook\Bank\Requisites
     */
    public function setIban(?string $iban): static
    {
        $this->setProperty('iban', $iban);

        return $this;
    }

    /**
     * Возвращает РНН номер из реквизитов
     *
     * @return string|null
     */
    public function getRnn(): ?string
    {
        return $this->getProperty('rnn');
    }

    /**
     * Устанавливает РНН номер в реквизиты
     *
     * @param  string|null                          $rnn
     * @return \App\Models\Handbook\Bank\Requisites
     */
    public function setRnn(?string $rnn): static
    {
        $this->setProperty('rnn', $rnn);

        return $this;
    }

    /**
     * Возвращает БИН номер из реквизитов
     *
     * @return string|null
     */
    public function getBin(): ?string
    {
        return $this->getProperty('bin');
    }

    /**
     * Устанавливает БИН номер в реквизиты
     *
     * @param  string|null                          $bin
     * @return \App\Models\Handbook\Bank\Requisites
     */
    public function setBin(?string $bin): static
    {
        $this->setProperty('bin', $bin);

        return $this;
    }

    /**
     * Возвращает номер лицензия из реквизитов
     *
     * @return string|null
     */
    public function getLicense(): ?string
    {
        return $this->getProperty('license');
    }

    /**
     * Устанавливает номер лицензия в реквизиты
     *
     * @param  string|null                          $license
     * @return \App\Models\Handbook\Bank\Requisites
     */
    public function setLicense(?string $license): static
    {
        $this->setProperty('license', $license);

        return $this;
    }

    /**
     * Возвращает SWIFT код или БИК из реквизитов
     *
     * @return string|null
     */
    public function getBik(): ?string
    {
        return $this->getProperty('bik');
    }

    /**
     * Устанавливает SWIFT код или БИК в реквизиты
     *
     * @param  string|null                          $bik
     * @return \App\Models\Handbook\Bank\Requisites
     */
    public function setBik(?string $bik): static
    {
        $this->setProperty('bik', $bik);

        return $this;
    }

    /**
     * Возвращает реквизиты в виде массива
     *
     * @return array
     */
    public function toArray(): array
    {
        return collect([
            'address' => $this->getAddress(),
            'phones'  => $this->getPhones(),
            'iban'    => $this->getIban(),
            'rnn'     => $this->getRnn(),
            'bin'     => $this->getBin(),
            'license' => $this->getLicense(),
            'bik'     => $this->getBik(),
        ])->filter()->toArray();
    }
}
