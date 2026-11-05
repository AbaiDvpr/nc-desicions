<?php declare(strict_types=1);
namespace Tests\Feature\Controllers\Frontend;

use App\Http\Middleware\VerifyCsrfToken;
use App\Models\User;
use Mcamara\LaravelLocalization\Middleware\LaravelLocalizationRedirectFilter;
use Mcamara\LaravelLocalization\Middleware\LocaleSessionRedirect;
use Tests\Base;
use Tests\Helpers\UserHelper;

class BaseController extends Base
{
    /**
     * Пользователь создаваемы для тестов в кабинете
     *
     * @var \App\Models\User|null
     */
    public User|null $user = null;

    /**
     * @inheritdoc
     *
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware([
            LocaleSessionRedirect::class,
            LaravelLocalizationRedirectFilter::class,
            VerifyCsrfToken::class,
        ]);
    }

    /**
     * Создаем пользователя и авторизуемся под ним в кабинете
     *
     * @return void
     */
    protected function authCabinetUser(): void
    {
        $this->user = (new UserHelper())->createUser();

        $this->actingAs($this->user);

        $this->userDestroyed[] = $this->user->getAuthIdentifier();
    }
}
