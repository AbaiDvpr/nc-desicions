<?php declare(strict_types=1);

namespace Tests\Feature\Controllers\Auth\Frontend;

use App\Http\Controllers\Auth\LoginController;
use App\Http\Requests\Auth\PostLoginRequest;
use Tests\Feature\Controllers\Frontend\BaseController;
use Tests\Helpers\UserHelper;

class LoginControllerTest extends BaseController
{
    /**
     * Проверка загрузки страницы с формой авторизации
     *
     * @return void
     */
    public function testFrontendAuthPage(): void
    {
        $response = $this->get(route('frontend.auth.login'));

        $response->assertStatus(200);
    }

    /**
     * Проверяем  страницу авторизации
     *
     * @return void
     */
    public function testRegistrationPage(): void
    {
        $response = $this->get(route('frontend.auth.login'));

        $response->assertStatus(200);

        // проверяем наличие нужных полей в форме
        $response->assertViewHasAll([
            'email'                 => '',
            'password'              => '',
        ]);
    }

    /**
     * Проверка входа в кабинет неавторизованным пользователем
     *
     * @return void
     */
    public function testLoginToCabinetByAnUnauthorizedUser(): void
    {
        $response = $this->get(route('frontend.cabinet.home'));

        // проверяем что его перенаправляет на страницу входа
        $response->assertRedirect(route('frontend.auth.login'));
    }

    /**
     * Проверка ввода невалидных параметров в форму
     *
     * @dataProvider validationLoginFields
     *
     * @param  array $fields
     * @param  array $errors
     * @return void
     */
    public function testInvalidParametersInput(array $fields, array $errors): void
    {
        $this->assertActionUsesFormRequest(LoginController::class, 'login', PostLoginRequest::class);

        $responsePost = $this->post(route('frontend.auth.post.login'), $fields);

        $responsePost->assertInvalid($errors);
    }

    /**
     * Проверка авторизации на сайте с валидными данными
     *
     * @return void
     */
    public function testSuccessfulAuthorization(): void
    {
        $this->assertActionUsesFormRequest(LoginController::class, 'login', PostLoginRequest::class);

        $user = (new UserHelper())->createUser();

        $response = $this->post(route('frontend.auth.post.login'), [
            'email'    => $user->email,
            'password' => '123123',
        ]);

        $this->userDestroyed[] = $user->id;

        // при успешной авторизации должно перенаправить на главную страницу кабинета
        $response->assertRedirect(route('frontend.cabinet.home'));
    }

    /**
     * Проверка срабатывания XSS скрипта в поле E-mail
     *
     * @return void
     */
    public function testInputIsSanitizedFromXSS(): void
    {
        // Отправляем POST-запрос на маршрут, который обрабатывает ввод данных
        $response = $this->post(route('frontend.auth.post.login'), [
            'email'    => '<script>alert("XSS")</script>',
            'password' => '123123',
        ]);

        // Проверяем, что в ответе отсутствует внедренный скрипт XSS
        $response->assertDontSee('<script>alert("XSS")</script>');
    }

    /**
     * Проверка срабатывания SQL запроса в поле E-mail
     *
     * @return void
     */
    public function testInputIsSanitizedFromSQLInjection(): void
    {
        // Отправляем POST-запрос на маршрут, который обрабатывает ввод данных
        $this->post(route('frontend.auth.post.login'), [
            'email'    => '1; DROP TABLE users;',
            'password' => '123123',
        ]);

        // Проверяем, что в базе данных отсутствует запись, соответствующая введенным данным
        $this->assertDatabaseMissing('users', [
            'email' => '1; DROP TABLE users;',
        ]);
    }

    /**
     * Дата провайдер для теста с негативной авторизацией
     *
     * @return array
     */
    public static function validationLoginFields(): array
    {
        return [
            [
                'fields' => [
                    'email'    => null,
                    'password' => null,
                ],
                'errors' => [
                    'email'    => 'E-mail не указан',
                    'password' => 'Пароль не указан',
                ],
            ],
            [
                'fields' => [
                    'email'    => ' ',
                    'password' => ' ',
                ],
                'errors' => [
                    'email'    => 'E-mail не указан',
                    'password' => 'Пароль не указан',
                ],
            ],
            [
                'fields' => [
                    'email'    => 'client@nc.com',
                    'password' => ' ',
                ],
                'errors' => [
                    'password' => 'Пароль не указан',
                ],
            ],
            [
                'fields' => [
                    'email'    => ' ',
                    'password' => '123123',
                ],
                'errors' => [
                    'email' => 'E-mail не указан',
                ],
            ],
            [
                'fields' => [
                    'email'    => 'Test',
                    'password' => rand(100, 10000),
                ],
                'errors' => [
                    'email' => 'E-mail введен не корректно',
                ],
            ],
        ];
    }
}
