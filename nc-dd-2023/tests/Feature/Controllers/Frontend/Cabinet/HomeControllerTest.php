<?php declare(strict_types=1);
namespace Tests\Feature\Controllers\Frontend\Cabinet;

use Tests\Feature\Controllers\Frontend\BaseController;


class HomeControllerTest extends BaseController
{
    /**
     * Проверяем вход в кабинет не авторизованным пользователем
     *
     * @return void
     */
   public function testLoginToCabinetByAnUnauthorizedUser()
   {
       $response = $this->get(route('frontend.cabinet.home'));

       // проверяем что его перенаправляет на страницу входа
       $response->assertRedirect(route('frontend.auth.login'));
   }
}
