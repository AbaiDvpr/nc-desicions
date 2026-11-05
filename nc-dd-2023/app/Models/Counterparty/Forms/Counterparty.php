<?php declare(strict_types = 1);

namespace App\Models\Counterparty\Forms;

use Illuminate\Http\Request;

/**
 * Объект формы контрагентов и банковских реквизитов
 */
class Counterparty
{
    /**
     * ID контрагента
     *
     * @var string|null
     *
     */
    public ?string $uuid;

    /**
     * Наименование компании/физ лица контрагента
     *
     * @var string
     *
     */
    public string $name;

    /**
     * Юридический адрес контрагента
     *
     * @var string
     *
     */
    public string $legalAddress;

    /**
     * Фактический адрес контрагента
     *
     * @var string|null
     */
    public ?string $actualAddress;

    /**
     * E-mail контрагента
     *
     * @var string
     */
    public string $email;

    /**
     * Телефон контрагента
     *
     * @var string|null
     */
    public ?string $phone;

    /**
     * Директор
     *
     * @var string
     */
    public string $director;

    /**
     * Бизнес-Идентификационный Номер - БИН
     *
     * @var string
     */
    protected string $bin;

    /**
     * Индивидуальный идентификационный номер - ИИК
     *
     * @var string
     */
    protected string $iik;

    /**
     * ID банк
     *
     * @var string
     */
    protected string $bankId;

    /**
     * Заполняет свойства данными из запроса
     *
     * @param  \Illuminate\Http\Request $request
     * @return \App\Models\Counterparty\Forms\Counterparty
     */
    public static function createFromRequest(Request $request): Counterparty
    {
        $form = new self();
        $form->uuid          = (string) $request->get('uuid');
        $form->name          = (string) $request->get('name');
        $form->legalAddress  = (string) $request->get('legalAddress');
        $form->actualAddress = (string) $request->get('actualAddress');
        $form->email         = (string) $request->get('email');
        $form->phone         = sanitizePhone((string) $request->get('phone'));
        $form->director      = (string) $request->get('director');
        $form->bin           = (string) $request->get('bin');
        $form->iik           = (string) $request->get('iik');
        $form->bankId        = (string) $request->get('bankId');

        return $form;
    }

    /**
     * Возвращает заполненную форму в виде массива
     *
     * @return array
     */
    public function toArray(): array
    {
        return [
            'name'          => $this->getName(),
            'legalAddress'  => $this->getLegalAddress(),
            'actualAddress' => $this->getActualAddress(),
            'email'         => $this->getEmail(),
            'phone'         => $this->getPhone(),
            'director'      => $this->getDirector(),
            'bin'           => $this->getBin(),
            'iik'           => $this->getIik(),
            'bankId'        => $this->getBankId(),
        ];
    }

    /**
     * @return string|null
     */
    public function getUuid(): ?string
    {
        return $this->uuid;
    }

    /**
     * @param string $uuid
     * @return void
     */
    public function setUuid(string $uuid): void
    {
        $this->uuid = $uuid;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @param  string $name
     * @return void
     */
    public function setName(string $name): void
    {
        $this->name = $name;
    }

    /**
     * @return string
     */
    public function getLegalAddress(): string
    {
        return $this->legalAddress;
    }

    /**
     * @return string|null
     */
    public function getActualAddress(): ?string
    {
        return $this->actualAddress;
    }

    /**
     * @return string
     */
    public function getEmail(): string
    {
        return $this->email;
    }

    /**
     * @return string|null
     */
    public function getPhone(): ?string
    {
        return $this->phone;
    }

    /**
     * @return string
     */
    public function getDirector(): string
    {
        return $this->director;
    }

    /**
     * @return string
     */
    public function getBin(): string
    {
        return $this->bin;
    }

    /**
     * @return string
     */
    public function getIik(): string
    {
        return $this->iik;
    }

    /**
     * @return string
     */
    public function getBankId(): string
    {
        return $this->bankId;
    }
}
