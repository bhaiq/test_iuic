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

Route::get('/register/{int_code?}', 'RegisterController@Register');

Route::get('tradingview', 'RegisterController@tradingview');

Route::get('/download', 'DownloadController@index');

// 转盘抽奖
Route::group(['prefix' => 'lottery'], function () {
    Route::get('index', 'LotteryController@index'); // 抽奖页面
    Route::get('log', 'LotteryController@log'); // 抽奖记录
    Route::get('info', 'LotteryController@info'); // 抽奖说明
    Route::post('submit', 'LotteryController@submit'); // 抽奖提交
});
