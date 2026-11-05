<?php declare(strict_types = 1);

namespace App\Models;

use MongoDB\Laravel\Eloquent\Model;
use Illuminate\Support\Carbon;
use App\Models\Presenters\Counterparty\Data as CounterpartyData;

/**
 * Сущность для хранения списка контрагентов
 *
 * @method \App\Models\Counterparty find(string $string)
 */
class Counterparty extends Model
{
    /**
     * @inheritdoc
     *
     * @var string
     */
    protected $connection = 'mongodb';

    /**
     * @inheritdoc
     *
     * @var string
     */
    protected $collection = 'counterparty';

    /**
     * @inheritdoc
     *
     * @var array
     */
    protected $guarded = [];

    /**
     * Наименование компании/физ лица контрагента
     *
     * @var string
     */
    public string $name;

    /**
     * ID пользователя
     *
     * @var int
     */
    public int $userId;

    /**
     * Юридический адрес контрагента
     *
     * @var string
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
     * Бизнес-Идентификационный Номер - БИН / ИИН контрагента
     *
     * @var string
     */
    public string $bin;

    /**
     * Индивидуальный идентификационный номер - ИИК контрагента
     *
     * @var string
     */
    public string $iik;

    /**
     * ID банка
     *
     * @var string
     */
    public string $bankId;

    /**
     * Презентер для работы с данными контрагента
     *
     * @var \App\Models\Presenters\Handbook\Bank\Data
     */
    public $presenter;

    /**
     * Возвращает ID банка
     *
     * @return string
     */
    public function getId(): string
    {
        return $this->getAttribute('_id');
    }

    /**
     * Возвращает ID пользователя
     *
     * @return int
     */
    public function getUserId(): int
    {
        return $this->getAttribute('userId');
    }

    /**
     * Устанавливает ID пользователя
     *
     * @param  int $userId
     * @return \App\Models\Counterparty
     */
    public function setUserId(int $userId): self
    {
        $this->setAttribute('userId', $userId);

        return $this;
    }

    /**
     * Возвращает наименование компании/физ лица контрагента
     *
     * @return string
     */
    public function getName(): string
    {
        return $this->getAttribute('name');
    }

    /**
     * Устанавливает наименование банка
     *
     * @param  string $name
     * @return \App\Models\Counterparty
     */
    public function setName(string $name): self
    {
        $this->setAttribute('name', $name);

        return $this;
    }

    /**
     * Возвращает юридический адрес контрагента
     *
     * @return string
     */
    public function getLegalAddress(): string
    {
        return $this->getAttribute('legalAddress');
    }

    /**
     * Устанавливает юридический адрес контрагента
     *
     * @param  string $legalAddress
     * @return \App\Models\Counterparty
     */
    public function setLegalAddress(string $legalAddress): self
    {
        $this->setAttribute('legalAddress', $legalAddress);

        return $this;
    }

    /**
     * Возвращает фактический адрес контрагента
     *
     * @return string|null
     */
    public function getActualAddress(): ?string
    {
        return $this->getAttribute('actualAddress');
    }

    /**
     * Устанавливает фактический адрес контрагента
     *
     * @param  string|null $actualAddress
     * @return \App\Models\Counterparty
     */
    public function setActualAddress(?string $actualAddress): self
    {
        $this->setAttribute('actualAddress', $actualAddress);

        return $this;
    }

    /**
     * Возвращает email контрагента
     *
     * @return string
     */
    public function getEmail(): string
    {
        return $this->getAttribute('email');
    }

    /**
     * Устанавливает email контрагента
     *
     * @param  string $email
     * @return \App\Models\Counterparty
     */
    public function setEmail(string $email): self
    {
        $this->setAttribute('email', $email);

        return $this;
    }

    /**
     * Возвращает номер контрагента
     *
     * @return string|null
     */
    public function getPhone(): ?string
    {
        return $this->getAttribute('phone');
    }

    /**
     * Устанавливает номер контрагента
     *
     * @param  string|null $phone
     * @return \App\Models\Counterparty
     */
    public function setPhone(?string $phone): self
    {
        $this->setAttribute('phone', $phone);

        return $this;
    }

    /**
     * Возвращает директора контрагента
     *
     * @return string
     */
    public function getDirector(): string
    {
        return $this->getAttribute('director');
    }

    /**
     * Устанавливает директора контрагента
     *
     * @param  string $director
     * @return \App\Models\Counterparty
     */
    public function setDirector(string $director): self
    {
        $this->setAttribute('director', $director);

        return $this;
    }

    /**
     * Возвращает БИН/ИИН контрагента
     *
     * @return string
     */
    public function getBin(): string
    {
        return $this->getAttribute('bin');
    }

    /**
     * Устанавливает БИН/ИИН контрагента
     *
     * @param  string $bin
     * @return \App\Models\Counterparty
     */
    public function setBin(string $bin): self
    {
        $this->setAttribute('bin', $bin);

        return $this;
    }

    /**
     * Возвращает ИИК контрагента
     *
     * @return string
     */
    public function getIik(): string
    {
        return $this->getAttribute('iik');
    }

    /**
     * Устанавливает ИИК контрагента
     *
     * @param  string $iik
     * @return \App\Models\Counterparty
     */
    public function setIik(string $iik): self
    {
        $this->setAttribute('iik', $iik);

        return $this;
    }

    /**
     * Возвращает ID банка концертанта
     *
     * @return string
     */
    public function getBankId(): string
    {
        return $this->getAttribute('bankId');
    }

    /**
     * Устанавливает ID банка
     *
     * @param  string $bankId
     * @return \App\Models\Counterparty
     */
    public function setBankId(string $bankId): self
    {
        $this->setAttribute('bankId', $bankId);

        return $this;
    }

    /**
     * Возвращает дату создания контрагента
     *
     * @return \Illuminate\Support\Carbon
     */
    public function getCreatedAt(): Carbon
    {
        return $this->created_at;
    }

    /**
     * Возвращает объект для работы с данными пользователя
     *
     * @return \App\Models\Presenters\Counterparty\Data
     */
    public function getPresenter(): CounterpartyData
    {
        if (!$this->presenter) {
            $this->presenter = new CounterpartyData($this);
        }

        return $this->presenter;
    }
}