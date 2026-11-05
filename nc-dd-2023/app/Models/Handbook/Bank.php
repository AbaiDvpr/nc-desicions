<?php declare(strict_types=1);

namespace App\Models\Handbook;

use App\Models\Handbook\Bank\Requisites;
use App\Models\Presenters\Handbook\Bank\Data as BankData;
use Illuminate\Support\Carbon;
use MongoDB\Laravel\Eloquent\Model;

/**
 * Сущность для хранения списка банков
 *
 * @method \App\Models\Handbook\Bank find(string $string)
 */
class Bank extends Model
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
    protected $collection = 'bank';

    /**
     * @inheritdoc
     *
     * @var array
     */
    protected $guarded = [];

    /**
     * Наименование банка
     *
     * @var string
     */
    protected string $name;

    /**
     * Реквизиты банка
     *
     * @var \App\Models\Handbook\Bank\Requisites
     */
    public $requisites;

    /**
     * Презентер для работы с данными банка
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
        return (string) $this->getAttribute('_id');
    }

    /**
     * Возвращает наименование банка
     *
     * @return string
     */
    public function getName(): string
    {
        return (string) $this->getAttribute('name');
    }

    /**
     * Устанавливает наименование банка
     *
     * @param  string                    $name
     * @return \App\Models\Handbook\Bank
     */
    public function setName(string $name): static
    {
        $this->setAttribute('name', $name);

        return $this;
    }

    /**
     * Возвращает дату создания банка
     *
     * @return \Illuminate\Support\Carbon
     */
    public function getCreatedAt(): Carbon
    {
        return $this->created_at;
    }

    /**
     * Возвращает объект для работы с реквизитами банка
     *
     * @return \App\Models\Handbook\Bank\Requisites
     */
    public function getRequisites(): Requisites
    {
        if (!$this->requisites) {
            $this->requisites = new Requisites($this);
        }

        return $this->requisites;
    }

    /**
     * Возвращает объект для работы с данными пользователя
     *
     * @return \App\Models\Presenters\Handbook\Bank\Data
     */
    public function getPresenter(): BankData
    {
        if (!$this->presenter) {
            $this->presenter = new BankData($this);
        }

        return $this->presenter;
    }

    /**
     * Возвращает модель в виде массива
     *
     * @return array
     */
    public function toArray(): array
    {
        return array_merge(
            parent::toArray(),
            [
                'requisites' => $this->getRequisites()->toArray()
            ],
        );
    }
}
