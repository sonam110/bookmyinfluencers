<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\InstagramController;
use App\Http\Controllers\IGController;
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

Route::get('/', function () {
    //print_r($_ENV['FACEBOOK_APP_ID']);
    return view('welcome');
});

Auth::routes();
Route::middleware(['influencer'])->group(function () {
    Route::get('/home', [App\Http\Controllers\HomeController::class, 'index'])->name('home');
});


Route::get('/clear', function() {

    Artisan::call('cache:clear');
   // Artisan::call('config:cache');
    Artisan::call('view:clear');
    Artisan::call('config:clear');
    Artisan::call('optimize');
 
    return "Cleared!";
 
 });


Route::get('instagram/login', 'InstagramController@auth')->name('instagram-login');
Route::get('instagram/callback', 'InstagramController@callback');
Route::get('generateIGToken', 'IGController@generateIGToken');
Route::get('callback', 'IGController@callback');



Route::get('auth/facebook', [InstagramController::class, 'facebookRedirect']);
Route::get('auth/facebook/callback', [InstagramController::class, 'loginWithFacebook']);
//Route::get('social/login/{provider}/redirect', 'socialiteController@handleProviderRedirect');