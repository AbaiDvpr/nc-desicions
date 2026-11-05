<?php declare(strict_types=1);

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Auth Routes for backend and frontend
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/
//Route::group(['frontend-auth', 'prefix' => 'auth'], function () {
//    Route::get('/{client}', \App\Http\Controllers\Auth\Socialite\ClientController::class)->name('frontend.auth.social.client');
//    Route::get('/{client}/callback', \App\Http\Controllers\Auth\Socialite\AuthClientController::class)->name('frontend.auth.social.callback');
//});

Route::get('/logout/exit', \App\Http\Controllers\Auth\LogoutController::class)->name('auth.exit');
