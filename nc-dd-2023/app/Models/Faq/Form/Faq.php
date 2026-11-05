<?php declare(strict_types=1);

namespace App\Models\Faq\Form;

use App\Http\Requests\Frontend\Faq\StoreRequest;

/**
 * Объект заполняемой формы FAQ
 */
class Faq
{
    /**
     * Имя
     *
     * @var string
     */
    protected string $name;

    /**
     * Фамилия
     *
     * @var string
     */
    protected string $surname;

    /**
     * Email
     *
     * @var string
     */
    protected string $email;

    /**
     * Телефон
     *
     * @var string
     */
    protected string $phone;

    /**
     * Вопрос
     *
     * @var string
     */
    protected string $question;

    /**
     * Язык
     *
     * @var string
     */
    protected string $lang;

    /**
     * IP пользователя
     *
     * @var string
     */
    protected string $ip;

    /**
     * Заполняет свойства данными из запроса
     *
     * @param  \App\Http\Requests\Frontend\Faq\StoreRequest $request
     * @return \App\Models\Faq\Form\Faq
     */
    public static function createFromRequest(StoreRequest $request): Faq
    {
        $form = new self();

        $form->name     = (string) $request->get('name');
        $form->surname  = (string) $request->get('surname');
        $form->email    = (string) $request->get('email');
        $form->phone    = sanitizePhone($request->get('phone'));
        $form->question = $request->get('question');
        $form->lang     = app()->getLocale();
        $form->ip       = $request->ip();

        return $form;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return string
     */
    public function getSurname(): string
    {
        return $this->surname;
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
     * @return string
     */
    public function getQuestion(): string
    {
        return $this->question;
    }

    /**
     * @return string
     */
    public function getLang(): string
    {
        return $this->lang;
    }

    /**
     * @return string
     */
    public function getIp(): string
    {
        return $this->ip;
    }

    /**
     * Возвращает заполненную форму в виде массива
     *
     * @return array
     */
    public function toArray(): array
    {
        return [
            'name'     => $this->getName(),
            'surname'  => $this->getSurname(),
            'email'    => $this->getEmail(),
            'phone'    => $this->getPhone(),
            'question' => $this->getQuestion(),
            'lang'     => $this->getLang(),
            'ip'       => $this->getIp(),
        ];
    }
}
