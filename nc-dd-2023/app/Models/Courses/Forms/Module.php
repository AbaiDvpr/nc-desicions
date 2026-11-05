<?php declare(strict_types=1);

namespace App\Models\Courses\Forms;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Форма заполнения модуля
 */
class Module
{
    /**
     * Включен ли модуль
     *
     * @var bool
     */
    protected bool $good;

    /**
     * Наименование модуля
     *
     * @var string
     */
    protected string $title;

    /**
     * Описание модуля
     *
     * @var string
     */
    protected string $description;

    /**
     * План модуля
     *
     * @var string
     */
    protected string $plan;

    /**
     * Заполняет свойства данными из запроса
     *
     * @param  \Illuminate\Foundation\Http\FormRequest $request
     * @return \App\Models\Courses\Forms\Module
     */
    public static function createFromRequest(FormRequest $request): Module
    {
        $form = new self();

        $form->good        = (bool) $request->input('good');
        $form->title       = (string) $request->input('title');
        $form->description = (string) $request->input('description');
        $form->plan        = (string) $request->input('plan');

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
     * Возвращает план
     *
     * @return string
     */
    public function getPlan(): string
    {
        return $this->plan;
    }

    /**
     * Возвращает заполненную форму в виде массива
     *
     * @return array
     */
    public function toArray(): array
    {
        return array_filter([
            'good'        => $this->getGood(),
            'title'       => $this->getTitle(),
            'description' => $this->getDescription(),
            'plan'        => $this->getPlan(),
        ]);
    }
}
