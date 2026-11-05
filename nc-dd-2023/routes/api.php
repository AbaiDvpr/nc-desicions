<?php

use App\Http\Controllers\Api\Cabinet\Companies\AccountingController as AccountingController;
use App\Http\Controllers\Api\Cabinet\CounterpartyController;
use App\Http\Controllers\Api\Cabinet\CourseController;
use App\Http\Controllers\Api\Cabinet\NotificationsController;
use App\Http\Controllers\Api\Cabinet\Payment\WooppayController;
use App\Http\Controllers\Api\Cabinet\Widgets\DdsController;
use App\Http\Controllers\Api\Cabinet\Widgets\OpuController;
use App\Http\Controllers\Api\Handbook\HandbookController;
use App\Http\Controllers\Api\Handbook\ArticleController as ArticleController;
use App\Http\Controllers\Api\Handbook\BankController;
use App\Http\Controllers\Api\Handbook\DirectionController;
use App\Http\Controllers\Api\Handbook\Forms\IncomeController;
use App\Http\Controllers\Api\Handbook\Forms\OutcomeController;
use App\Http\Controllers\Api\User\WebsocketController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::get('/', [\App\Http\Controllers\Api\IndexController::class, 'index'])->name('api.index');

//Route::middleware('auth:api')->get('/user', function (Request $request) {
//    return $request->user();
//});

/**
 * Роуты для обращения
 */
Route::group(['prefix' => 'v1', 'middleware' => ['auth:sanctum']], function () {

    Route::group(['prefix' => 'user'], function () {
        Route::get('/websocket/getConfig', [WebsocketController::class, 'getConfig'])
            ->name('api.user.websocket.getConfig');
    });

    Route::group(['prefix' => 'handbook'], function () {
        Route::get('/', [HandbookController::class, 'getAll'])->name('api.handbook');
        Route::post('/create', [HandbookController::class, 'create'])->name('api.handbook.create');

        Route::group(['prefix' => 'income'], function () {
            Route::get('/types', [IncomeController::class, 'getTypesAction'])->name('api.handbook.income.types');
        });

        Route::group(['prefix' => 'outcome'], function () {
            Route::get('/types', [OutcomeController::class, 'getTypesAction'])->name('api.handbook.outcome.types');
        });

        Route::group(['prefix' => 'banks'], function () {
            Route::get('/', [BankController::class, 'getAll'])->name('api.handbook.banks');

            Route::get('/{uuid}', [BankController::class, 'getOne'])->name('api.handbook.banks.one');
        });

        Route::group(['prefix' => 'articles'], function () {
            Route::get('/',  [ArticleController::class, 'getAll'])->name('api.handbook.articles');
            Route::post('/', [ArticleController::class, 'create'])->name('api.handbook.articles.create');
        });

        Route::group(['prefix' => 'direction'], function () {
            Route::post('/', [DirectionController::class, 'getAll'])->name('api.handbook.direction');
            Route::post('/create', [DirectionController::class, 'create'])->name('api.handbook.direction.create');
        });
    });

    Route::group(['prefix' => 'cabinet'], function () {
        Route::group(['prefix' => 'company'], function () {
            // Работа с доходной/расходной частью
            Route::group(['prefix' => 'accounting'], function () {
                Route::post('/getOne', [AccountingController::class, 'getOne'])
                    ->name('api.cabinet.company.accounting.getOne');

                Route::get('/{uuid}', [AccountingController::class, 'getList'])
                    ->name('api.cabinet.company.accounting');

                Route::post('/save', [AccountingController::class, 'saveAction'])
                    ->name('api.cabinet.company.accounting.save');

                Route::put('/delete', [AccountingController::class, 'deleteAction'])
                    ->name('api.cabinet.company.accounting.delete');
            });
        });

        Route::group(['prefix' => 'notifications'], function () {
            Route::get('/unread', [NotificationsController::class, 'getUnreadNotifications'])
                ->name('api.cabinet.notifications.unread');
            Route::put('/{id}', [NotificationsController::class, 'markAsReadNotificationAction'])
                ->name('api.cabinet.notifications.markRead');
        });

        Route::group(['prefix' => 'widgets'], function () {
            Route::group(['prefix' => 'opu', 'middleware' => 'access.company'], function () {
                Route::get('/netProfitDonut', [OpuController::class, 'getNetProfitDonutAction'])
                    ->name('api.cabinet.widgets.opu.netProfitDonut');
                Route::get('/netProfitArea', [OpuController::class, 'getNetProfitAreaAction'])
                    ->name('api.cabinet.widgets.opu.netProfitArea');
            });

            Route::group(['prefix' => 'dds', 'middleware' => 'access.company'], function () {
                Route::get('/cashByEndPeriodDonut', [DdsController::class, 'getCashByEndPeriodDonutAction'])
                    ->name('api.cabinet.widgets.dds.cashByEndPeriodDonut');
                Route::get('/cashByEndPeriodArea', [DdsController::class, 'getCashByEndPeriodAreaAction'])
                    ->name('api.cabinet.widgets.dds.cashByEndPeriodArea');
            });
        });

        Route::group(['prefix' => 'counterparties'], function () {
            Route::get('/', array(CounterpartyController::class, 'index'))
                ->name('api.cabinet.counterparty.index');
            Route::post('/save', array(CounterpartyController::class, 'save'))
                ->name('api.cabinet.counterparty.save');
            Route::delete('/{uuid}', array(CounterpartyController::class, 'delete'))
                ->name('api.cabinet.counterparty.delete');
        });

        Route::group(['prefix' => 'courses', 'middleware' => 'access.course'], function () {
            Route::group(['prefix' => 'modules'], function () {
                Route::get('/', [CourseController::class, 'getModules'])
                    ->name('api.cabinet.courses.modules.all');

                Route::get('/{module}', [CourseController::class, 'getOneModule'])
                    ->name('api.cabinet.courses.modules.getOne');

                Route::get('/{module}/lessons', [CourseController::class, 'getLessons'])
                    ->name('api.cabinet.courses.modules.lessons');

                Route::post('/{module}/lessons/{lesson}/rate', [CourseController::class, 'setRateLesson'])
                    ->name('api.cabinet.courses.modules.lesson.rate');

                Route::post('/{module}/lessons/{lesson}/complete', [CourseController::class, 'setCompleteLesson'])
                    ->name('api.cabinet.courses.modules.lesson.complete');
            });
        });
    });
});

Route::group(['middleware' => 'api'], function () {
    Route::get('/getrates', [\App\Http\Controllers\Api\Rates::class, 'getRatesAction'])->name('api.getrates');

    Route::group(['prefix' => 'wooppay'], function () {
        // Эндпоинт для подтверждения оплаты от wooppay
        Route::get('/confirm/{token}', [WooppayController::class, 'confirmAction'])
            ->name('api.wooppay.confirm')
            ->where('token', '.*');
    });
});
