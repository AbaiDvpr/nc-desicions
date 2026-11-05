<?php declare(strict_types=1);
namespace Tests\Feature\Controllers\Backend;

use App\DI;
use App\Http\Middleware\VerifyCsrfToken;
use Mcamara\LaravelLocalization\Middleware\LocaleSessionRedirect;
use Tests\Base;

class BaseController extends Base
{
    /**
     * @inheritdoc
     *
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();

        $this->authAdminUser();

        $this->withoutMiddleware([
            LocaleSessionRedirect::class,
            VerifyCsrfToken::class,
        ]);
    }

    /**
     * Авторизуемся под админом
     *
     * @return void
     */
    protected function authAdminUser(): void
    {
        $this->actingAs(
            DI::getRepository()->getUser()->findByEmail('admin@ncdecision.com')
        );
    }
}
