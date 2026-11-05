<?php declare(strict_types=1);

use App\Http\Controllers\Frontend\Cabinet\CompanyController;
use App\Http\Controllers\Frontend\Cabinet\ProfileController;
use App\Http\Controllers\Frontend\Cabinet\Projects\ProjectController;
use App\Http\Controllers\Frontend\Cabinet\Projects\UpdateController as ProjectUpdateController;
use App\Http\Controllers\Frontend\Cabinet\Projects\Form\FormController as ProjectFormController;
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

Route::group([
    'prefix'     => LaravelLocalization::setLocale(),
    'middleware' => ['localeSessionRedirect', 'localizationRedirect'],
], function () {
    Route::group(['prefix' => 'cabinet', 'middleware' => ['cabinet']], function () {
        Route::get('/', \App\Http\Controllers\Frontend\Cabinet\HomeController::class)
            ->name('frontend.cabinet.home');

        Route::group(['prefix' => 'profile'], function () {
            Route::post('/', [ProfileController::class, 'updateAction'])
                ->name('frontend.cabinet.profile.update');
            Route::post('/upload-avatar', [ProfileController::class, 'uploadAvatarAction'])
                ->name('frontend.cabinet.profile.uploadAvatar');
            Route::put('/course-eula', [ProfileController::class, 'applyCourseEulaAction'])
                ->name('frontend.cabinet.profile.applyCourseEula');
        });

        Route::group(['prefix' => 'payments'], function () {
            $paymentClass = \App\Http\Controllers\Frontend\Cabinet\PaymentController::class;

            Route::get('/', [$paymentClass, 'index'])->name('frontend.cabinet.payments.index');

            Route::get('/getTariff/{id}', [$paymentClass, 'getTariffAction'])
                ->name('frontend.cabinet.payments.getTariff');

            Route::get('/getTariffs/', [$paymentClass, 'getTariffsAction'])
                ->name('frontend.cabinet.payments.getTariffs');

            Route::post('request-refill', [$paymentClass, 'sendRequestRefillAction'])
                ->name('frontend.cabinet.payment.refillRequestSend');

            Route::post('/pay/{id}', [$paymentClass, 'pay'])->name('frontend.cabinet.payments.pay');
        });

        Route::group(['prefix' => 'projects'], function () {
            Route::get('/', [ProjectController::class, 'getListAction'])
                ->name('frontend.cabinet.projects.getListAction');

            Route::get('/checkCreate', [ProjectController::class, 'checkCreateAction'])
                ->name('frontend.cabinet.projects.checkCreateAction');

            Route::post('/create', [ProjectController::class, 'createAction'])
                ->name('frontend.cabinet.projects.createAction');

            Route::group(['prefix'  => 'p', 'middleware' => 'access.project'], function () {
                Route::get('/{uuid}/checkCorrect', [ProjectController::class, 'checkCorrectAction'])
                    ->name('frontend.cabinet.projects.checkCorrectAction');

                Route::put('/{uuid}/step{step}', ProjectUpdateController::class)
                    ->name('frontend.cabinet.projects.update');

                Route::post('/{uuid}/steps', [ProjectFormController::class, 'getCountSteps'])
                    ->name('frontend.cabinet.projects.form.getCountSteps');

                Route::post('/{uuid}/get-current-step', [ProjectFormController::class, 'getCurrentStep'])
                    ->name('frontend.cabinet.projects.form.getCurrentStep');

                Route::post('/{uuid}/step-{step}', [ProjectFormController::class, 'getFormByStep'])
                    ->name('frontend.cabinet.projects.form.getFormByStep');

                Route::post('/{uuid}/equipment-list', [ProjectFormController::class, 'saveEquipmentItem'])
                    ->name('frontend.cabinet.projects.form.saveEquipmentItem');

                Route::put('/{uuid}/equipment-list/{id}', [ProjectFormController::class, 'deleteEquipmentItem'])
                    ->name('frontend.cabinet.projects.form.deleteEquipmentItem');

                Route::post('/{uuid}/salary-fund', [ProjectFormController::class, 'saveSalaryFundItem'])
                    ->name('frontend.cabinet.projects.form.saveSalaryFundItem');

                Route::put('/{uuid}/salary-fund/{id}', [ProjectFormController::class, 'deleteSalaryFundItem'])
                    ->name('frontend.cabinet.projects.form.deleteSalaryFundItem');

                Route::post('/{uuid}/volume-sales', [ProjectFormController::class, 'saveVolumeSalesItem'])
                    ->name('frontend.cabinet.projects.form.saveVolumeSalesItem');

                Route::put('/{uuid}/volume-sales/{id}', [ProjectFormController::class, 'deleteVolumeSalesItem'])
                    ->name('frontend.cabinet.projects.form.deleteVolumeSalesItem');

                Route::post('/{uuid}/variable-costs', [ProjectFormController::class, 'saveVariableCostsItem'])
                    ->name('frontend.cabinet.projects.form.saveVariableCostsItem');

                Route::put('/{uuid}/variable-costs/{id}', [ProjectFormController::class, 'deleteVariableCostsItem'])
                    ->name('frontend.cabinet.projects.form.deleteVariableCostsItem');

                Route::post('/{uuid}/ready-premises-list', [ProjectFormController::class, 'saveReadyPremisesItem'])
                    ->name('frontend.cabinet.projects.form.saveReadyPremisesItem');

                Route::put('/{uuid}/ready-premises-list/{id}', [ProjectFormController::class, 'deleteReadyPremisesItem'])
                    ->name('frontend.cabinet.projects.form.deleteReadyPremisesItem');

                Route::post('/{uuid}/smr-list', [ProjectFormController::class, 'saveSmrItem'])
                    ->name('frontend.cabinet.projects.form.saveSmrItem');

                Route::put('/{uuid}/smr-list/{id}', [ProjectFormController::class, 'deleteSmrItem'])
                    ->name('frontend.cabinet.projects.form.deleteSmrItem');

                Route::post('/{uuid}/overhaul-list', [ProjectFormController::class, 'saveOverhaulItem'])
                    ->name('frontend.cabinet.projects.form.saveOverhaulItem');

                Route::put('/{uuid}/overhaul-list/{id}', [ProjectFormController::class, 'deleteOverhaulItem'])
                    ->name('frontend.cabinet.projects.form.deleteOverhaulItem');
            });
        });

        Route::group(['prefix' => 'companies'], function () {
            Route::get('/', [CompanyController::class, 'getListAction'])
                ->name('frontend.cabinet.companies.getListAction');

            Route::post('/save', [CompanyController::class, 'saveAction'])
                ->name('frontend.cabinet.companies.saveAction');

            Route::group(['middleware' => 'access.company'], function() {
                Route::get('/getOne', [CompanyController::class, 'getOneAction'])
                    ->name('frontend.cabinet.companies.getOneAction');

                Route::group(['prefix' => 'import'], function () {
                    Route::post('/', [CompanyController::class, 'uploadImportFileAction'])
                        ->name('frontend.cabinet.companies.uploadImportFileAction');

                    Route::get('/status', [CompanyController::class, 'getImportFileStatusAction'])
                        ->name('frontend.cabinet.companies.getImportFileStatusAction');
                });

                Route::group(['prefix' => 'opu'], function () {
                    Route::get('/report', [CompanyController::class, 'getOpuReportAction'])
                        ->name('frontend.cabinet.companies.opu.getOpuReportAction');

                    Route::get('/report/plan', [CompanyController::class, 'getOpuReportPlanAction'])
                        ->name('frontend.cabinet.companies.opu.getOpuReportPlanAction');
                });

                Route::group(['prefix' => 'dds'], function () {
                    Route::get('/report', [CompanyController::class, 'getDdsReportAction'])
                        ->name('frontend.cabinet.companies.dds.getDdsReportAction');

                    Route::get('/report/plan', [CompanyController::class, 'getDdsReportPlanAction'])
                        ->name('frontend.cabinet.companies.dds.getDdsReportPlanAction');
                });

                Route::group(['prefix' => 'balance'], function () {
                    Route::get('/report', [CompanyController::class, 'getBalanceReportAction'])
                        ->name('frontend.cabinet.companies.balance.getBalanceReportAction');

                    Route::get('/report/plan', [CompanyController::class, 'getBalanceReportPlanAction'])
                        ->name('frontend.cabinet.companies.balance.getBalanceReportPlanAction');
                });

                Route::group(['prefix' => 'indicators'], function () {
                    Route::get('/', [CompanyController::class, 'getIndicatorsAction'])
                        ->name('frontend.cabinet.indicators.getIndicatorsAction');

                    Route::post('/create', [CompanyController::class, 'createIndicatorsAction'])
                        ->name('frontend.cabinet.indicators.create');

                    Route::put('/{id}', [CompanyController::class, 'updateIndicatorsAction'])
                        ->name('frontend.cabinet.indicators.update');

                    Route::delete('/{id}', [CompanyController::class, 'deleteIndicatorsAction'])
                        ->name('frontend.cabinet.indicators.delete');
                });
            });
        });
    });
});
