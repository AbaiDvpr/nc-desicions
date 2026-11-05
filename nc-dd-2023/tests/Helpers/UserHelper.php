<?php declare(strict_types=1);
namespace Tests\Helpers;

use App\DI;
use App\Models\User;

/**
 * Хелпер для работы с пользователями
 */
class UserHelper extends BaseHelper
{
    /**
     * Тема письма отправляемого при регистрации
     */
    const REGISTRATION_MAIL_SUBJECT = 'Регистрация - на ncdecision.com';

    /**
     * Создает тестового пользователя
     *
     * @param  string           $role
     * @return \App\Models\User
     */
    public function createUser(string $role = 'client'): User
    {
        $user = DI::getRepository()->getUser()->create($this->getUserData());

        $user->assignRole($role);

        return $user;
    }

    /**
     * Возвращает рандомный номер телефона в формате казахстанских номеров
     *
     * @return string
     */
    public function getRandomPhone(): string
    {
        $phonePrefixes = ['700', '701', '705', '771', '777', '778'];

        return '+7 (' . $phonePrefixes[rand(0, count($phonePrefixes) - 1)] . ') ' . mt_rand(100, 999) . '-' . mt_rand(10, 99) . '-' . mt_rand(10, 99);
    }
}
