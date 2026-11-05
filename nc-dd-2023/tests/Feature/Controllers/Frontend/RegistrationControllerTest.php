<?php declare(strict_types=1);

namespace Tests\Feature\Controllers\Frontend;

use App\DI;
use App\Repositories\User\Exceptions\NotFoundException;
use Faker\Factory;
use Tests\Helpers\UserHelper;

/**
 * Проверка формы регистрации
 */
class RegistrationControllerTest extends BaseController
{
    /**
     * Проверяем главную страницу регистрации
     *
     * @return void
     */
    public function testRegistrationPage(): void
    {
        $response = $this->get(route('registration.index'));

        $response->assertStatus(200);

        // проверяем наличие нужных элементов в форме
        $response->assertViewHasAll([
            'name'                  => '',
            'email'                 => '',
            'password'              => '',
            'password_confirmation' => '',
        ]);
    }

    /**
     * Проверяем валидацию формы регистрации
     *
     * @dataProvider formErrorsProvider
     *
     * @param  array $fields
     * @param  array $errors
     * @return void
     */
    public function testInvalidRegistrationPage(array $fields, array $errors): void
    {
        $response = $this->get(route('registration.index'));

        $response->assertStatus(200);

        // проверяем валидацию формы
        $this->post(route('registration.post'), $fields)
            ->assertSessionHasErrors($errors);
    }

    /**
     * Проверка успешной регистрации и отправки письма подтверждения
     *
     * @return void
     * @throws \App\Repositories\User\Exceptions\NotFoundException
     */
    public function testValidRegistration(): void
    {
        $email    = $this->faker->freeEmail;
        $name     = $this->faker->name;
        $password = $this->faker->password . '#Ff1';

        $response = $this->post(route('registration.post'), [
            'name'                  => $name,
            'email'                 => $email,
            'password'              => $password,
            'password_confirmation' => $password,
            'g-recaptcha-response'  => $this->faker->text(10),
        ]);

        $response->assertSessionHas('success', true);

        $user = DI::getRepository()->getUser()->findByEmail($email);

        if (is_null($user)) {
            throw new NotFoundException(sprintf('User %s not found', $email));
        }

        $this->assertEquals($name, $user->getName());
        $this->assertEquals($email, $user->getEmail());

        // засыпаем, для того, чтоб письмо успело появиться в Mailhog-е
        usleep(500000);

        $this->assertEquals(
            UserHelper::REGISTRATION_MAIL_SUBJECT,
            DI::getRepository()->getMailHog()->getSubjectForLastMessage()
        );

        // Удаляем созданного пользователя
        $this->userDestroyed[] = $user->getAuthIdentifier();
    }

    /**
     * Проверка срабатывания XSS скрипта в поле ФИО
     *
     * @return void
     */
    public function testFIOInputXSS(): void
    {
        // Отправляем POST-запрос на маршрут, который обрабатывает ввод данных
        $response = $this->post(route('registration.post'), [
            'name'                  => '<script>alert("XSS")</script>',
            'email'                 => 'test_test@gmail.com',
            'password'              => 'TestTest_123#',
            'password_confirmation' => 'TestTest_123#',
            'g-recaptcha-response'  => $this->faker->text(10),
        ]);

        // Проверяем, что в ответе отсутствует внедренный скрипт XSS
        $response->assertDontSee('<script>alert("XSS")</script>');
    }

    /**
     * Проверка срабатывания SQL запроса в поле ФИО
     *
     * @return void
     */
    public function testFIOInputSQLInjection(): void
    {
        // Отправляем POST-запрос на маршрут, который обрабатывает ввод данных
        $this->post(route('registration.post'), [
            'name'                  => '1; DROP TABLE users;',
            'email'                 => 'test_test@gmail.com',
            'password'              => 'TestTest_123#',
            'password_confirmation' => 'TestTest_123#',
        ]);

        // Проверяем, что в базе данных отсутствует запись, соответствующая введенным данным
        $this->assertDatabaseMissing('users', [
            'name' => '1; DROP TABLE users;',
        ]);
    }

    /**
     * Проверка срабатывания XSS скрипта в поле E-mail
     *
     * @return void
     */
    public function testEmailInputXSS(): void
    {
        // Отправляем POST-запрос на маршрут, который обрабатывает ввод данных
        $response = $this->post(route('registration.post'), [
            'name'                  => 'TestTest',
            'email'                 => '<script>alert("XSS")</script>',
            'password'              => 'TestTest_123#',
            'password_confirmation' => 'TestTest_123#',
            'g-recaptcha-response'  => $this->faker->text(10),
        ]);

        // Проверяем, что в ответе отсутствует внедренный скрипт XSS
        $response->assertDontSee('<script>alert("XSS")</script>');
    }

    /**
     * Проверка срабатывания SQL запроса в поле E-mail
     *
     * @return void
     */
    public function testEmailInputSQLInjection(): void
    {
        // Отправляем POST-запрос на маршрут, который обрабатывает ввод данных
        $this->post(route('registration.post'), [
            'name'                  => 'TestTest',
            'email'                 => '1; DROP TABLE users;',
            'password'              => 'TestTest_123#',
            'password_confirmation' => 'TestTest_123#',
            'g-recaptcha-response'  => $this->faker->text(10),
        ]);

        // Проверяем, что в базе данных отсутствует запись, соответствующая введенным данным
        $this->assertDatabaseMissing('users', [
            'email' => '1; DROP TABLE users;',
        ]);
    }

    /**
     * Дата провайдер с ошибками ввода данные в форму регистрации
     *
     * @return array[]
     */
    public static function formErrorsProvider(): array
    {
        $faker = Factory::create();

        return [
            [
                'fields' => [
                    'name'                  => null,
                    'email'                 => null,
                    'password'              => null,
                    'password_confirmation' => null,
                    'g-recaptcha-response'  => null,
                ],
                'errors' => [
                    'name'                  => 'ФИО не заполнено',
                    'email'                 => 'E-mail не заполнен',
                    'password'              => 'Пароль не указан',
                    'password_confirmation' => 'Подтвердите пароль',
                    'g-recaptcha-response'  => 'Подтвердите, что вы не робот',
                ],
            ],
            [
                'fields' => [
                    'name'                  => '',
                    'email'                 => '',
                    'password'              => '',
                    'password_confirmation' => '',
                    'g-recaptcha-response'  => '',
                ],
                'errors' => [
                    'name'                  => 'ФИО не заполнено',
                    'email'                 => 'E-mail не заполнен',
                    'password'              => 'Пароль не указан',
                    'password_confirmation' => 'Подтвердите пароль',
                    'g-recaptcha-response'  => 'Подтвердите, что вы не робот',
                ],
            ],
            [
                'fields' => [
                    'name'                  => ' ',
                    'email'                 => ' ',
                    'password'              => ' ',
                    'password_confirmation' => ' ',
                    'g-recaptcha-response'  => ' ',
                ],
                'errors' => [
                    'name'                  => 'ФИО не заполнено',
                    'email'                 => 'E-mail не заполнен',
                    'password'              => 'Пароль не указан',
                    'password_confirmation' => 'Подтвердите пароль',
                ],
            ],
            [
                'fields' => [
                    'name'                  => 'TestTest',
                    'email'                 => 'test_test@mailru',
                    'password'              => '111111',
                    'password_confirmation' => $faker->password,
                    'g-recaptcha-response'  => $faker->text(10),
                ],
                'errors' => [
                    'email'    => 'E-mail заполнен не верно',
                    'password' => 'Пароль должен содержать хоть один заглавный и строчный символ',
                ],
            ],
            [
                'fields' => [
                    'name'                  => 'TestTest',
                    'email'                 => 'test_test@@mail.ru',
                    'password'              => $faker->password,
                    'password_confirmation' => '222222',
                    'g-recaptcha-response'  => $faker->text(10),
                ],
                'errors' => [
                    'email'    => 'E-mail заполнен не верно',
                    'password' => 'Пароли не совпадают',
                ],
            ],
            [
                'fields' => [
                    'name'                  => 'TestTest',
                    'email'                 => 'test_test@mail..ru',
                    'password'              => $faker->password,
                    'password_confirmation' => '',
                    'g-recaptcha-response'  => $faker->text(10),
                ],
                'errors' => [
                    'email'                 => 'E-mail заполнен не верно',
                    'password_confirmation' => 'Подтвердите пароль',
                ],
            ],
            [
                'fields' => [
                    'name'                  => 'TestTest',
                    'email'                 => 'test_te st@mail.ru',
                    'password'              => '1',
                    'password_confirmation' => $faker->password,
                    'g-recaptcha-response'  => $faker->text(10),
                ],
                'errors' => [
                    'email'    => 'E-mail заполнен не верно',
                    'password' => 'Пароль должен быть не менее 6 символов',
                ],
            ],
            [
                'fields' => [
                    'name'                  => 'Соображения в новая сложившаяся участниками и особенности по порядка, дальнейших и выполнять модель п',
                    'email'                 => 'client@nc.com',
                    'password'              => '12_Gg',
                    'password_confirmation' => '12_Gg',
                    'g-recaptcha-response'  => $faker->text(10),
                ],
                'errors' => [
                    'name'      => 'ФИО не более 100 символов',
                    'password'  => 'Пароль должен быть не менее 6 символов',
                ],
            ],
            [
                'fields' => [
                    'name'                  => 'С',
                    'email'                 => $faker->email,
                    'password'              => $faker->password,
                    'password_confirmation' => $faker->password,
                    'g-recaptcha-response'  => $faker->text(10),
                ],
                'errors' => [
                    'name'      => 'ФИО не менее 2 символов',
                ],
            ],
            [
                'fields' => [
                    'name'                  => '',
                    'email'                 => 'client@nc.com',
                    'password'              => $faker->password,
                    'password_confirmation' => $faker->password,
                    'g-recaptcha-response'  => $faker->text(10),
                ],
                'errors' => [
                    'email'    => 'E-mail уже зарегистрирован',
                ],
            ],
        ];
    }

    /**
     * Проверяем загрузку страницы восстановления пароля
     *
     * @return void
     */
    public function testRegistrationRestorePage(): void
    {
        $response = $this->get(route('registration.restore'));

        $response->assertStatus(200);

        // проверяем наличие нужных элементов в форме
        $response->assertViewHasAll([
            'email' => '',
        ]);
    }

    /**
     * Проверяем валидацию формы восстановления пароля
     *
     * @dataProvider restoreFormErrorsProvider
     *
     * @param  array $fields
     * @param  array $errors
     * @return void
     */
    public function testInvalidRestorePass(array $fields, array $errors): void
    {
        $response = $this->get(route('registration.restore'));

        $response->assertStatus(200);

        // проверяем валидацию формы
        $this->post(route('registration.restore.post'), $fields)
            ->assertSessionHasErrors($errors);
    }

    /**
     * Проверка срабатывания SQL запроса в поле E-mail формы восстановления пароля
     *
     * @return void
     */
    public function testRestoreInputSQLInjection(): void
    {
        // Отправляем POST-запрос на маршрут, который обрабатывает ввод данных
        $this->post(route('registration.restore.post'), [
            'email' => '1; DROP TABLE users;',
        ]);

        // Проверяем, что в базе данных отсутствует запись, соответствующая введенным данным
        $this->assertDatabaseMissing('users', [
            'email' => '1; DROP TABLE users;',
        ]);
    }

    /**
     * Проверка срабатывания XSS скрипта в поле E-mail формы восстановления пароля
     *
     * @return void
     */
    public function testRestoreEmailInputXSS(): void
    {
        // Отправляем POST-запрос на маршрут, который обрабатывает ввод данных
        $response = $this->post(route('registration.restore.post'), [
            'email' => '<script>alert("XSS")</script>',
        ]);

        // Проверяем, что в ответе отсутствует внедренный скрипт XSS
        $response->assertDontSee('<script>alert("XSS")</script>');
    }

    /**
     * Дата провайдер с ошибками ввода данных в форму восстановления пароля
     *
     * @return array[]
     */
    public static function restoreFormErrorsProvider(): array
    {
        return [
            [
                'fields' => [
                    'email' => '123abc@mail.ru',
                ],
                'errors' => [
                    'email' => 'E-mail не зарегистрирован',
                ],
            ],
            [
                'fields' => [
                    'email' => ' ',
                ],
                'errors' => [
                    'email' => 'E-mail не заполнен',
                ],
            ],
            [
                'fields' => [
                    'email' => null,
                ],
                'errors' => [
                    'email' => 'E-mail не заполнен',
                ],
            ],
            [
                'fields' => [
                    'email' => '#$%#@mail.ru',
                ],
                'errors' => [
                    'email' => 'E-mail не зарегистрирован',
                ],
            ],
        ];
    }
}
