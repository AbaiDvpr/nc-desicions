<?php declare(strict_types=1);

namespace App\Models\Handbook;

use App\Models\Presenters\Handbook\Article\Data;
use Illuminate\Support\Carbon;
use MongoDB\Laravel\Eloquent\Model;

/**
 * Сущность для хранения списка статей
 *
 * @method \App\Models\Handbook\Article find(string $string)
 */
class Article extends Model
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
    protected $collection = 'articles';

    /**
     * @inheritdoc
     *
     * @var array
     */
    protected $guarded = [];

    /**
     * Наименование статьи
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
     * Возвращает ID статьи
     *
     * @return string
     */
    public function getId(): string
    {
        return (string) $this->getAttribute('_id');
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
     * Устанавливает наименование статьи
     *
     * @param  string                       $title
     * @return \App\Models\Handbook\Article
     */
    public function setTitle(string $title): static
    {
        $this->setAttribute('title', $title);

        return $this;
    }

    /**
     * Возвращает статус проверки модерацией
     *
     * @return bool
     */
    public function getModerated(): bool
    {
        return (bool) $this->getAttribute('moderated');
    }

    /**
     * Устанавливает статус проверки модерацией
     *
     * @param  bool                         $moderated
     * @return \App\Models\Handbook\Article
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
     * Возвращает дату создания статьи
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
     * @return \App\Models\Presenters\Handbook\Article\Data
     */
    public function getPresenter(): Data
    {
        if (!$this->presenter) {
            $this->presenter = new Data($this);
        }

        return $this->presenter;
    }
}
