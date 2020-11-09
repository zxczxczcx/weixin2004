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
    return view('welcome');
});

Route::get('/test','Test\TestController@test');//测试


Route::get('/xx','ApiController@checkwx');//测试接口
Route::get('/ken','ApiController@Aoken');//access_token

Route::get('/wx','ApiController@event');//推送事件