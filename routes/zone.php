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

Route::get('/us', 'SystemController@usInfo');
Route::get('/us_ex', 'SystemController@zoneUs');
Route::get('/exchange', 'SystemController@zoneExchange');
Route::get('/country', 'SystemController@zoneCountryTotal');
Route::get('/week', 'SystemController@zoneWeekTotal');
Route::get('/pusher', 'SystemController@zonePusher');


