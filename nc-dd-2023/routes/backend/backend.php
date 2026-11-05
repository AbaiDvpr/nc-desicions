<?php

use App\Http\Controllers\Backend\Courses\LessonsController;
use App\Http\Controllers\Backend\Courses\ModulesController;
use App\Http\Controllers\Backend\Handbook\Bank as Bank;
use App\Http\Controllers\Backend\IndexController;
use App\Http\Controllers\Backend\Modules\Indicators\IndicatorController;
use App\Http\Controllers\Backend\Modules\NewsController;
use App\Http\Controllers\Backend\Modules\FeedbacksController;
use App\Http\Controllers\Backend\Modules\RequestRefill;
use Illuminate\Support\Facades\Route;

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
/**
 * Routes for auth backend
 */
Route::get('login', \App\Http\Controllers\Auth\Backend\LoginController::class)->name('backend.auth.login');
Route::post('login', \App\Http\Controllers\Auth\Backend\PostLoginController::class)->name('backend.auth.post.login');

Route::group(['backend', 'middleware' => ['backend']], function () {
    Route::get('/', IndexController::class)->name('backend.index');

    Route::prefix('ajax')->group(function () {
        Route::post('upload-file', \App\Http\Controllers\Backend\Ajax\UploadController::class)
            ->name('backend.ajax.upload.file');

        Route::delete('delete-file/{uuid}', \App\Http\Controllers\Backend\Ajax\RemoveController::class)
            ->name('backend.ajax.delete.file');
    });

    // Контроллер для работы с пользой
    Route::resource('users', \App\Http\Controllers\Backend\UserController::class)
        ->names('backend.users');

    Route::prefix('roles')->group(function () {
        Route::get('/', \App\Http\Controllers\Backend\Roles\IndexController::class)
            ->name('backend.roles.index');
        Route::get('/create', \App\Http\Controllers\Backend\Roles\CreateController::class)
            ->name('backend.roles.create');
        Route::post('/', \App\Http\Controllers\Backend\Roles\StoreController::class)
            ->name('backend.roles.store');
        Route::get('/edit/{role}', \App\Http\Controllers\Backend\Roles\EditController::class)
            ->name('backend.roles.edit');
        Route::put('/edit/{role}', \App\Http\Controllers\Backend\Roles\UpdateController::class)
            ->name('backend.roles.update');
        Route::delete('/delete/{role}', \App\Http\Controllers\Backend\Roles\DeleteController::class)
            ->name('backend.roles.delete');

        Route::prefix('permissions')->group(function () {
            Route::get('/', \App\Http\Controllers\Backend\Roles\Permissions\IndexController::class)
                ->name('backend.roles.permissions.index');
            Route::get('/create', \App\Http\Controllers\Backend\Roles\Permissions\CreateController::class)
                ->name('backend.roles.permissions.create');
            Route::post('/', \App\Http\Controllers\Backend\Roles\Permissions\StoreController::class)
                ->name('backend.roles.permissions.store');
            Route::get('/edit/{permission}', \App\Http\Controllers\Backend\Roles\Permissions\EditController::class)
                ->name('backend.roles.permissions.edit');
            Route::put('/edit/{permission}', \App\Http\Controllers\Backend\Roles\Permissions\UpdateController::class)
                ->name('backend.roles.permissions.update');
            Route::delete('/delete/{permission}', \App\Http\Controllers\Backend\Roles\Permissions\DeleteController::class)
                ->name('backend.roles.permissions.delete');
        });
    });

    Route::prefix('notifications')->group(function () {
        Route::get('/', \App\Http\Controllers\Backend\Notifications\IndexController::class)
            ->name('backend.notifications.index');
        Route::get('/mark-as-read/{notify_id}', \App\Http\Controllers\Backend\Notifications\MarkAsReadController::class)
            ->name('backend.notifications.mark-as-read');
    });

    Route::prefix('seo')->group(function () {
        Route::get('/', \App\Http\Controllers\Backend\Seo\IndexController::class)
            ->name('backend.seo.index');
        Route::put('/edit/{seo}', \App\Http\Controllers\Backend\Seo\UpdateController::class)
            ->name('backend.seo.update');
    });

    Route::prefix('modules')->group(function () {
        Route::prefix('client')->group(function () {
            Route::get('/', [App\Http\Controllers\Backend\Modules\Client\ClientController::class, 'index'])
                ->name('backend.modules.client.index');
            Route::post('/', [App\Http\Controllers\Backend\Modules\Client\ClientController::class, 'store'])
                ->name('backend.modules.client.store');
            Route::get('/create', [App\Http\Controllers\Backend\Modules\Client\ClientController::class, 'create'])
                ->name('backend.modules.client.create');
            Route::get('/{user}/edit', [App\Http\Controllers\Backend\Modules\Client\ClientController::class, 'edit'])
                ->name('backend.modules.client.edit');
            Route::get('/{user}/auth', [App\Http\Controllers\Backend\Modules\Client\ClientController::class, 'auth'])
                ->name('backend.modules.client.auth');
            Route::put('/{user}', [App\Http\Controllers\Backend\Modules\Client\ClientController::class, 'update'])
                ->name('backend.modules.client.update');
            Route::get('/{user}', [App\Http\Controllers\Backend\Modules\Client\ClientController::class, 'clearCache'])
                ->name('backend.modules.client.clearCache');

            Route::prefix('payments')->namespace('App\Http\Controllers\Backend\Modules\Client')->group(function () {
                Route::get('/u-{user}', "PaymentController@index")
                    ->name('backend.modules.client.payment.index');

                Route::get('/u-{user}/up-payment-form', "PaymentController@upPaymentForm")
                    ->name('backend.modules.client.payment.upPaymentForm');
                Route::post('/u-{user}/up-balance', "PaymentController@toUpBalance")
                    ->name('backend.modules.client.payment.toUpBalance'); // пополнения

                Route::get('/u-{user}/down-payment-form', "PaymentController@downPaymentForm")
                    ->name('backend.modules.client.payment.downPaymentForm');
                Route::post('/u-{user}/down-balance', "PaymentController@toDownBalance")
                    ->name('backend.modules.client.payment.toDownBalance'); // списание
            });
        });

        Route::prefix('rates')->group(function () {
            Route::get('/', \App\Http\Controllers\Backend\Modules\Rates\IndexController::class)
                ->name('backend.rates.index');
            Route::get('/create', \App\Http\Controllers\Backend\Modules\Rates\CreateController::class)
                ->name('backend.rates.create');
            Route::post('/', \App\Http\Controllers\Backend\Modules\Rates\StoreController::class)
                ->name('backend.rates.store');
            Route::get('/edit/{rate}', \App\Http\Controllers\Backend\Modules\Rates\EditController::class)
                ->name('backend.rates.edit');
            Route::put('/edit/{rate}', \App\Http\Controllers\Backend\Modules\Rates\UpdateController::class)
                ->name('backend.rates.update');
            Route::delete('/delete/{rate}', \App\Http\Controllers\Backend\Modules\Rates\DeleteController::class)
                ->name('backend.rates.delete');
            Route::get('/getrates', \App\Http\Controllers\Backend\Modules\Rates\GetRatesController::class)
                ->name('backend.rates.getrates');
        });

        Route::prefix('request-refill')->group(function () {
            Route::get('/', [RequestRefill::class, 'index'])->name('backend.request-refill.index');
            Route::put('/{id}', [RequestRefill::class, 'update'])->name('backend.request-refill.update');
        });

        Route::get('indicators/getIndicators', [IndicatorController::class, 'getIndicators'])
            ->name('backend.indicators.getIndicators');
        Route::resource('indicators', IndicatorController::class)
            ->except(['show'])
            ->names('backend.indicators');

        Route::prefix('tariff')->group(function () {
            Route::get('/', \App\Http\Controllers\Backend\Modules\Tariff\IndexController::class)
                ->name('backend.modules.tariff.index');
            Route::get('/create', \App\Http\Controllers\Backend\Modules\Tariff\CreateController::class)
                ->name('backend.modules.tariff.create');
            Route::get('/edit/{tariff}', \App\Http\Controllers\Backend\Modules\Tariff\EditController::class)
                ->name('backend.modules.tariff.edit');
            Route::put('/edit/{tariff}', \App\Http\Controllers\Backend\Modules\Tariff\UpdateController::class)
                ->name('backend.modules.tariff.update');
            Route::delete('/delete/{tariff}', \App\Http\Controllers\Backend\Modules\Tariff\DeleteController::class)
                ->name('backend.modules.tariff.delete');
        });

        Route::prefix('pages')->group(function () {
            Route::get('/', \App\Http\Controllers\Backend\Modules\Pages\IndexController::class)
                ->name('backend.modules.pages.index');
            Route::get('/create', \App\Http\Controllers\Backend\Modules\Pages\CreateController::class)
                ->name('backend.modules.pages.create');
            Route::get('/edit/{page}', \App\Http\Controllers\Backend\Modules\Pages\EditController::class)
                ->name('backend.modules.pages.edit');
            Route::put('/edit/{page}', \App\Http\Controllers\Backend\Modules\Pages\UpdateController::class)
                ->name('backend.modules.pages.update');
            Route::delete('/delete/{page}', \App\Http\Controllers\Backend\Modules\Pages\DeleteController::class)
                ->name('backend.modules.pages.delete');
        });

        Route::prefix('faq')->group(function () {
            Route::get('/', \App\Http\Controllers\Backend\Modules\Faq\IndexController::class)
                ->name('backend.modules.faq.index');
            Route::get('/edit/{faq}', \App\Http\Controllers\Backend\Modules\Faq\EditController::class)
                ->name('backend.modules.faq.edit');
            Route::put('/edit/{faq}', \App\Http\Controllers\Backend\Modules\Faq\UpdateController::class)
                ->name('backend.modules.faq.update');
            Route::delete('/delete/{faq}', \App\Http\Controllers\Backend\Modules\Faq\DeleteController::class)
                ->name('backend.modules.faq.delete');
        });

        Route::resource('feedbacks', FeedbacksController::class)
            ->except(['create', 'store', 'show'])
            ->names('backend.modules.feedbacks');

        Route::prefix('categories')->group(function () {
            Route::get('/', \App\Http\Controllers\Backend\Modules\Categories\IndexController::class)
                ->name('backend.modules.categories.index');
            Route::get('/create', \App\Http\Controllers\Backend\Modules\Categories\CreateController::class)
                ->name('backend.modules.categories.create');
            Route::get('/edit/{category}', \App\Http\Controllers\Backend\Modules\Categories\EditController::class)
                ->name('backend.modules.categories.edit');
            Route::put('/edit/{category}', \App\Http\Controllers\Backend\Modules\Categories\UpdateController::class)
                ->name('backend.modules.categories.update');
            Route::delete('/delete/{category}', \App\Http\Controllers\Backend\Modules\Categories\DeleteController::class)
                ->name('backend.modules.categories.delete');
        });

        Route::resource('news', NewsController::class)
            ->except(['show'])
            ->names('backend.modules.news');
    });

    Route::prefix('projects')->group(function () {
        Route::resource('project', \App\Http\Controllers\Backend\Projects\ProjectController::class)
            ->names('backend.projects.project');
    });

    Route::prefix('companies')->group(function () {
        Route::resource('company', \App\Http\Controllers\Backend\Companies\CompanyController::class)
            ->names('backend.companies.company');
    });

    Route::prefix('courses')->group(function () {
        Route::resource('modules', ModulesController::class)
            ->except(['show'])
            ->names('backend.courses.modules');
        Route::put('modules/{module}/order', [ModulesController::class, 'updateOrder'])
            ->name('backend.courses.modules.updateOrder');

        Route::resource('lessons', LessonsController::class)
            ->except(['show'])
            ->names('backend.courses.lessons');
        Route::put('lessons/{lesson}/order', [LessonsController::class, 'updateOrder'])
            ->name('backend.courses.lessons.updateOrder');
    });

    Route::prefix('handbook')->group(function () {
        // Маршруты для справочника банков
        Route::prefix('bank')->group(function () {
            Route::get('', [Bank::class, 'index'])->name('backend.handbook.bank.index');
            Route::get('create', [Bank::class, 'create'])->name('backend.handbook.bank.create');
            Route::get('{id}', [Bank::class, 'edit'])->name('backend.handbook.bank.edit');
            Route::post('save/{id?}', [Bank::class, 'save'])->name('backend.handbook.bank.save');
            Route::delete('{id}', [Bank::class, 'delete'])->name('backend.handbook.bank.delete');
        });
    });
});
