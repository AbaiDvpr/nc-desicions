<?php declare(strict_types=1);
namespace Controllers\Frontend\Home;

use Tests\Feature\Controllers\Frontend\BaseController;

class IndexControllerTest extends BaseController
{
    /**
     * Тест перенаправления на язык указанный по умолчанию
     *
     * @return void
     */
    public function testShowHomePage()
    {
        $response = $this->get('/');

        $response->assertStatus(200);
    }
}
