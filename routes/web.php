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


Route::post('/wx','ApiController@checkwx');//测试接口    测试  关注推送
Route::get('/ken','ApiController@Aoken');//access_token

Route::get('/custom','ApiController@custom');//自定义菜单
Route::get('/cs','ApiController@Useradd');//测试
