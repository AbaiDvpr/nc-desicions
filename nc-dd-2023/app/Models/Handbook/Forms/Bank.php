<?php declare(strict_types=1);

namespace App\Models\Handbook\Forms;

use Illuminate\Http\Request;
use Illuminate\Support\Arr;

/**
 * Объект формы банка и реквизитов
 */
class Bank
{
    /**
     * Наименование компании
     *
     * @var string
     */
    protected string $name;

    /**
     * Адрес банка
     *
     * @var string|null
     */
    protected ?string $address;

    /**
     * Телефоны банка
     *
     * @var array|null
     */
    protected ?array $phones;

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
     * Заполняет свойства данными из запроса
     *
     * @param  \Illuminate\Http\Request        $request
     * @return \App\Models\Handbook\Forms\Bank
     */
    public static function createFromRequest(Request $request): Bank
    {
        $form = new self();

        $form->name    = (string) $request->get('name');
        $form->address = (string) $request->get('address');
        $form->phones  = $form->setPhones((string) $request->get('phones'));
        $form->iban    = (string) $request->get('iban');
        $form->rnn     = (string) $request->get('rnn');
        $form->bin     = (string) $request->get('bin');
        $form->license = (string) $request->get('license');
        $form->bik     = (string) $request->get('bik');

        return $form;
    }

    /**
     * @return string|null
     */
    public function getName(): ?string
    {
        return $this->name;
    }

    /**
     * @return string|null
     */
    public function getAddress(): ?string
    {
        return $this->address;
    }

    /**
     * @return array|null
     */
    public function getPhones(): ?array
    {
        return $this->phones;
    }

    /**
     * Формирует массив телефонов и списка пришедших через запятую
     *
     * @param  string|null $phones
     * @return array
     */
    public function setPhones(?string $phones): array
    {
        if (!empty($phones)) {
            $phones = explode(',', $phones);
        }

        return collect(Arr::wrap($phones))->map(function ($phone) {
            return sanitizePhone($phone);
        })->filter()->toArray();
    }

    /**
     * @return string|null
     */
    public function getIban(): ?string
    {
        return $this->iban;
    }

    /**
     * @return string|null
     */
    public function getRnn(): ?string
    {
        return $this->rnn;
    }

    /**
     * @return string|null
     */
    public function getBin(): ?string
    {
        return $this->bin;
    }

    /**
     * @return string|null
     */
    public function getLicense(): ?string
    {
        return $this->license;
    }

    /**
     * @return string|null
     */
    public function getBik(): ?string
    {
        return $this->bik;
    }

    /**
     * Возвращает заполненную форму в виде массива
     *
     * @return array
     */
    public function toArray(): array
    {
        return [
            'name'    => $this->getName(),
            'address' => $this->getAddress(),
            'phones'  => $this->getPhones(),
            'iban'    => $this->getIban(),
            'rnn'     => $this->getRnn(),
            'bin'     => $this->getBin(),
            'license' => $this->getLicense(),
            'bik'     => $this->getBik(),
        ];
    }
}
