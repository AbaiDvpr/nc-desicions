<?php declare(strict_types=1);

namespace App\Models\Company\Forms;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Request;

/**
 * Объект заполняемой формы компании
 */
class Company
{
    /**
     * UUID
     *
     * @var null|string
     */
    protected ?string $uuid = null;

    /**
     * Наименование компании
     *
     * @var string
     */
    protected string $name;

    /**
     * Организационно-правовая форма
     *
     * @var int
     */
    protected int $form;

    /**
     * Форма налогообложения
     *
     * @var int
     */
    protected int $tax_regime;

    /**
     * НДС
     *
     * @var bool
     */
    protected bool $nds;

    /**
     * Описание
     *
     * @var null|string
     */
    protected ?string $description = null;

    /**
     * Валюта расчётов
     *
     * @var string
     */
    protected string $currency;

    /**
     * Заполняет свойства данными из запроса
     *
     * @param  \Illuminate\Foundation\Http\FormRequest $request
     * @return \App\Models\Company\Forms\Company
     */
    public static function createFromRequest(FormRequest $request): Company
    {
        $form = new self();

        $form->setUuid($request);
        $form->setDescription($request);

        $form->name        = (string) $request->input('name');
        $form->form        = (int) $request->input('form');
        $form->tax_regime  = (int) $request->input('tax_regime');
        $form->nds         = (bool) $request->input('nds');
        $form->currency    = (string) $request->input('currency');

        return $form;
    }

    /**
     * Возвращает UUID
     *
     * @return null|string
     */
    public function getUuid(): ?string
    {
        return $this->uuid;
    }

    /**
     * Устанавливает UUID
     *
     * @param  \Illuminate\Http\Request $request
     * @return \App\Models\Company\Forms\Company
     */
    public function setUuid(Request $request): self
    {
        if ($request->input('uuid')) {
            $this->uuid = (string) $request->input('uuid');
        }

        return $this;
    }

    /**
     * Возвращает наименование компании
     *
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Возвращает организационно-правовую форму
     *
     * @return int
     */
    public function getForm(): int
    {
        return $this->form;
    }

    /**
     * Возвращает форму налогообложения
     *
     * @return int
     */
    public function getTaxRegime(): int
    {
        return $this->tax_regime;
    }

    /**
     * Возвращает НДС
     *
     * @return bool
     */
    public function getNds(): bool
    {
        return $this->nds;
    }

    /**
     * Возвращает описание
     *
     * @return null|string
     */
    public function getDescription(): ?string
    {
        return $this->description;
    }

    /**
     * Устанавливает описание
     *
     * @param  \Illuminate\Http\Request         $request
     * @return \App\Models\Company\Forms\Company
     */
    public function setDescription(Request $request): self
    {
        if ($request->input('description')) {
            $this->description = (string) $request->input('description');
        }

        return $this;
    }

    /**
     * Возвращает валюту расчётов
     *
     * @return string
     */
    public function getCurrency(): string
    {
        return $this->currency;
    }

    /**
     * Возвращает заполненную форму в виде массива
     *
     * @return array
     */
    public function toArray(): array
    {
        return array_filter([
            'name'        => $this->getName(),
            'form'        => $this->getForm(),
            'tax_regime'  => $this->getTaxRegime(),
            'nds'         => $this->getNds(),
            'description' => $this->getDescription(),
            'currency'    => $this->getCurrency(),
        ]);
    }
}
