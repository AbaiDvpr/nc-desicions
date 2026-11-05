<?php declare(strict_types=1);

namespace App\Models\Invoice\Form;

use Illuminate\Http\Request;

/**
 * Объект заполненной формы при оплате тарифа
 */
class Payment
{
    /**
     * E-mail указанный в форме
     *
     * @var string
     */
    protected string $email;

    /**
     * Телефон указанный в форме
     *
     * @var string
     */
    protected string $phone;

    /**
     * Описание указанное в форме
     *
     * @var string|null
     */
    protected ?string $description;

    /**
     * Заполняет свойства данными из запроса
     *
     * @param  \Illuminate\Http\Request         $request
     * @return \App\Models\Invoice\Form\Payment
     */
    public static function createFromRequest(Request $request): Payment
    {
        $form = new self();

        $form->email       = (string) $request->get('email');
        $form->phone       = sanitizePhone($request->get('phone'));
        $form->description = $request->get('description');

        return $form;
    }

    /**
     * @return string
     */
    public function getEmail(): string
    {
        return $this->email;
    }

    /**
     * @return string
     */
    public function getPhone(): string
    {
        return $this->phone;
    }

    /**
     * @return string|null
     */
    public function getDescription(): ?string
    {
        return $this->description;
    }
}
