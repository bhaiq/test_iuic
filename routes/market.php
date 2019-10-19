<?php

Route::get('/config', 'ConfigController@index');
Route::get('/time', 'ConfigController@time');

Route::get('/symbols', 'SymbolController@info');

Route::get('/history', 'HistoryController@index');
