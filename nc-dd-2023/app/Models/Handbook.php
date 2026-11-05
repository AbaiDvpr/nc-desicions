<?php declare(strict_types=1);

namespace App\Models;

use App\Models\Presenters\Handbook\Data;
use Illuminate\Support\Carbon;
use MongoDB\Laravel\Eloquent\Model;

/**
 * Сущность для хранения справочников
 *
 * @method \App\Models\Handbook find(string $string)
 */
class Handbook extends Model
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
    protected $collection = 'handbook';

    /**
     * @inheritdoc
     *
     * @var array
     */
    protected $guarded = [];

    /**
     * UUID компании
     *
     * @var string
     */
    protected string $companyUuid;

    /**
     * Наименование
     *
     * @var string
     */
    protected string $title;

    /**
     * Тип
     *
     * @var int
     */
    protected int $type;

    /**
     * Типы
     */
    const TYPE_GROUP = 1; // Группа

    /**
     * Презентер для работы с представлением данных
     *
     * @var \App\Models\Presenters\Handbook\Data
     */
    public $presenter;

    /**
     * @return string
     */
    public function getId(): string
    {
        return (string) $this->getAttribute('_id');
    }

    /**
     * @return string
     */
    public function getCompanyUuid(): string
    {
        return (string) $this->getAttribute('companyUuid');
    }

    /**
     * @param  string              $companyUuid
     * @return \App\Models\Handbook
     */
    public function setCompanyUuid(string $companyUuid): self
    {
        $this->setAttribute('companyUuid', $companyUuid);

        return $this;
    }

    /**
     * @param  string              $title
     * @return \App\Models\Handbook
     */
    public function setTitle(string $title): self
    {
        $this->setAttribute('title', $title);

        return $this;
    }

    /**
     * @return string
     */
    public function getTitle(): string
    {
        return (string) $this->getAttribute('title');
    }

    /**
     * @param  int                 $type
     * @return \App\Models\Handbook
     */
    public function setType(int $type): self
    {
        $this->setAttribute('type', $type);

        return $this;
    }

    /**
     * @return int
     */
    public function getType(): int
    {
        return (int) $this->getAttribute('type');
    }


    /**
     * @return \Illuminate\Support\Carbon
     */
    public function getCreatedAt(): Carbon
    {
        return $this->created_at;
    }

    /**
     * Презентер для работы с публичными данными
     *
     * @return \App\Models\Presenters\Handbook\Data
     */
    public function getPresenter(): Data
    {
        if (!$this->presenter) {
            $this->presenter = new Data($this);
        }

        return $this->presenter;
    }
}
