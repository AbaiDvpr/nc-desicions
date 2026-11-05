<?php declare(strict_types=1);

namespace App\Models\Handbook\Forms;

use Illuminate\Http\Request;

/**
 * Объект формы для справочника
 */
class Handbook
{
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
     * Тип справочника
     *
     * @var int
     */
    protected int $type;

    /**
     * Заполняет свойства данными из запроса
     *
     * @param  \Illuminate\Http\Request           $request
     * @return \App\Models\Handbook\Forms\Handbook
     */
    public static function createFromRequest(Request $request): Handbook
    {
        $form = new self();

        $form->title       = (string) $request->get('title');
        $form->companyUuid = (string) $request->get('companyUuid');
        $form->type        = (int) $request->get('type');

        return $form;
    }

    /**
     * @return string
     */
    public function getCompanyUuid(): string
    {
        return $this->companyUuid;
    }

    /**
     * @return string
     */
    public function getTitle(): string
    {
        return $this->title;
    }

    /**
     * @return int
     */
    public function getType(): int
    {
        return $this->type;
    }

    /**
     * Возвращает заполненную форму в виде массива
     *
     * @return array
     */
    public function toArray(): array
    {
        return [
            'companyUuid' => $this->getCompanyUuid(),
            'title'       => $this->getTitle(),
            'type'        => $this->getType(),
        ];
    }
}
