<?php declare(strict_types=1);

namespace App\Models\Handbook\Forms;

use Illuminate\Http\Request;
use Illuminate\Support\Arr;

/**
 * Объект формы справочника направлений
 */
class Direction
{
    /**
     * Наименование направления
     *
     * @var string
     */
    protected string $title;

    /**
     * UUID компании к которой привязано направление
     *
     * @var string
     */
    protected string $companyUuid;

    /**
     * Заполняет свойства данными из запроса
     *
     * @param  \Illuminate\Http\Request             $request
     * @return \App\Models\Handbook\Forms\Direction
     */
    public static function createFromRequest(Request $request): Direction
    {
        $form = new self();

        $form->title       = (string) $request->get('title');
        $form->companyUuid = (string) $request->get('companyUuid');

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
     * @return string|null
     */
    public function getCompanyUuid(): ?string
    {
        return $this->companyUuid;
    }

    /**
     * Возвращает заполненную форму в виде массива
     *
     * @return array
     */
    public function toArray(): array
    {
        return [
            'title'       => $this->getTitle(),
            'companyUuid' => $this->getCompanyUuid(),
        ];
    }
}
