<?php declare(strict_types=1);

namespace App\Models\Courses\Forms;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Форма заполнения урока
 */
class Lesson
{
    /**
     * Включен ли урок
     *
     * @var bool
     */
    protected bool $good;

    /**
     * Наименование урока
     *
     * @var string
     */
    protected string $title;

    /**
     * Описание урока
     *
     * @var string
     */
    protected string $description;

    /**
     * Ссылка на youtube ролик
     *
     * @var string
     */
    protected string $linkVideo;

    /**
     * Ссылка на доп. материалы
     *
     * @var string
     */
    protected string $linkAdditional;

    /**
     * ID модуля
     *
     * @var string
     */
    protected string $moduleId;

    /**
     * Заполняет свойства данными из запроса
     *
     * @param  \Illuminate\Foundation\Http\FormRequest $request
     * @return \App\Models\Courses\Forms\Lesson
     */
    public static function createFromRequest(FormRequest $request): Lesson
    {
        $form = new self();

        $form->good           = (bool) $request->input('good');
        $form->title          = (string) $request->input('title');
        $form->description    = (string) $request->input('description');
        $form->linkVideo      = (string) $request->input('linkVideo');
        $form->linkAdditional = (string) $request->input('linkAdditional');
        $form->moduleId       = (string) $request->input('moduleId');

        return $form;
    }

    /**
     * Возвращает on/off
     *
     * @return bool
     */
    public function getGood(): bool
    {
        return $this->good;
    }

    /**
     * Возвращает наименование
     *
     * @return string
     */
    public function getTitle(): string
    {
        return $this->title;
    }

    /**
     * Возвращает описание
     *
     * @return string
     */
    public function getDescription(): string
    {
        return $this->description;
    }

    /**
     * Возвращает ссылку на youtube ролик
     *
     * @return string
     */
    public function getLinkVideo(): string
    {
        return $this->linkVideo;
    }

    /**
     * Возвращает ссылку на доп. материалы
     *
     * @return string
     */
    public function getLinkAdditional(): string
    {
        return $this->linkAdditional;
    }

    /**
     * Возвращает ID модуля
     *
     * @return string
     */
    public function getModuleId(): string
    {
        return $this->moduleId;
    }

    /**
     * Возвращает заполненную форму в виде массива
     *
     * @return array
     */
    public function toArray(): array
    {
        return array_filter([
            'good'           => $this->getGood(),
            'title'          => $this->getTitle(),
            'description'    => $this->getDescription(),
            'linkVideo'      => $this->getLinkVideo(),
            'linkAdditional' => $this->getLinkAdditional(),
            'moduleId'       => $this->getModuleId(),
        ]);
    }
}
