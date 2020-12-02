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


Route::post('/wx','ApiController@checkwx');//测试接口    测试  关注推送
Route::get('/ken','ApiController@Aoken');//access_token

Route::get('/custom','ApiController@custom');//自定义菜单
Route::get('/cs','ApiController@spell');//测试

/**登录车测试 */
Route::get('/test','Test\TestController@test');//小程序测试
Route::get('/login','Test\TestController@login');//获取用户信息    并登陆
Route::get('/token','Test\TestController@access_token');//小程序 获取token
Route::get('/detail','Test\TestController@detail');//小程序 获取token
Route::get('/shoppage','Test\TestController@shoppage');//商品详情页
Route::get('/SetCart','Test\TestController@Setcart')->middleware('check.token');//加入购物车
Route::get('/GetCart','Test\TestController@GetCart');//购物车页面
Route::get('/collect','Test\TestController@collect')->middleware('check.token');//收藏
Route::post('/decr','Test\TestController@decr')->middleware('check.token');//减数量
Route::post('/incr','Test\TestController@incr')->middleware('check.token');//加数量
Route::post('/delgoods','Test\TestController@delgoods')->middleware('check.token');//删除



