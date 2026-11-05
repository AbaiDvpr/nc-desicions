<?php declare(strict_types=1);

namespace App\Modules\Payment\Wooppay\Objects\Request;

/**
 * Запрос входа
 */
class Login
{
    /**
     * Поле имени пользователя
     *
     * @var string
     */
    protected $username;

    /**
     * Поле пароля
     *
     * @var string
     */
    protected $password;

    /**
     * Поле капчи
     *
     * @var string
     */
    protected $captcha;

    public function getUsername(): string
    {
        return $this->username;
    }

    public function setUsername(string $username): self
    {
        $this->username = $username;

        return $this;
    }

    public function getPassword(): string
    {
        return $this->password;
    }

    public function setPassword(string $password): self
    {
        $this->password = $password;

        return $this;
    }

    public function getCaptcha(): string
    {
        return $this->captcha;
    }

    public function setCaptcha(string $captcha): self
    {
        $this->captcha = $captcha;

        return $this;
    }
}
