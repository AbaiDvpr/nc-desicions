<?php declare(strict_types=1);
namespace Tests\Helpers;

use Illuminate\Foundation\Testing\WithFaker;

class BaseHelper
{
    use WithFaker;

    /**
     * Инициализация для всех хелперов
     */
    public function __construct()
    {
        $this->setUpFaker();
    }

    /**
     * Случайные данные для создания пользователя
     *
     * @return array
     */
    public function getUserData(): array
    {
        return [
            'name'              => $this->faker->name,
            'admin'             => false,
            'email'             => $this->faker->email,
            'email_verified_at' => now(),
            'password'          => '123123',
            'remember_token'    => $this->faker->text(10),
            'driver'            => 'web',
        ];
    }
}
