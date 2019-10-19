<?php

use Illuminate\Http\Request;

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

Route::group(['middleware' => ['auth.api']], function () {

    Route::get('ex/history/{team_id}', 'ExOrderController@_list')->where('team_id', '[0-9]+');
    Route::get('ex/history_price/{team_id}', 'ExOrderController@price')->where('team_id', '[0-9]+');

    Route::get('/init', 'ConfigController@init');
    Route::get('/system/checkVersion', 'ConfigController@version');

    Route::get('/article', 'ArticleController@_list');

    Route::post('/userLogin', 'UserController@login');
    Route::post('/userLogout', 'UserController@logout');
    Route::post('/user', 'UserController@create');
    Route::get('/userSelf', 'UserController@info');
    Route::get('/user/{username}', 'UserController@find');
    Route::get('/userCode', 'UserController@getCode');
    Route::put('/userForget', 'UserController@forgetPassword');
    Route::post('/userInfo', 'UserController@update');
    Route::post('/userAuth', 'UserController@auth');
    Route::put('/userPay', 'UserController@payPassword');
    Route::put('/userPassword', 'UserController@setPassword');

    Route::get('/account_secret', 'AccountController@_listSecret');
    Route::get('/account', 'AccountController@_list');
    Route::put('/account', 'AccountController@trans');
    Route::get('/accountLog/{coin_id}', 'AccountController@log')->where('coin_id', '[0-9]+');

    Route::get('/coin', 'CoinController@_list');
    Route::get('/exTeam/{id}', 'CoinController@getTeam')->where('coin_id', '[0-9]+');

    Route::get('ex/buy/{team_id}', 'ExOrderBuyController@_list')->where('team_id', '[0-9]+');
    Route::post('ex/buy/{team_id}', 'ExOrderBuyController@create')->where('team_id', '[0-9]+');
    Route::delete('ex/buy/{id}', 'ExOrderBuyController@del')->where('id', '[0-9]+');

    Route::get('ex/sell/{team_id}', 'ExOrderSellController@_list')->where('team_id', '[0-9]+');
    Route::post('ex/sell/{team_id}', 'ExOrderSellController@create')->where('team_id', '[0-9]+');
    Route::delete('ex/sell/{id}', 'ExOrderSellController@del')->where('id', '[0-9]+');

    Route::get('ex/{team_id}/price', 'ExTeamController@curPrice')->where('team_id', '[0-9]+');
    Route::get('ex/{team_id}/order', 'ExOrderBuyController@selfList')->where('team_id', '[0-9]+');
//    Route::get('/exTeam/{team_id}/price', 'ExTeamController@curPrice')->where('team_id', '[0-9]+');
//    Route::get('/exTeam/{team_id}/order', 'ExTeamController@selfList')->where('team_id', '[0-9]+');

    Route::get('/otcSellSelf', 'OtcSellController@selfList');
    Route::get('/otcSell/{id}', 'OtcSellController@info')->where('id', '[0-9]+');
    Route::get('/coin/{coin_id}/otcSell', 'OtcSellController@_list');
    Route::post('/coin/{coin_id}/otcSell', 'OtcSellController@create');
    Route::put('/otcSell/{id}', 'OtcSellController@update')->where('id', '[0-9]+');
    Route::delete('/otcSell/{id}', 'OtcSellController@del')->where('id', '[0-9]+');

    Route::get('/otcBuySelf', 'OtcBuyController@selfList');
    Route::get('/otcBuy/{id}', 'OtcBuyController@info')->where('id', '[0-9]+');
    Route::get('/coin/{coin_id}/otcBuy', 'OtcBuyController@_list');
    Route::post('/coin/{coin_id}/otcBuy', 'OtcBuyController@create');
    Route::put('/otcBuy/{id}', 'OtcBuyController@update')->where('id', '[0-9]+');
    Route::delete('/otcBuy/{id}', 'OtcBuyController@del')->where('id', '[0-9]+');

    Route::get('/otcOrder', 'OtcOrderController@_list');
    Route::put('/otcOrder/{id}/pay', 'OtcOrderController@pay')->where('id', '[0-9]+');
    Route::put('/otcOrder/{id}/coin', 'OtcOrderController@payCoin')->where('id', '[0-9]+');
    Route::put('/otcOrder/{id}/appeal', 'OtcOrderController@appeal')->where('id', '[0-9]+');
    Route::delete('/otcOrder/{id}', 'OtcOrderController@del')->where('id', '[0-9]+');

    Route::get('modePay', 'ModePayController@_list');
    Route::get('modePay/{id}', 'ModePayController@info');
    Route::post('modePay', 'ModePayController@create');
    Route::put('modePay/{id}', 'ModePayController@update');
    Route::delete('modePay/{id}', 'ModePayController@del');

    Route::get('FAQ', 'FAQController@_list');
    Route::get('FAQ/{id}', 'FAQController@detail')->where('id', '[0-9]+');

    Route::get('feedback', 'FeedbackController@_list');
    Route::get('feedback/{id}', 'FeedbackController@detail')->where('id', '[0-9]+');
    Route::post('feedback', 'FeedbackController@create')->where('id', '[0-9]+');

    Route::post('feedback/{id}/comment', 'FeedbackController@comment')->where('id', '[0-9]+');

    Route::get('wallet', 'WalletController@_list');
    Route::get('wallet/{coin_id}', 'WalletController@detail')->where('coin_id', '[0-9]+');

    Route::get('test', 'ConfigController@test');

    Route::post('file', 'FileController@create');

//    Route::post('/extract/usdt', 'EthController@extract'); // usdt提现
//    Route::get('/extract/list/usdt', 'EthController@getExtraceList'); // 提现列表
    Route::get('/extract/config', 'EthController@extractConfig'); // 提现配置
    Route::post('/extract/submit', 'EthController@extractSubmit'); // 提现配置
    Route::get('/extract/list', 'EthController@extractList'); // 提现列表

    Route::get('browser/market', 'BrowserController@market');

    Route::get('banner', 'BannerController@_list');

    Route::get('news', 'NewsController@_list');
    Route::get('news/{id}', 'NewsController@detail')->where('id', '[0-9]+');

    // 商城报单
    Route::group(['prefix' => 'shop'], function () {

        Route::get('goods', 'ShopController@goods'); // 商城商品
        Route::get('order', 'ShopController@order'); // 商城订单
        Route::post('order', 'ShopController@store'); // 新增商城订单
        Route::get('order/{id}', 'ShopController@orderInfo')->where('id', '[0-9]+'); // 商城订单详情

        Route::get('address', 'ShopAddressController@index'); // 收货地址列表
        Route::post('address', 'ShopAddressController@store'); // 新增地址
        Route::put('address/{id}', 'ShopAddressController@update')->where(['id' => '[0-9]+']); // 修改地址
        Route::delete('address/{id}', 'ShopAddressController@destroy')->where(['id' => '[0-9]+']); // 删除地址

    });

    // 用户推荐
    Route::group(['prefix' => 'user/recommend'], function () {

        Route::get('info', 'RecommendController@info'); // 用户推荐信息
        Route::get('list', 'RecommendController@list'); // 用户推荐列表

    });

    // 用户矿池
    Route::group(['prefix' => 'pool'], function () {

        Route::get('log', 'PoolController@log'); // 用户矿池记录

    });

    // 用户分红日志
    Route::get('bonus/log', 'BonusController@log');

    // 商家验证
    Route::group(['prefix' => 'business'], function () {

        Route::get('start', 'BusinessController@start'); //商家认证条件
        Route::post('submit', 'BusinessController@submit'); //商家认证提交
        Route::post('quit', 'BusinessController@quit'); //退出商家认证

    });

    // 合伙人分红
    Route::group(['prefix' => 'partner'], function () {

        Route::get('start', 'PartnerController@start'); //合伙人页面
        Route::post('submit', 'PartnerController@submit'); //合伙人提交申请
        Route::get('log', 'PartnerController@log'); //合伙人收益日志

    });

    // 社区
    Route::group(['prefix' => 'community'], function () {

        Route::get('city', 'CommunityController@getGity'); //获取城市社区
        Route::post('submit', 'CommunityController@submit'); //申请社区提交

    });

});

Route::post('/handleEthTransaction', 'EthController@handleEthTokenTransaction');


