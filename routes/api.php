<?php

use Illuminate\Http\Request;
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

Route::middleware('auth:api')->get('/user', function (Request $request) {
    return $request->user();
});

Route::prefix('v1')->group(function() {
    Route::post('login', 'AuthController@login');
    Route::post('register', 'AuthController@register');
    Route::get('categories','CategoryController@index');
    Route::get('categories/random/{count}','CategoryController@random');
    Route::get('hijabs/top/{count}','HijabController@top');
    Route::get('hijabs/slug/{slug}', 'HijabController@slug');
    Route::get('hijabs/search/{keyword}', 'HijabController@search');
    Route::get('hijabs', 'HijabController@index');
    Route::get('categories/slug/{slug}', 'CategoryController@slug');
    Route::get('provinces', 'ShopController@provinces');
    Route::get('cities', 'ShopController@cities');
    Route::get('couriers', 'ShopController@couriers');

    Route::middleware('auth:api')->group(function () {
    Route::post('logout', 'AuthController@logout');
    Route::post('shipping', 'ShopController@shipping');
    Route::post('services', 'ShopController@services');
    Route::post('payment', 'ShopController@payment');
    Route::post('my-order', 'ShopController@myOrder');
    Route::post('order-detail', 'ShopController@orderDetail');
    Route::post('edit-profile', 'AuthController@editProfile');
    });

    Route::get('hijab/{id}', 'HijabController@view')->where('id', '[0-9]+');
});
