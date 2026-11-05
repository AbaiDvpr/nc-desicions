<?php declare(strict_types=1);

namespace App\Repositories;

use App\Repositories\Handbook\Article;
use App\Repositories\Handbook\Bank;
use App\Repositories\Handbook\Common;
use App\Repositories\Handbook\Direction;

/**
 * Репозиторий для работы со справочниками
 */
class Handbook extends BaseRepository
{
    /**
     * Репозиторий для работы со справочником
     *
     * @var \App\Repositories\Handbook\Common
     */
    public $common;

    /**
     * Репозиторий для работы со справочником банков
     *
     * @var \App\Repositories\Handbook\Bank
     */
    public $bank;

    /**
     * Репозиторий для работы со справочником статей доходов/расходов
     *
     * @var \App\Repositories\Handbook\Article
     */
    public $article;

    /**
     * Репозиторий для работы со справочником направлений
     *
     * @var \App\Repositories\Handbook\Direction
     */
    public $direction;

    /**
     * Репозиторий для работы со справочником
     *
     * @return \App\Repositories\Handbook\Common
     */
    public function getCommon(): Common
    {
        if (!$this->common) {
            $this->common = new Common();
        }

        return $this->common;
    }

    /**
     * Репозиторий для работы с банками
     *
     * @return \App\Repositories\Handbook\Bank
     */
    public function getBank(): Bank
    {
        if (!$this->bank) {
            $this->bank = new Bank();
        }

        return $this->bank;
    }

    /**
     * Репозиторий для работы со статьями доходов/расходов
     *
     * @return \App\Repositories\Handbook\Article
     */
    public function getArticle(): Article
    {
        if (!$this->article) {
            $this->article = new Article();
        }

        return $this->article;
    }

    /**
     * Репозиторий для работы со статьями доходов/расходов
     *
     * @return \App\Repositories\Handbook\Direction
     */
    public function getDirection(): Direction
    {
        if (!$this->direction) {
            $this->direction = new Direction();
        }

        return $this->direction;
    }
}
