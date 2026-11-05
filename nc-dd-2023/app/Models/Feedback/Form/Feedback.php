<?php declare(strict_types=1);

namespace App\Models\Feedback\Form;

use App\Http\Requests\Frontend\Feedback\StoreRequest;

/**
 * Объект заполняемой формы Обратная связь
 */
class Feedback
{
    /**
     * Имя
     *
     * @var string
     */
    protected string $name;

    /**
     * Телефон
     *
     * @var string
     */
    protected string $phone;

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
     * @param  \App\Http\Requests\Frontend\Feedback\StoreRequest $request
     * @return \App\Models\Feedback\Form\Feedback
     */
    public static function createFromRequest(StoreRequest $request): Feedback
    {
        $form = new self();

        $form->name  = (string) $request->get('name');
        $form->phone = sanitizePhone($request->get('phone'));
        $form->lang  = app()->getLocale();
        $form->ip    = $request->ip();

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
    public function getPhone(): string
    {
        return $this->phone;
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
            'name'  => $this->getName(),
            'phone' => $this->getPhone(),
            'lang'  => $this->getLang(),
            'ip'    => $this->getIp(),
        ];
    }
}
