<?php declare(strict_types=1);

namespace App\Models\Handbook\Forms;

use Illuminate\Http\Request;

/**
 * Объект формы со статьями доходов/расходов
 */
class Article
{
    /**
     * Наименование статьи
     *
     * @var string
     */
    protected string $title;

    /**
     * Заполняет свойства данными из запроса
     *
     * @param  \Illuminate\Http\Request           $request
     * @return \App\Models\Handbook\Forms\Article
     */
    public static function createFromRequest(Request $request): Article
    {
        $form = new self();

        $form->title = (string) $request->get('title');

        return $form;
    }

    /**
     * @return string|null
     */
    public function getTitle(): ?string
    {
        return $this->title;
    }

    /**
     * @param  string                             $title
     * @return \App\Models\Handbook\Forms\Article
     */
    public function setTitle(string $title): self
    {
        $this->title = $title;

        return $this;
    }

    /**
     * Возвращает заполненную форму в виде массива
     *
     * @return array
     */
    public function toArray(): array
    {
        return [
            'title' => $this->getTitle(),
        ];
    }
}
