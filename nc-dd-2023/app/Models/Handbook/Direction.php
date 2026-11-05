<?php declare(strict_types=1);

namespace App\Models\Handbook;

use App\Models\Presenters\Handbook\Direction\Data;
use Illuminate\Support\Carbon;
use MongoDB\Laravel\Eloquent\Model;

/**
 * Сущность для хранения списка направлений компании
 *
 * @method \App\Models\Handbook\Direction find(string $string)
 */
class Direction extends Model
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
    protected $collection = 'directions';

    /**
     * @inheritdoc
     *
     * @var array
     */
    protected $guarded = [];

    /**
     * UUID компании к которой привязано направление
     *
     * @var string
     */
    protected string $companyUuid;

    /**
     * Наименование направления
     *
     * @var string
     */
    protected string $title;

    /**
     * Статус "Проверено модерацией"
     *
     * @var bool
     */
    protected bool $moderated;

    /**
     * Презентер для работы с представлением данных
     *
     * @var \App\Models\Presenters\Handbook\Article\Data
     */
    public $presenter;

    /**
     * Возвращает ID направления
     *
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
     * @param  string                         $companyUuid
     * @return \App\Models\Handbook\Direction
     */
    public function setCompanyUuid(string $companyUuid): static
    {
        $this->setAttribute('companyUuid', $companyUuid);

        return $this;
    }

    /**
     * Возвращает наименование статьи
     *
     * @return string
     */
    public function getTitle(): string
    {
        return (string) $this->getAttribute('title');
    }

    /**
     * @param  string                         $title
     * @return \App\Models\Handbook\Direction
     */
    public function setTitle(string $title): static
    {
        $this->setAttribute('title', $title);

        return $this;
    }

    /**
     * @return bool
     */
    public function getModerated(): bool
    {
        return (bool) $this->getAttribute('moderated');
    }

    /**
     * @param  bool                           $moderated
     * @return \App\Models\Handbook\Direction
     */
    public function setModerated(bool $moderated): static
    {
        $this->setAttribute('moderated', $moderated);

        return $this;
    }

    /**
     * Проверено ли модерацией
     *
     * @return bool
     */
    public function isModerated(): bool
    {
        return $this->getModerated();
    }

    /**
     * Возвращает дату создания направления
     *
     * @return \Illuminate\Support\Carbon
     */
    public function getCreatedAt(): Carbon
    {
        return $this->created_at;
    }

    /**
     * Презентер для работы с публичными данными
     *
     * @return \App\Models\Presenters\Handbook\Direction\Data
     */
    public function getPresenter(): Data
    {
        if (!$this->presenter) {
            $this->presenter = new Data($this);
        }

        return $this->presenter;
    }
}
