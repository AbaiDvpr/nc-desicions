<?php declare(strict_types=1);

use App\Http\Controllers\Api\StatusController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\Socialite\AuthController;
use App\Http\Controllers\Frontend\Modules\FeedbacksController;
use App\Http\Controllers\Frontend\Modules\FaqController;
use App\Http\Controllers\Frontend\Modules\NewsController;
use App\Http\Controllers\Frontend\Modules\PagesController;
use App\Http\Controllers\Frontend\Payment\ConfirmController;
use App\Http\Controllers\Frontend\RegistrationController;
use Illuminate\Support\Facades\Route;
use Mcamara\LaravelLocalization\Facades\LaravelLocalization;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/
Route::group(['prefix' => LaravelLocalization::setLocale(), 'middleware' => [ 'localeSessionRedirect', 'localizationRedirect']], function () {
    Route::group(['frontend-auth', 'prefix' => 'auth'], function () {
        Route::get('login', [LoginController::class, 'index'])->name('frontend.auth.login');
        Route::post('login', [LoginController::class, 'login'])->name('frontend.auth.post.login');
    });

    Route::get('/', \App\Http\Controllers\Frontend\IndexController::class)->name('frontend.index');

    // Регистрация
    Route::group(['prefix' => 'registration'], function () {
        Route::get('/', [RegistrationController::class, 'index'])
            ->name('registration.index');
        Route::post('/', [RegistrationController::class, 'store'])
            ->name('registration.post');
        Route::get('/verification/{code}', [RegistrationController::class, 'verification'])
            ->name('registration.verification');

        Route::get('/restore', [RegistrationController::class, 'restore'])
            ->name('registration.restore');
        Route::post('/restore', [RegistrationController::class, 'postRestore'])
            ->name('registration.restore.post');

        Route::get('/restore/code-{code}', [RegistrationController::class, 'updatePassword'])
            ->name('registration.restore.code');
        Route::post('/restore/code-{code}', [RegistrationController::class, 'postUpdatePassword'])
            ->where('code', '(.*)')
            ->name('registration.restore.code.update');
    });

    Route::get('/page/{slug}', PagesController::class)->name('frontend.modules.page');

    Route::post('/feedback', [FeedbacksController::class, 'storeAction'])->name('frontend.modules.feedback.store');

    Route::group(['prefix' => 'faq'], function () {
        Route::get('/', [FaqController::class, 'index'])->name('frontend.modules.faq');
        Route::post('/', [FaqController::class, 'storeAction'])->name('frontend.modules.faq.store');
    });

    Route::group(['prefix' => 'news', 'middleware' => 'modules.news'], function () {
        Route::get('/{slug}', [NewsController::class, 'index'])->name('frontend.modules.news.index');
        Route::get('/{slug}/{id}', [NewsController::class, 'show'])->name('frontend.modules.news.show');
    });

    // Экран для пользователя который он увидит после оплаты в wooppay
    Route::get('/payment/confirm/{token}', ConfirmController::class)
        ->where('token', '(.*)')
        ->name('frontend.payment.confirm');
});

Route::group(['frontend-auth', 'prefix' => 'auth'], function () {
    Route::get('/login/{client}', [AuthController::class, 'login'])->name('frontend.auth.socialite');

    Route::get('/google/callback', [AuthController::class, 'googleAction'])->name('frontend.auth.callback.google');
    Route::get('/github/callback', [AuthController::class, 'githubAction'])->name('frontend.auth.callback.github');
    Route::get('/facebook/callback', [AuthController::class, 'facebookAction'])->name('frontend.auth.callback.facebook');

    Route::get('/{user}', [LoginController::class, 'loginBySignature'])->name('frontend.auth.login.signature');
});

/**
 * Маршруты для скачивания отчетов по проектам
 */
Route::group(['prefix' => 'reports', 'middleware' => ['auth', 'access.report']], function () {
    Route::get('get/{uuid}', \App\Http\Controllers\Reports\DownloadController::class)->name('report.get');
});

Route::group(['prefix' => 'health'], function () {
    Route::get('v1/status', [StatusController::class, 'status'])->name('health.status');
});
