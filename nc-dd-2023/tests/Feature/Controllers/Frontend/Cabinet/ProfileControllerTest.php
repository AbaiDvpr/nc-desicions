<?php declare(strict_types=1);
namespace Tests\Feature\Controllers\Frontend\Cabinet;

use Illuminate\Support\Carbon;
use Illuminate\Testing\Fluent\AssertableJson;
use Tests\Feature\Controllers\Frontend\BaseController;
use Tests\Helpers\UserHelper;

/**
 * Тесты на редактирование профиля пользователя в кабинете
 */
class ProfileControllerTest extends BaseController
{
    /**
     * Создание клиента пользователя перед тестами
     */
    public function setUp(): void
    {
        parent::setUp();

        $this->authCabinetUser();
    }

    /**
     * Проверяем валидацию формы редактирования профиля
     *
     * @return void
     */
   public function testUpdateProfileWithInvalidParameters()
   {
       $response = $this->postJson(route('frontend.cabinet.profile.update'), [
           'name'     => $this->user->getName(),
           'email'    => $this->user->getEmail(),
           'birthday' => $this->faker->dateTimeBetween('-10 years')->format('Y-m-d'),
       ]);

       $response->assertJsonPath('errors.mobile.0', 'Номер телефона не указан');
       $response->assertJsonPath('errors.birthday.0', 'Вам еще не исполнилось 18 лет');

       // В ответе должен быть редирект
       $response->isRedirect();
   }

    /**
     * Проверяем успешную валидацию и обновление данных пользователя
     *
     * @return void
     */
    public function testUpdateProfileWithValidParameters()
    {
        $birthday = $this->faker->dateTimeBetween('-30 years', '-18 years')->format('Y-m-d');
        $mobile   = (new UserHelper())->getRandomPhone();

        $response = $this->postJson(route('frontend.cabinet.profile.update'), [
            'name'     => $this->user->getName(),
            'email'    => $this->user->getEmail(),
            'birthday' => $birthday,
            'mobile'   => $mobile,
        ]);

        $response->assertJson(fn (AssertableJson $json) =>
            $json->has('user', fn (AssertableJson $user) =>
                $user->where('email', $this->user->getEmail())
                    ->where('name', $this->user->getName())
                    ->where('birthday', Carbon::parse($birthday)->format('Y.m.d'))
                    ->where('mobile', formatPhone($mobile))
                    ->etc()
            )
            ->where('redirectUrl', null)
        );
    }
}
