<?php

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
	echo phpinfo();
    //return view('welcome');
});
Route::post('/wx','TextController@wx'); //微信接入

Route::get('/access_token','TextController@access_token'); //获取access_token

Route::get('/test2','TextController@test2'); //测试
Route::post('/test3','TextController@test3'); //测试3

Route::get('/getweather','TextController@getweather');  //天气
Route::get('/createMenu','TextController@createMenu');  //
Route::any('/eckSignature','TextController@checkSignature');
